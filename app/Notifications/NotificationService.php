<?php
namespace MeTransfers\Notifications;

use MeTransfers\Booking\I18n;
use MeTransfers\Core\Settings;
use MeTransfers\Pricing\Money;

final class NotificationService {
    public static function bookingPending( $booking_id, $booking ) {
        return self::dispatch( $booking_id, $booking, 'pending' );
    }

    public static function bookingConfirmed( $booking_id, $booking ) {
        return self::dispatch( $booking_id, $booking, 'confirmed' );
    }

    public static function sendWhatsapp( $booking_id, $booking ) {
        $api_key = (string) get_option( 'wptb_whatsapp_apikey', '' );
        if ( '' === $api_key ) {
            return true;
        }
        $phone = preg_replace( '/\s+/', '', (string) get_option( 'wptb_admin_phone_notifications', '' ) );
        if ( '' === $phone ) {
            return false;
        }

        $vehicle = \WPTB_Vehicle_Manager::get_vehicle( $booking->vehicle_id );
        $vehicle_name = $vehicle ? $vehicle->name : 'Vehículo #' . (int) $booking->vehicle_id;
        $text = "*Nueva reserva pagada #" . (int) $booking_id . "*\n";
        $text .= "👤 " . (string) $booking->customer_name . "\n";
        $text .= "🚘 " . $vehicle_name . "\n";
        $text .= "📍 " . (string) $booking->origin . "\n⬇️\n📍 " . (string) $booking->destination . "\n";
        $text .= "📅 " . (string) $booking->booking_date . ' ' . (string) $booking->booking_time . "\n";
        $text .= "💶 €" . Money::fromBooking( $booking )->decimal() . "\n";
        $text .= "📞 " . (string) $booking->customer_phone;

        $response = wp_remote_get(
            add_query_arg(
                array(
                    'phone'  => $phone,
                    'text'   => $text,
                    'apikey' => $api_key,
                ),
                'https://api.callmebot.com/whatsapp.php'
            ),
            array( 'timeout' => 5 )
        );

        if ( is_wp_error( $response ) ) {
            return false;
        }
        $status_code = wp_remote_retrieve_response_code( $response );
        return $status_code >= 200 && $status_code < 300;
    }

    public static function sendEmails( $booking_id, $booking, $status ) {
        $errors = array();
        if ( ! self::sendCustomerEmail( $booking_id, $booking, $status ) ) {
            $errors[] = 'customer_email';
        }
        if ( ! self::sendAdminEmail( $booking_id, $booking, $status ) ) {
            $errors[] = 'admin_email';
        }
        if ( ! self::sendHotelEmail( $booking_id, $booking, $status ) ) {
            $errors[] = 'hotel_email';
        }
        return empty( $errors ) ? true : implode( ',', $errors );
    }

    public static function sendCustomerEmail( $booking_id, $booking, $status ) {
        if ( ! is_email( $booking->customer_email ) ) {
            return false;
        }
        $locale = I18n::normalizeLanguage( isset( $booking->booking_locale ) ? $booking->booking_locale : 'es' );
        $title = I18n::text( 'pending' === $status ? 'notification_pending_title' : 'notification_confirmed_title', $locale );
        return self::mail(
            $booking->customer_email,
            $title . ' #' . (int) $booking_id . ' - MeTransfers',
            self::message( $booking_id, $booking, $status, $locale )
        );
    }

    public static function sendAdminEmail( $booking_id, $booking, $status ) {
        $recipients = self::adminRecipients();
        if ( empty( $recipients ) ) {
            return false;
        }
        $title = 'pending' === $status ? 'Nueva reserva pendiente' : 'Reserva pagada';
        $message = self::message( $booking_id, $booking, $status, 'es' );
        $success = true;
        foreach ( $recipients as $recipient ) {
            if ( ! self::mail( $recipient, $title . ' #' . (int) $booking_id, $message ) ) {
                $success = false;
            }
        }
        return $success;
    }

    public static function sendHotelEmail( $booking_id, $booking, $status ) {
        if ( empty( $booking->hotel_token ) ) {
            return true;
        }
        $query = new \WP_Query( array(
            'post_type'      => 'hotel_partner',
            'meta_key'       => '_hqp_token',
            'meta_value'     => $booking->hotel_token,
            'posts_per_page' => 1,
            'fields'         => 'ids',
        ) );
        if ( empty( $query->posts[0] ) ) {
            return false;
        }
        $email = get_post_meta( $query->posts[0], '_hqp_contact_email', true );
        if ( ! is_email( $email ) ) {
            return false;
        }
        $title = 'pending' === $status ? 'Nueva reserva pendiente desde tu hotel' : 'Reserva pagada desde tu hotel';
        return self::mail(
            $email,
            $title . ' #' . (int) $booking_id,
            self::message( $booking_id, $booking, $status, 'es' )
        );
    }

    public static function configureSmtp( $mailer ) {
        $host = (string) Settings::get( 'smtp_host', '' );
        $user = (string) Settings::get( 'smtp_user', '' );
        $password = (string) Settings::get( 'smtp_password', '' );
        if ( '' === $host || '' === $user || '' === $password ) {
            return;
        }

        $mailer->isSMTP();
        $mailer->Host = $host;
        $mailer->SMTPAuth = true;
        $mailer->Port = (int) Settings::get( 'smtp_port', 465 );
        $mailer->Username = $user;
        $mailer->Password = $password;
        $mailer->SMTPSecure = (string) Settings::get( 'smtp_encryption', 'ssl' );
        $mailer->From = (string) Settings::get( 'smtp_from', $user );
        $mailer->FromName = (string) Settings::get( 'smtp_from_name', 'MeTransfers' );
    }

