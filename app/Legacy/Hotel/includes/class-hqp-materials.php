<?php

/** Shared generation and delivery for hotel QR images and printable flyers. */
class HQP_Materials {

    public static function download( $format ) {
        $post_id = self::authorize( $format );
        $token = (string) get_post_meta( $post_id, '_hqp_token', true );
        if ( '' === $token ) {
            wp_die( 'Guarda el hotel para generar su código QR.' );
        }

        $url = add_query_arg( 'promo', $token, home_url( '/reservas-hotel/' ) );
        $image = self::qr_image( $url );
        if ( is_wp_error( $image ) ) {
            wp_die( esc_html( $image->get_error_message() ) );
        }

        $inline = 'qr' === $format && isset( $_GET['inline'] ) && '1' === $_GET['inline'];
        $name = sanitize_title( get_the_title( $post_id ) );
        $name = $name ? $name : (string) $post_id;
        if ( 'qr' === $format ) {
            if ( ! $inline ) {
                \MeTransfers\Admin\AuditLog::record( 'hotel.qr_downloaded', 'hotel', $post_id );
            }
            self::send( $image, 'image/png', 'qr-hotel-' . $name . '.png', $inline );
        }

        $pdf = self::flyer( $post_id, $image );
        if ( is_wp_error( $pdf ) ) {
            wp_die( esc_html( $pdf->get_error_message() ) );
        }
        \MeTransfers\Admin\AuditLog::record( 'hotel.flyer_generated', 'hotel', $post_id );
        self::send( $pdf, 'application/pdf', 'Flyer-Hotel-' . $name . '.pdf', false );
    }

    private static function authorize( $format ) {
        if ( ! isset( $_GET['post_id'], $_GET['nonce'] ) || ! is_scalar( $_GET['post_id'] ) || ! is_string( $_GET['nonce'] ) ) {
            wp_die( 'Faltan parámetros válidos.', '', array( 'response' => 400 ) );
        }
        $post_id = absint( $_GET['post_id'] );
        if ( ! wp_verify_nonce( wp_unslash( $_GET['nonce'] ), 'hqp_download_' . $format . '_' . $post_id ) ) {
            wp_die( 'Enlace caducado o inválido. Recarga la página del hotel y vuelve a descargar.', '', array( 'response' => 403 ) );
        }
        if ( ! $post_id || 'hotel_partner' !== get_post_type( $post_id ) ) {
            wp_die( 'Hotel no encontrado.', '', array( 'response' => 404 ) );
        }

        $access = '\MeTransfers\HotelPortal\Access\HotelAccess';
        if ( class_exists( $access ) && $access::isBlocked() ) {
            wp_die( 'Permisos insuficientes.', '', array( 'response' => 403 ) );
        }
        $allowed = current_user_can( 'edit_post', $post_id );
        if ( ! $allowed && class_exists( $access ) ) {
            $allowed = current_user_can( \MeTransfers\Admin\Capabilities::HOTEL_VIEW_PROFILE )
                && $access::canEnterPortal() && $access::canAccessHotel( $post_id );
        }
        if ( ! $allowed ) {
            wp_die( 'Permisos insuficientes.', '', array( 'response' => 403 ) );
        }
        return $post_id;
    }

