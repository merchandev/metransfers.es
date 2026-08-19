<?php
namespace MeTransfers\Core;

final class ReleaseGate {
	public static function redsysLiveReady() {
		foreach ( self::requirements() as $setting => $label ) {
			if ( ! self::validAttestation( Settings::get( $setting, '' ) ) ) {
				return false;
			}
		}
		return true;
	}

	public static function missingRequirements() {
		$missing = array();
		foreach ( self::requirements() as $setting => $label ) {
			if ( ! self::validAttestation( Settings::get( $setting, '' ) ) ) {
				$missing[] = $label;
			}
		}
		return $missing;
	}

	private static function requirements() {
		return array(
			'redsys_credentials_rotated_at' => 'Redsys credentials rotation',
			'smtp_credentials_rotated_at'   => 'SMTP credentials rotation',
			'maps_credentials_rotated_at'   => 'Google Maps key rotation/restriction',
			'redsys_sandbox_verified_at'    => 'Redsys Sandbox end-to-end verification',
		);
	}

	private static function validAttestation( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return false;
		}
		$timestamp = strtotime( $value );
		return false !== $timestamp && $timestamp <= time();
	}
}
