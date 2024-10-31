<?php

namespace Paydock\Hooks;

use Paydock\Contracts\Hook;
use Paydock\Contracts\Repository;
use Paydock\PaydockPlugin;
use Paydock\Plugin;

class DeactivationHook implements Hook {

	public function __construct() {
	}

	public static function handle(): void {
		$instance = new self();

		$repositories = array_map( function ($className) {
			return new $className();
		}, PaydockPlugin::REPOSITORIES );

		array_map( [ $instance, 'runMigration' ], $repositories );
	}

	protected function runMigration( Repository $repository ): void {
		$repository->dropTable();
	}
}