    private static function valid_png( $image, $qr = true ) {
        if ( ! is_string( $image ) || strlen( $image ) > ( $qr ? 2 : 32 ) * MB_IN_BYTES || substr( $image, 0, 8 ) !== "\x89PNG\r\n\x1a\n" ) {
            return false;
        }
        $info = @getimagesizefromstring( $image );
        if ( ! $info || IMAGETYPE_PNG !== $info[2] || $info[0] <= 0 || $info[1] <= 0
            || ( $qr && ( $info[0] < 500 || $info[0] > 2000 || $info[0] !== $info[1] ) ) ) {
            return false;
        }
        // Reading dimensions alone also accepts an incomplete IHDR-only file.
        // Verify every chunk and the decoded scanlines before caching or sending.
        $offset = 8;
        $length = strlen( $image );
        $compressed = '';
        $header = null;
        $palette = false;
        while ( $offset + 12 <= $length ) {
            $size = unpack( 'N', substr( $image, $offset, 4 ) )[1];
            if ( $size > $length - $offset - 12 ) {
                return false;
            }
            $type = substr( $image, $offset + 4, 4 );
            $data = substr( $image, $offset + 8, $size );
            if ( hash( 'crc32b', $type . $data, true ) !== substr( $image, $offset + 8 + $size, 4 ) ) {
                return false;
            }
            if ( 8 === $offset && 'IHDR' !== $type ) {
                return false;
            }
            if ( 'IHDR' === $type ) {
                if ( null !== $header || 13 !== $size ) {
                    return false;
                }
                $header = unpack( 'Nwidth/Nheight/Cdepth/Ccolor/Ccompression/Cfilter/Cinterlace', $data );
                $channels = array( 0 => 1, 2 => 3, 3 => 1, 4 => 2, 6 => 4 );
                if ( ! isset( $channels[$header['color']] ) || ! in_array( $header['depth'], array( 1, 2, 4, 8 ), true )
                    || ( in_array( $header['color'], array( 2, 4, 6 ), true ) && 8 !== $header['depth'] )
                    || $header['compression'] || $header['filter'] || $header['interlace'] ) {
                    return false;
                }
            } elseif ( 'PLTE' === $type ) {
                $palette = $size > 0 && $size <= 768 && 0 === $size % 3;
            } elseif ( 'IDAT' === $type ) {
                $compressed .= $data;
            } elseif ( 'IEND' === $type ) {
                if ( 0 !== $size || $offset + 12 !== $length || '' === $compressed || ( 3 === $header['color'] && ! $palette ) ) {
                    return false;
                }
                if ( ! $qr ) {
                    // FPDF decodes the print background. Avoid retaining another
                    // uncompressed copy of a full-resolution A4 image here.
                    return true;
                }
                $row_length = 1 + (int) ceil( $info[0] * $channels[$header['color']] * $header['depth'] / 8 );
                $expected = $row_length * $info[1];
                $pixels = @gzuncompress( $compressed, $expected );
                if ( false === $pixels || strlen( $pixels ) !== $expected ) {
                    return false;
                }
                for ( $row = 0; $row < $info[1]; $row++ ) {
                    if ( ord( $pixels[$row * $row_length] ) > 4 ) {
                        return false;
                    }
                }
                return true;
            }
            $offset += 12 + $size;
        }
        return false;
    }

