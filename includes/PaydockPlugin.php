<?php

namespace Paydock;

use Paydock\Abstracts\AbstractSingleton;
use Paydock\Hooks\ActivationHook;
use Paydock\Hooks\DeactivationHook;
use Paydock\Repositories\LogRepository;
use Paydock\Services\ActionsService;
use Paydock\Services\FiltersService;

if ( ! class_exists( '\Paydock\PaydockPlugin' ) ) {
	final class PaydockPlugin extends AbstractSingleton {
		public const REPOSITORIES = [ 
			LogRepository::class,
		];

		public const PLUGIN_PREFIX = 'paydock';

		public const VERSION = '1.0.0';

		protected static $instance = null;

		protected $paymentService = null;

		protected function __construct() {
			register_activation_hook( PAYDOCK_PLUGIN_FILE, [ ActivationHook::class, 'handle' ] );
			register_deactivation_hook( PAYDOCK_PLUGIN_FILE, [ DeactivationHook::class, 'handle' ] );

			ActionsService::getInstance();
			FiltersService::getInstance();
		}
	}
}
