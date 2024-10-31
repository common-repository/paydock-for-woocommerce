<?php

namespace PayDock\Enums;

use Paydock\Abstracts\AbstractEnum;

class ConfigAPI extends AbstractEnum {
	protected const PRODUCTION_API_URL = 'https://api.paydock.com/v1/';
	protected const SANDBOX_API_URL = 'https://api-sandbox.paydock.com/v1/';
	protected const PRODUCTION_ENVIRONMENT = 'production';
	protected const SANDBOX_ENVIRONMENT = 'sandbox';
}
