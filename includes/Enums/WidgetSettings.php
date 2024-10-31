<?php

namespace Paydock\Enums;

use Paydock\Abstracts\AbstractEnum;

class WidgetSettings extends AbstractEnum {
	protected const VERSION = 'VERSION';
	protected const CUSTOM_VERSION = 'CUSTOM_VERSION';
	protected const PAYMENT_CARD_TITLE = 'PAYMENT_CARD_TITLE';
	protected const PAYMENT_CARD_DESCRIPTION = 'PAYMENT_CARD_DESCRIPTION';
	protected const PAYMENT_CARD_MIN = 'PAYMENT_CARD_MIN';
	protected const PAYMENT_CARD_MAX = 'PAYMENT_CARD_MAX';
	protected const PAYMENT_BANK_ACCOUNT_TITLE = 'PAYMENT_BANK_ACCOUNT_TITLE';
	protected const PAYMENT_BANK_ACCOUNT_DESCRIPTION = 'PAYMENT_BANK_ACCOUNT_DESCRIPTION';
	protected const PAYMENT_WALLET_APPLE_PAY_TITLE = 'PAYMENT_WALLET_APPLE_PAY_TITLE';
	protected const PAYMENT_WALLET_APPLE_PAY_DESCRIPTION = 'PAYMENT_WALLET_APPLE_PAY_DESCRIPTION';
	protected const PAYMENT_WALLET_APPLE_PAY_MIN = 'PAYMENT_WALLET_APPLE_PAY_MIN';
	protected const PAYMENT_WALLET_APPLE_PAY_MAX = 'PAYMENT_WALLET_APPLE_PAY_MAX';
	protected const PAYMENT_WALLET_GOOGLE_PAY_TITLE = 'PAYMENT_WALLET_GOOGLE_PAY_TITLE';
	protected const PAYMENT_WALLET_GOOGLE_PAY_DESCRIPTION = 'PAYMENT_WALLET_GOOGLE_PAY_DESCRIPTION';
	protected const PAYMENT_WALLET_GOOGLE_PAY_MIN = 'PAYMENT_WALLET_GOOGLE_PAY_MIN';
	protected const PAYMENT_WALLET_GOOGLE_PAY_MAX = 'PAYMENT_WALLET_GOOGLE_PAY_MAX';
	protected const PAYMENT_WALLET_AFTERPAY_V2_TITLE = 'PAYMENT_WALLET_AFTERPAY_V2_TITLE';
	protected const PAYMENT_WALLET_AFTERPAY_V2_DESCRIPTION = 'PAYMENT_WALLET_AFTERPAY_V2_DESCRIPTION';
	protected const PAYMENT_WALLET_AFTERPAY_V2_MIN = 'PAYMENT_WALLET_AFTERPAY_V2_MIN';
	protected const PAYMENT_WALLET_AFTERPAY_V2_MAX = 'PAYMENT_WALLET_AFTERPAY_V2_MAX';
	protected const PAYMENT_WALLET_PAYPAL_TITLE = 'PAYMENT_WALLET_PAYPAL_TITLE';
	protected const PAYMENT_WALLET_PAYPAL_DESCRIPTION = 'PAYMENT_WALLET_PAYPAL_DESCRIPTION';
	protected const PAYMENT_WALLET_PAYPAL_MIN = 'PAYMENT_WALLET_PAYPAL_MIN';
	protected const PAYMENT_WALLET_PAYPAL_MAX = 'PAYMENT_WALLET_PAYPAL_MAX';
	protected const PAYMENT_A_P_M_S_AFTERPAY_V1_TITLE = 'PAYMENT_A_P_M_S_AFTERPAY_V1_TITLE';
	protected const PAYMENT_A_P_M_S_AFTERPAY_V1_DESCRIPTION = 'PAYMENT_A_P_M_S_AFTERPAY_V1_DESCRIPTION';
	protected const PAYMENT_A_P_M_S_AFTERPAY_V1_MIN = 'PAYMENT_A_P_M_S_AFTERPAY_V1_MIN';
	protected const PAYMENT_A_P_M_S_AFTERPAY_V1_MAX = 'PAYMENT_A_P_M_S_AFTERPAY_V1_MAX';
	protected const PAYMENT_A_P_M_S_ZIP_TITLE = 'PAYMENT_A_P_M_S_ZIP_TITLE';
	protected const PAYMENT_A_P_M_S_ZIP_DESCRIPTION = 'PAYMENT_A_P_M_S_ZIP_DESCRIPTION';
	protected const PAYMENT_A_P_M_S_ZIP_MIN = 'PAYMENT_A_P_M_S_ZIP_MIN';
	protected const PAYMENT_A_P_M_S_ZIP_MAX = 'PAYMENT_A_P_M_S_ZIP_MAX';
	protected const STYLE_BACKGROUND_COLOR = 'STYLE_BACKGROUND_COLOR';
	protected const STYLE_TEXT_COLOR = 'STYLE_TEXT_COLOR';
	protected const STYLE_BORDER_COLOR = 'STYLE_BORDER_COLOR';
	protected const STYLE_ERROR_COLOR = 'STYLE_ERROR_COLOR';
	protected const STYLE_SUCCESS_COLOR = 'STYLE_SUCCESS_COLOR';
	protected const STYLE_FONT_SIZE = 'STYLE_FONT_SIZE';
	protected const STYLE_FONT_FAMILY = 'STYLE_FONT_FAMILY';
	protected const STYLE_CUSTOM = 'STYLE_CUSTOM';

