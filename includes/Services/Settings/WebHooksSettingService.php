<?php

namespace Paydock\Services\Settings;

use Paydock\Abstracts\AbstractSettingService;
use Paydock\Enums\SettingsTabs;

class WebHooksSettingService extends AbstractSettingService {
	protected function getId(): string {
		return SettingsTabs::WEBHOOKS()->value;
	}
}