    private static function qr_image( $url ) {
        // One high-resolution QR is reused by the preview, PNG and PDF. The URL
        // is part of the key so changing the token or domain cannot reuse an old QR.
        $key = 'hqp_qr_v2_' . md5( $url );
        $stored = get_transient( $key );
        $cached = is_string( $stored ) ? base64_decode( $stored, true ) : false;
        if ( self::valid_png( $cached ) ) {
            return $cached;
        }
        $api = add_query_arg( array(
            'size' => '1000x1000', 'format' => 'png', 'ecc' => 'M',
            'qzone' => 4, 'data' => rawurlencode( $url ),
        ), 'https://api.qrserver.com/v1/create-qr-code/' );
        $response = wp_remote_get( $api, array( 'timeout' => 20, 'limit_response_size' => 2 * MB_IN_BYTES ) );
        if ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
            $image = wp_remote_retrieve_body( $response );
            // Validate the bytes, since valid PNG responses may use a generic MIME type.
            if ( self::valid_png( $image ) ) {
                // wp_options stores text: raw PNG bytes are not valid UTF-8.
                set_transient( $key, base64_encode( $image ), 30 * DAY_IN_SECONDS );
                return $image;
            }
        }
        return new WP_Error( 'hqp_qr_unavailable', 'No se pudo obtener el código QR. Vuelve a intentarlo en unos minutos.' );
    }

    private static function background() {
        $name = 'HABLADOR - METRANSFERS.png';
        // Prefer the design shipped with the theme over legacy plugin assets.
        $paths = array( dirname( __DIR__ ) . '/assets/' . $name );
        $paths[] = dirname( __DIR__ ) . '/' . $name;
        $paths[] = get_stylesheet_directory() . '/assets/img/' . $name;
        $paths[] = get_template_directory() . '/assets/img/' . $name;
        if ( defined( 'CBP_PLUGIN_DIR' ) ) {
            $paths[] = rtrim( CBP_PLUGIN_DIR, '/\\' ) . '/' . $name;
        }
        foreach ( array_unique( $paths ) as $path ) {
            if ( is_readable( $path ) && is_file( $path ) && filesize( $path ) <= 32 * MB_IN_BYTES
                && self::valid_png( @file_get_contents( $path ), false ) ) {
                return $path;
            }
        }
        return '';
    }

    private static function flyer( $post_id, $image ) {
        $tmp = wp_tempnam( 'hqp_qr_' . $post_id . '.png' );
        if ( ! $tmp ) {
            return new WP_Error( 'hqp_temp', 'No se pudo preparar temporalmente el código QR.' );
        }
        try {
            if ( file_put_contents( $tmp, $image ) !== strlen( $image ) ) {
                throw new RuntimeException( 'Could not write QR image.' );
            }
            if ( ! class_exists( 'FPDF' ) ) {
                require_once __DIR__ . '/fpdf.php';
            }
            $pdf = self::new_pdf();
            $background = self::background();
            if ( $background ) {
                try {
                    $pdf->Image( $background, 0, 0, 210, 297, 'PNG' );
                } catch ( Throwable $error ) {
                    // A damaged or unsupported template must not block downloads.
                    $background = '';
                    $pdf = self::new_pdf();
                }
            }
            if ( $background ) {
                /*
                 * QR placement for the bundled MeTransfers hotel artwork.
                 * Reference canvas: 2480 x 3508 px. The visible gold placeholder
                 * is approximately x=1004..1474 / y=2668..3098. A 400 px square
                 * at x=1040, y=2683 stays fully inside that frame and is centred
                 * at approximately (1240, 2883).
                 *
                 * FPDF stretches the full artwork to A4, so convert the reference
                 * coordinates to page millimetres. Use the smaller axis scale for
                 * the QR side so it remains perfectly square.
                 */
                $reference_width  = 2480.0;
                $reference_height = 3508.0;
                $page_width       = 210.0;
                $page_height      = 297.0;
                $scale_x          = $page_width / $reference_width;
                $scale_y          = $page_height / $reference_height;

                $qr_x_px    = 1014.0;
                $qr_y_px    = 2652.0;
                $qr_size_px = 450.0;

                $defaults = array(
                    'x'    => $qr_x_px * $scale_x,
                    'y'    => $qr_y_px * $scale_y,
                    'size' => $qr_size_px * min( $scale_x, $scale_y ),
                );
                $rect = apply_filters( 'hqp_flyer_qr_rect', $defaults );
            } else {
                self::default_flyer( $pdf, $post_id );
                // The custom-template coordinates do not apply to this layout.
                $defaults = array( 'x' => 65, 'y' => 125, 'size' => 80 );
                $rect = $defaults;
            }
            $x = isset( $rect['x'] ) ? (float) $rect['x'] : $defaults['x'];
            $y = isset( $rect['y'] ) ? (float) $rect['y'] : $defaults['y'];
            $size = isset( $rect['size'] ) ? (float) $rect['size'] : $defaults['size'];
            if ( ! is_finite( $x ) || ! is_finite( $y ) || ! is_finite( $size ) || $size <= 0 || $x < 0 || $y < 0 || $x + $size > 210 || $y + $size > 297 ) {
                throw new RuntimeException( 'Invalid flyer QR coordinates.' );
            }
            $pdf->Image( $tmp, $x, $y, $size, $size, 'PNG' );
            return $pdf->Output( 'S' );
        } catch ( Throwable $error ) {
            return new WP_Error( 'hqp_pdf', 'No se pudo generar el PDF. Comprueba la plantilla del hablador y la carpeta temporal del servidor.' );
        } finally {
            wp_delete_file( $tmp );
        }
    }

    private static function new_pdf() {
        $pdf = new FPDF( 'P', 'mm', 'A4' );
        $pdf->SetAutoPageBreak( false );
        $pdf->AddPage();
        return $pdf;
    }

    private static function pdf_text( $text ) {
        $text = html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES, 'UTF-8' );
        if ( function_exists( 'iconv' ) ) {
            $encoded = iconv( 'UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text );
            if ( false !== $encoded ) {
                return $encoded;
            }
        }
        return preg_replace( '/[^\x20-\x7E]/', '', remove_accents( $text ) );
    }

    private static function default_flyer( $pdf, $post_id ) {
        // Bundle the metrics with the existing FPDF library: no background image
        // or server-installed fonts are required for the default printable A4.
        $pdf->AddFont( 'Helvetica', '', 'helvetica.php', __DIR__ . '/font/' );
        $pdf->AddFont( 'Helvetica', 'B', 'helveticab.php', __DIR__ . '/font/' );
        $pdf->SetFillColor( 17, 35, 52 );
        $pdf->Rect( 0, 0, 210, 49, 'F' );
        $pdf->SetFillColor( 207, 166, 84 );
        $pdf->Rect( 0, 49, 210, 2, 'F' );
        $pdf->SetTextColor( 255, 255, 255 );
        $pdf->SetFont( 'Helvetica', 'B', 30 );
        $pdf->SetXY( 15, 15 );
        $pdf->Cell( 180, 14, 'METRANSFERS', 0, 1, 'C' );
        $pdf->SetFont( 'Helvetica', '', 11 );
        $pdf->Cell( 190, 8, 'PRIVATE TRANSFERS  /  TRASLADOS PRIVADOS', 0, 1, 'C' );
        $pdf->SetTextColor( 17, 35, 52 );
        $pdf->SetFont( 'Helvetica', 'B', 16 );
        $pdf->SetXY( 20, 64 );
        $pdf->MultiCell( 170, 8, self::pdf_text( wp_html_excerpt( get_the_title( $post_id ), 100, '...' ) ), 0, 'C' );
        $pdf->SetFont( 'Helvetica', 'B', 25 );
        $pdf->SetXY( 15, 93 );
        $pdf->Cell( 180, 12, 'Reserva tu traslado', 0, 1, 'C' );
        $pdf->SetFont( 'Helvetica', '', 14 );
        $pdf->Cell( 190, 9, 'Book your transfer', 0, 1, 'C' );
        $pdf->SetFont( 'Helvetica', 'B', 15 );
        $pdf->SetXY( 15, 218 );
        $pdf->Cell( 180, 10, self::pdf_text( 'Escanea el QR con la cámara de tu móvil' ), 0, 1, 'C' );
        $pdf->SetFont( 'Helvetica', '', 12 );
        $pdf->Cell( 190, 8, 'Scan the QR code with your phone camera', 0, 1, 'C' );
        $pdf->SetDrawColor( 207, 166, 84 );
        $pdf->Line( 30, 253, 180, 253 );
        $pdf->SetXY( 20, 262 );
        $pdf->Cell( 170, 8, self::pdf_text( wp_parse_url( home_url(), PHP_URL_HOST ) ), 0, 1, 'C' );
    }

    private static function send( $body, $type, $filename, $inline ) {
        // Discard buffered notices/markup before sending binary files.
        while ( ob_get_level() > 0 ) {
            if ( ! @ob_end_clean() ) {
                break;
            }
        }
        if ( ob_get_level() > 0 || headers_sent() ) {
            wp_die( 'La descarga no pudo iniciarse porque el servidor ya envió contenido. Recarga la página e inténtalo de nuevo.' );
        }
        status_header( 200 );
        nocache_headers();
        header( 'Content-Type: ' . $type );
        header( 'X-Content-Type-Options: nosniff' );
        header( 'Content-Disposition: ' . ( $inline ? 'inline' : 'attachment' ) . '; filename="' . $filename . '"' );
        // Let PHP/the web server handle length when output compression is enabled.
        echo $body;
        exit;
    }
}
