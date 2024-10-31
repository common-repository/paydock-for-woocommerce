<?php

namespace Paydock\Hooks;

use Paydock\Contracts\Hook;
use Paydock\Contracts\Repository;
use Paydock\PaydockPlugin;

class ActivationHook implements Hook {

	public function __construct() {
	}

	public static function handle(): void {
		$instance = new self();

		$repositories = array_map( function ( $className ) {
			return new $className();
		}, PaydockPlugin::REPOSITORIES );

		array_map( [ $instance, 'runMigration' ], $repositories );

		$instance->renameConfiguration();
	}

	protected function runMigration( Repository $repository ): void {
		$repository->createTable();
	}

	protected function renameConfiguration() {
		$options = [
			'woocommerce_pay_dock_sandbox_settings' => 'woocommerce_paydock_sandbox_settings',
			'woocommerce_pay_dock_settings'         => 'woocommerce_paydock_settings',
			'woocommerce_pay_dock_widget_settings'  => 'woocommerce_paydock_widget_settings'
		];
		foreach ( $options as $oldOptionName => $newOptionName ) {
			$oldOption = get_option( $oldOptionName );
			if ( $oldOption ) {
				$newOption = [];
				foreach ( $oldOption as $key => $value ) {
					$newKey               = str_replace( 'pay_dock', 'paydock', $key );
					$newOption[ $newKey ] = $value;
				}
				add_option( $newOptionName, $newOption );
			}
		}
	}
}
