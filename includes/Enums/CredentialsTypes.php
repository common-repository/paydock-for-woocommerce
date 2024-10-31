<?php

namespace Paydock\Enums;

use Paydock\Abstracts\AbstractEnum;

class CredentialsTypes extends AbstractEnum {
	protected const CREDENTIALS = 'Public & Secret Keys';
	protected const ACCESS_KEY = 'Access Token';

	public static function toArray(): array {
		$result = [];

		foreach ( self::cases() as $type ) {
			$result[ $type->name ] = $type->value;
		}

		return $result;
	}
}