	public static function cases(): array {
		return parent::cases();
	}

	public function getTitle(): string {
		$text = str_replace( [ '_MIN', '_MAX' ], '', $this->name );

		$result = explode( '_', $text );
		$result = array_map( function ( $item ) {
			return ucfirst( strtolower( $item ) );
		}, $result );

		$result = array_filter( $result, function ( $item ) {
			return ! in_array( strtolower( $item ), [
				'style',
				'payment',
				'card',
				'bank',
				'account',
				'wallet',
				'a',
				'p',
				'm',
				's',
			] );
		} );

		return implode( ' ', $result );
	}

	public function getFullTitle(): string {
		$result = explode( '_', $this->name );
		$result = array_map( function ( $item ) {
			return ucfirst( strtolower( $item ) );
		}, $result );

		return implode( ' ', $result );
	}

	public function getInputType(): string {
		switch ( $this->name ) {
			case self::CUSTOM_VERSION:
			case self::PAYMENT_CARD_TITLE:
			case self::PAYMENT_CARD_DESCRIPTION:
			case self::PAYMENT_BANK_ACCOUNT_TITLE:
			case self::PAYMENT_BANK_ACCOUNT_DESCRIPTION:
			case self::PAYMENT_WALLET_APPLE_PAY_TITLE:
			case self::PAYMENT_WALLET_APPLE_PAY_DESCRIPTION:
			case self::PAYMENT_WALLET_GOOGLE_PAY_TITLE:
			case self::PAYMENT_WALLET_GOOGLE_PAY_DESCRIPTION:
			case self::PAYMENT_WALLET_AFTERPAY_V2_TITLE:
			case self::PAYMENT_WALLET_AFTERPAY_V2_DESCRIPTION:
			case self::PAYMENT_WALLET_PAYPAL_TITLE:
			case self::PAYMENT_WALLET_PAYPAL_DESCRIPTION:
			case self::PAYMENT_A_P_M_S_AFTERPAY_V1_TITLE:
			case self::PAYMENT_A_P_M_S_AFTERPAY_V1_DESCRIPTION:
			case self::PAYMENT_A_P_M_S_ZIP_TITLE:
			case self::PAYMENT_A_P_M_S_ZIP_DESCRIPTION:
				$result = 'text';
				break;
			case self::VERSION:
			case self::STYLE_FONT_FAMILY:
			case self::STYLE_FONT_SIZE:
				$result = 'select';
				break;
			case self::STYLE_CUSTOM:
				$result = 'textarea';
				break;
			case self::PAYMENT_CARD_MIN:
			case self::PAYMENT_CARD_MAX:
			case self::PAYMENT_WALLET_APPLE_PAY_MIN:
			case self::PAYMENT_WALLET_APPLE_PAY_MAX:
			case self::PAYMENT_WALLET_GOOGLE_PAY_MIN:
			case self::PAYMENT_WALLET_GOOGLE_PAY_MAX:
			case self::PAYMENT_WALLET_AFTERPAY_V2_MIN:
			case self::PAYMENT_WALLET_AFTERPAY_V2_MAX:
			case self::PAYMENT_WALLET_PAYPAL_MIN:
			case self::PAYMENT_WALLET_PAYPAL_MAX:
			case self::PAYMENT_A_P_M_S_AFTERPAY_V1_MIN:
			case self::PAYMENT_A_P_M_S_AFTERPAY_V1_MAX:
			case self::PAYMENT_A_P_M_S_ZIP_MIN:
			case self::PAYMENT_A_P_M_S_ZIP_MAX:
				$result = 'min_max';
				break;
			default:
				$result = 'color_picker';
				break;
		}

		return $result;
	}

