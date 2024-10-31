<?php

namespace Paydock\Helpers;

class ShippingHelper {
	public static function getPickupLocationByName( string $name ) {
		$options = get_option( 'pickup_location_pickup_locations' );

		foreach ( $options as $option ) {
			if ( $option['name'] === $name ) {
				return $option;
			}
		}

		return false;
	}

	public static function getPickupLocationByKey( string $key ) {
		if ( strpos( $key, ':' ) !== false ) {
			$key = explode( ':', $key )[1];
		}

		$options = get_option( 'pickup_location_pickup_locations' );

		if ( ! empty( $options[ $key ] ) ) {
			return $options[ $key ];
		}

		return false;
	}
}
