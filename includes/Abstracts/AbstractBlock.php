<?php

namespace Paydock\Abstracts;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Paydock\PaydockPlugin;
use Paydock\Services\SettingsService;

abstract class AbstractBlock extends AbstractPaymentMethodType {
	private static $isLoad = false;
	protected $script;

	public function __construct() {
		if ( defined( static::class . '::SCRIPT' ) ) {
			$this->script = static::SCRIPT;
		}
	}

	public function is_active() {
		return $this->gateway->is_available();
	}

	public function get_payment_method_script_handles() {
		if ( ! self::$isLoad && is_checkout() ) {
			wp_enqueue_script(
				'paydock-form',
				PAYDOCK_PLUGIN_URL . '/assets/js/frontend/form.js',
				[],
				PAYDOCK_PLUGIN_VERSION,
				true
			);
			wp_enqueue_style(
				'paydock-widget-css',
				PAYDOCK_PLUGIN_URL . '/assets/css/frontend/widget.css',
				[],
				PAYDOCK_PLUGIN_VERSION,
				true
			);

			wp_enqueue_script(
				'paydock-api',
				SettingsService::getInstance()->getWidgetScriptUrl(),
				[],
				PAYDOCK_PLUGIN_VERSION,
				true
			);

			self::$isLoad = true;
		}

		$scriptPath      = 'assets/build/js/frontend/' . $this->script . '.js';
		$scriptAssetPath = 'assets/build/js/frontend/' . $this->script . '.asset.php';
		$scriptUrl       = plugins_url( $scriptPath, PAYDOCK_PLUGIN_FILE );
		$scriptName      = PaydockPlugin::PLUGIN_PREFIX . '-' . $this->script;

		$scriptAsset = file_exists( $scriptAssetPath ) ? require( $scriptAssetPath ) : [
			'dependencies' => [],
			'version'      => PAYDOCK_PLUGIN_VERSION,
		];
		wp_register_script( $scriptName, $scriptUrl, $scriptAsset['dependencies'], $scriptAsset['version'], true );

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( $scriptName );
		}

		return [ $scriptName ];
	}
}
