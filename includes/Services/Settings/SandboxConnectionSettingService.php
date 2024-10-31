<?php

namespace Paydock\Services\Settings;

use Paydock\Enums\CredentialSettings;
use Paydock\Enums\SettingGroups;
use Paydock\Enums\SettingsTabs;
use Paydock\PaydockPlugin;
use Paydock\Services\SettingsService;

class SandboxConnectionSettingService extends LiveConnectionSettingService {
	public function init_form_fields(): void {
		$sandBoxOptionKey = SettingsService::getInstance()
		                                   ->getOptionName( $this->id, [
			                                   SettingGroups::CREDENTIALS()->name,
			                                   CredentialSettings::SANDBOX()->name
		                                   ] );

		$this->form_fields[ $sandBoxOptionKey ] = [
			'type' => CredentialSettings::SANDBOX()->getInputType(),
			'label' => __(
				'To test your Paydock for WooCommerce Plugin, you can use the sandbox mode.',
				'paydock'
			),
			'title' => CredentialSettings::SANDBOX()->getLabel(),
		];
		parent::init_form_fields();
	}

	protected function getId(): string {
		return SettingsTabs::SANDBOX_CONNECTION()->value;
	}
}
