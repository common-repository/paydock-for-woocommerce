<?php

namespace Paydock\Enums;

use Paydock\Abstracts\AbstractEnum;

class TypeExchangeOTT extends AbstractEnum {
	protected const PERMANENT_VAULT = 'With vault';
	protected const SESSION_VAULT = 'With OTT';

	public static function toArray(): array {
		$result = [];
		foreach ( self::cases() as $type ) {
			$result[ $type->name ] = $type->value;
		}

		return $result;
	}
}
