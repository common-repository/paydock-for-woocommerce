<?php

namespace Paydock\Enums;

use Paydock\Abstracts\AbstractEnum;
use Paydock\PaydockPlugin;

class OrderListColumns extends AbstractEnum {
	public const AFTER_COLUMN = 'order_status';
	protected const PAYMENT_SOURCE_TYPE = 'PAYMENT_SOURCE_TYPE';

	public function getLabel(): string {
		switch ( $this->name ) {
			case self::PAYMENT_SOURCE_TYPE:
				return 'Paydock Payment Type';
			default:
				return '';
		}
	}

	public function getKey(): string {
		switch ( $this->name ) {
			case self::PAYMENT_SOURCE_TYPE:
				return PaydockPlugin::PLUGIN_PREFIX . '_payment_source_type';
			default:
				return '';
		}
	}
}