    private static function dispatch( $booking_id, $booking, $status ) {
        $email_result = self::sendEmails( $booking_id, $booking, $status );
        $errors = true === $email_result ? array() : explode( ',', (string) $email_result );

        if ( 'confirmed' === $status && ! self::sendWhatsapp( $booking_id, $booking ) ) {
            $errors[] = 'whatsapp';
        }

        do_action( 'mt_booking_notifications_dispatched', $booking_id, $status, $errors );
        return empty( $errors ) ? true : implode( ',', array_unique( $errors ) );
    }

    private static function mail( $recipient, $subject, $message ) {
        add_action( 'phpmailer_init', array( __CLASS__, 'configureSmtp' ), 9999 );
        try {
            return wp_mail( $recipient, $subject, $message, self::headers() );
        } finally {
            remove_action( 'phpmailer_init', array( __CLASS__, 'configureSmtp' ), 9999 );
        }
    }

    private static function message( $booking_id, $booking, $status, $locale ) {
        $t = static function ( $key ) use ( $locale ) {
            return I18n::text( $key, $locale );
        };
        $vehicle = \WPTB_Vehicle_Manager::get_vehicle( $booking->vehicle_id );
        $vehicle_name = $vehicle ? $vehicle->name : '#' . (int) $booking->vehicle_id;
        $trip = 'round_trip' === $booking->trip_type ? $t( 'round_trip' ) : $t( 'one_way' );
        $title = $t( 'pending' === $status ? 'notification_pending_title' : 'notification_confirmed_title' );
        $intro = $t( 'pending' === $status ? 'notification_pending_intro' : 'notification_confirmed_intro' );
        $rows = array(
            $t( 'date' ) . ' / ' . $t( 'time' ) => $booking->booking_date . ' ' . $booking->booking_time,
            $t( 'trip_type' ) => $trip,
            $t( 'origin' ) => $booking->origin,
            $t( 'destination' ) => $booking->destination,
            $t( 'vehicle' ) => $vehicle_name,
            $t( 'passengers' ) => (int) $booking->passengers,
            $t( 'luggage' ) => (int) $booking->suitcases . ' + ' . (int) $booking->carry_ons,
            $t( 'price' ) => '€' . Money::fromBooking( $booking )->decimal(),
        );
        if ( ! empty( $booking->flight_number ) ) {
            $rows[ $t( 'flight' ) ] = $booking->flight_number;
        }
        if ( ! empty( $booking->notes ) ) {
            $rows[ $t( 'notes' ) ] = $booking->notes;
        }

        $details = '';
        foreach ( $rows as $label => $value ) {
            $details .= '<tr><th align="left" style="padding:8px;border-bottom:1px solid #e7edf0;color:#405463">' . esc_html( $label ) . '</th><td align="right" style="padding:8px;border-bottom:1px solid #e7edf0">' . esc_html( (string) $value ) . '</td></tr>';
        }

        return '<!doctype html><html><body style="margin:0;background:#f4f7f8;font-family:Arial,sans-serif;color:#152934">'
            . '<table role="presentation" width="100%"><tr><td align="center" style="padding:24px"><table role="presentation" width="100%" style="max-width:620px;background:#fff;border-radius:16px;overflow:hidden">'
            . '<tr><td style="background:#004b68;color:#fff;padding:24px;text-align:center"><h1 style="margin:0;font-size:24px">' . esc_html( $title ) . '</h1><p style="margin:8px 0 0">' . esc_html( $t( 'reference' ) ) . ' #' . (int) $booking_id . '</p></td></tr>'
            . '<tr><td style="padding:28px"><p>' . esc_html( $t( 'hello' ) ) . ' <strong>' . esc_html( $booking->customer_name ) . '</strong>,</p><p>' . esc_html( $intro ) . '</p><table role="presentation" width="100%">' . $details . '</table>'
            . '<p style="text-align:center;margin:28px 0 8px"><a href="' . esc_url( home_url( '/' ) ) . '" style="display:inline-block;background:#ff7100;color:#fff;padding:12px 22px;border-radius:999px;text-decoration:none;font-weight:bold">' . esc_html( $t( 'visit_website' ) ) . '</a></p></td></tr>'
            . '<tr><td style="background:#edf2f4;padding:18px;text-align:center;color:#60727c;font-size:13px">&copy; ' . esc_html( gmdate( 'Y' ) ) . ' MeTransfers. ' . esc_html( $t( 'notification_help' ) ) . '</td></tr>'
            . '</table></td></tr></table></body></html>';
    }

    private static function headers() {
        $default = (string) get_option( 'admin_email', '' );
        $from = sanitize_email( (string) Settings::get( 'smtp_from', $default ) );
        $name = sanitize_text_field( (string) Settings::get( 'smtp_from_name', 'MeTransfers' ) );
        if ( ! is_email( $from ) ) {
            $from = sanitize_email( $default );
        }
        return array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $name . ' <' . $from . '>',
            'Reply-To: ' . $name . ' <' . $from . '>',
        );
    }

    private static function adminRecipients() {
        $recipients = array( get_option( 'admin_email', '' ), get_option( 'wptb_admin_email_notifications', '' ) );
        return array_values( array_unique( array_filter( $recipients, 'is_email' ) ) );
    }

}
