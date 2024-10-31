<?php

namespace Paydock\Enums;

use Paydock\Abstracts\AbstractEnum;

class FraudTypes extends AbstractEnum {
	protected const DISABLE = 'Disable';
	protected const STANDALONE = 'Standalone Fraud';
	protected const IN_BUILD = 'In-built Fraud';

	public static function toArray(): array {
		$result = [];

		foreach ( self::cases() as $type ) {
			$result[ $type->name ] = $type->value;
		}

		return $result;
	}
}
