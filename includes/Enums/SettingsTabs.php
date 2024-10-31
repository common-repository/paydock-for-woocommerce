<?php

namespace Paydock\Enums;

use Paydock\Abstracts\AbstractEnum;
use Paydock\PaydockPlugin;
use Paydock\Services\Settings\LiveConnectionSettingService;
use Paydock\Services\Settings\LogsSettingService;
use Paydock\Services\Settings\SandboxConnectionSettingService;
use Paydock\Services\Settings\WebHooksSettingService;
use Paydock\Services\Settings\WidgetSettingService;

class SettingsTabs extends AbstractEnum {
	protected const LIVE_CONNECTION = PaydockPlugin::PLUGIN_PREFIX;
	protected const SANDBOX_CONNECTION = PaydockPlugin::PLUGIN_PREFIX . '_sandbox';
	protected const WEBHOOKS = PaydockPlugin::PLUGIN_PREFIX . '_webhooks';
	protected const WIDGET = PaydockPlugin::PLUGIN_PREFIX . '_widget';
	protected const LOG = PaydockPlugin::PLUGIN_PREFIX . '_log';

	public static function secondary(): array {
		$allTabs = self::allCases(); // Use a custom method to simulate enum cases.

		return array_filter( $allTabs, function ($tab) {
			return self::LIVE_CONNECTION !== $tab;
		} );
	}

	public static function allCases(): array {
		$RefClass = new \ReflectionClass( static::class);

		return array_map( function (string $name) {
			return static::{$name}();
		}, array_keys( $RefClass->getConstants() ) );
	}

	public function getSettingService() {
		switch ( $this->value ) {
			case self::LIVE_CONNECTION:
				return new LiveConnectionSettingService();
			case self::SANDBOX_CONNECTION:
				return new SandboxConnectionSettingService();
			case self::WEBHOOKS:
				return new WebHooksSettingService();
			case self::WIDGET:
				return new WidgetSettingService();
			case self::LOG:
				return new LogsSettingService();
			default:
				return null;
		}
	}
}
