<?php

namespace Paydock\Services\Assets;

use Paydock\PaydockPlugin;

class AdminAssetsService {
	private const SCRIPTS = [
		'tabs',
		'connections',
		'card-select',
		'deactivation-confirmation',
		'admin-helpers'
	];
	private const STYLES = [
		'card-select',
	];

	private const PREFIX = 'admin';

	private const SCRIPT_PREFIX = 'script';
	private const STYLE_PREFIX = 'style';

	private const URL_SCRIPT_PREFIX = 'assets/js/admin/';
	private const URL_SCRIPT_POSTFIX = '.js?27012024';

	private const URL_STYLE_PREFIX = 'assets/css/admin/';
	private const URL_STYLE_POSTFIX = '.css';

	public function __construct() {
		$this->registerScripts();
		$this->loadScripts();
		$this->addStyles();
	}

	public function registerScripts(): void {
		foreach ( self::SCRIPTS as $script ) {
			wp_register_script(
				$this->getScriptName( $script ),
				plugins_url( $this->getScriptPath( $script ), PAYDOCK_PLUGIN_FILE ),
				[],
				PAYDOCK_PLUGIN_VERSION,
				true
			);
		}
	}

	private function getScriptName( string $script ): string {
		return implode( '_', [ PaydockPlugin::PLUGIN_PREFIX, self::PREFIX, self::SCRIPT_PREFIX, $script ] );
	}

	private function getScriptPath( string $script ): string {
		return self::URL_SCRIPT_PREFIX . $script . self::URL_SCRIPT_POSTFIX;
	}

	public function loadScripts(): void {
		foreach ( self::SCRIPTS as $script ) {
			wp_enqueue_script( $this->getScriptName( $script ), '', [], PAYDOCK_PLUGIN_VERSION, true );
		}
	}

	private function addStyles(): void {
		foreach ( self::STYLES as $style ) {
			wp_enqueue_style(
				$this->getStyleName( $style ),
				plugins_url( $this->getStylePath( $style ), PAYDOCK_PLUGIN_FILE ),
				[],
				PAYDOCK_PLUGIN_VERSION
			);
		}
	}

	private function getStyleName( string $script ): string {
		return implode( '_', [ PaydockPlugin::PLUGIN_PREFIX, self::PREFIX, self::STYLE_PREFIX, $script ] );
	}

	private function getStylePath( string $script ): string {
		return self::URL_STYLE_PREFIX . $script . self::URL_STYLE_POSTFIX;
	}

}