	public function getOptions(): array {
		switch ( $this->name ) {
			case self::STYLE_FONT_SIZE:
				$result = $this->getFontSizes();
				break;
			case self::VERSION:
				$result = $this->getVersions();
				break;
			case self::STYLE_FONT_FAMILY:
				$result = $this->getFontFamily();
				break;
			default:
				$result = [];
				break;
		}

		return $result;
	}

	public function getFontSizes(): array {
		$result = [];

		for ( $i = 8; $i <= 32; $i += 2 ) {
			$result[ $i . 'px' ] = $i;
		}

		return $result;
	}

	public function getVersions(): array {
		return [
			'latest' => 'latest',
			'custom' => 'custom',
		];
	}

	public function getFontFamily(): array {
		$fonts = [
			'Inter Regular',
			'serif',
			'sans-serif',
			'monospace',
			'cursive',
			'fantasy',
			'system-ui',
			'ui-serif',
			'ui-sans-serif',
			'ui-monospace',
			'ui-rounded',
			'emoji',
			'math',
			'fangsong',
		];

		return array_combine( $fonts, $fonts );
	}

	public function getDefault() {
		switch ( $this->name ) {
			case self::STYLE_FONT_SIZE:
				$result = '18px';
				break;
			case self::VERSION:
				$result = 'latest';
				break;
			case self::PAYMENT_CARD_TITLE:
				$result = 'Cards';
				break;
			case self::PAYMENT_CARD_DESCRIPTION:
				$result = 'Pay by cards';
				break;
			case self::PAYMENT_BANK_ACCOUNT_TITLE:
				$result = 'Bank Accounts';
				break;
			case self::PAYMENT_BANK_ACCOUNT_DESCRIPTION:
				$result = 'Pay by Bank Accounts';
				break;
			case self::PAYMENT_WALLET_APPLE_PAY_TITLE:
				$result = 'Apple Pay';
				break;
			case self::PAYMENT_WALLET_APPLE_PAY_DESCRIPTION:
				$result = 'Apple Pay is a safe, secure, and private way to pay.';
				break;
			case self::PAYMENT_WALLET_GOOGLE_PAY_TITLE:
				$result = 'Google Pay';
				break;
			case self::PAYMENT_WALLET_GOOGLE_PAY_DESCRIPTION:
				$result = 'Google Pay is a quick, easy, and secure way to pay online in store.';
				break;
			case self::PAYMENT_WALLET_AFTERPAY_V2_TITLE:
				$result = 'Afterpay v2';
				break;
			case self::PAYMENT_WALLET_AFTERPAY_V2_DESCRIPTION:
			case self::PAYMENT_A_P_M_S_AFTERPAY_V1_DESCRIPTION:
				$result = 'Shop as usual, then choose Afterpay as your payment method at checkout.';
				break;
			case self::PAYMENT_WALLET_PAYPAL_TITLE:
				$result = 'PayPal';
				break;
			case self::PAYMENT_WALLET_PAYPAL_DESCRIPTION:
				$result = 'PayPal is the faster, safer way to make an online payment.';
				break;
			case self::PAYMENT_A_P_M_S_AFTERPAY_V1_TITLE:
				$result = 'Afterpay v1';
				break;
			case self::PAYMENT_A_P_M_S_ZIP_TITLE:
				$result = 'Zip';
				break;
			case self::PAYMENT_A_P_M_S_ZIP_DESCRIPTION:
				$result = 'Zip Pay is an interest-free buy-now-pay-later service.';
				break;
			case self::STYLE_BACKGROUND_COLOR:
				$result = 'rgb(246, 240, 235)';
				break;
			case self::STYLE_TEXT_COLOR:
				$result = '#191919';
				break;
			case self::STYLE_BORDER_COLOR:
				$result = '#C9BCB9';
				break;
			case self::STYLE_ERROR_COLOR:
				$result = '#CD0000';
				break;
			case self::STYLE_SUCCESS_COLOR:
				$result = '#0B7F3B';
				break;
			case self::PAYMENT_CARD_MIN:
			case self::PAYMENT_WALLET_APPLE_PAY_MIN:
			case self::PAYMENT_WALLET_GOOGLE_PAY_MIN:
			case self::PAYMENT_WALLET_AFTERPAY_V2_MIN:
			case self::PAYMENT_WALLET_PAYPAL_MIN:
			case self::PAYMENT_A_P_M_S_AFTERPAY_V1_MIN:
			case self::PAYMENT_A_P_M_S_ZIP_MIN:
				$result = 0;
				break;
			default:
				$result = null;
				break;
		}

		return $result;
	}

	public function getDescription(): string {
		switch ( $this->name ) {
			case self::STYLE_CUSTOM:
				return 'Enter the API public key for the live environment.
						This key is used for authentication to ensure secure communication
						with the Payment Gateway.';
			default:
				return '';
		}
	}
}
