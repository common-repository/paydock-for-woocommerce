<?php

namespace Paydock\Enums;

use Paydock\Abstracts\AbstractEnum;

class DSTypes extends AbstractEnum {
	protected const DISABLE = 'Disable';
	protected const STANDALONE = 'Standalone 3DS';
	protected const IN_BUILD = 'In-built 3DS';

	public static function toArray(): array {
		$result = [];

		foreach ( self::cases() as $type ) {
			$result[ $type->name ] = $type->value;
		}

		return $result;
	}
}
