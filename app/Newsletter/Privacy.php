<?php
namespace MeTransfers\Newsletter;

/**
 * Tools → Export / Erase Personal Data include the newsletter (GDPR access
 * and erasure).
 */
final class Privacy {
	public function register() {
		add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'exporters' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( __CLASS__, 'erasers' ) );
	}

	public static function exporters( $exporters ) {
		$exporters['mt-newsletter'] = array(
			'exporter_friendly_name' => 'Newsletter de MeTransfers',
			'callback'               => array( __CLASS__, 'export' ),
		);
		return $exporters;
	}

	public static function erasers( $erasers ) {
		$erasers['mt-newsletter'] = array(
			'eraser_friendly_name' => 'Newsletter de MeTransfers',
			'callback'             => array( __CLASS__, 'erase' ),
		);
		return $erasers;
	}

	/**
	 * @return array{data: array<int, array>, done: bool}
	 */
	public static function export( $email, $page = 1 ) {
		unset( $page );
		$subscriber = Subscribers::findByEmail( $email );
		if ( ! $subscriber ) {
			return array(
				'data' => array(),
				'done' => true,
			);
		}
		$fields = array(
			'Correo'             => $subscriber['email'],
			'Nombre'             => $subscriber['name'],
			'Estado'             => Subscribers::STATUSES[ $subscriber['status'] ] ?? $subscriber['status'],
			'Idioma'             => $subscriber['language'],
			'Página de registro' => $subscriber['signup_page'],
			'Llegó desde'        => $subscriber['referrer'],
			'Páginas visitadas'  => implode( ', ', array_column( Subscribers::pages( $subscriber ), 'u' ) ),
			'Consentimiento'     => $subscriber['consent_text'] . ' (' . $subscriber['consent_at'] . ' UTC)',
			'Confirmado'         => (string) $subscriber['confirmed_at'],
			'Baja'               => (string) $subscriber['unsubscribed_at'],
			'Alta'               => $subscriber['created_at'],
		);
		$data   = array();
		foreach ( $fields as $name => $value ) {
			$data[] = array(
				'name'  => $name,
				'value' => (string) $value,
			);
		}
		return array(
			'data' => array(
				array(
					'group_id'    => 'mt-newsletter',
					'group_label' => 'Newsletter',
					'item_id'     => 'mt-newsletter-' . (int) $subscriber['id'],
					'data'        => $data,
				),
			),
			'done' => true,
		);
	}

	/**
	 * @return array{items_removed: bool, items_retained: bool, messages: array<int, string>, done: bool}
	 */
	public static function erase( $email, $page = 1 ) {
		unset( $page );
		$subscriber = Subscribers::findByEmail( $email );
		return array(
			'items_removed'  => $subscriber ? Subscribers::delete( array( (int) $subscriber['id'] ) ) > 0 : false,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
	}
}
