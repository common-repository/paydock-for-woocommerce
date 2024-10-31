<?php

namespace Paydock\Abstracts;

abstract class AbstractSingleton {
	abstract protected function __construct();

	public static function getInstance() {
		if ( is_null( static::$instance ) ) {
			static::$instance = new static();
		}

		return static::$instance;
	}


	/**
	 * Cloning instances is forbidden due to singleton pattern.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		wc_doing_it_wrong(
			__FUNCTION__,
			sprintf( 'You cannot clone instances of %s.', get_class( $this ) ),
			'1.10.0'
		);
	}


	/**
	 * Unserializing instances is forbidden due to singleton pattern.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		wc_doing_it_wrong(
			__FUNCTION__,
			sprintf( 'You cannot unserialize instances of %s.', get_class( $this ) ),
			'1.10.0'
		);
	}
}
