<?php

namespace Paydock\Services\Settings;

use Paydock\Abstracts\AbstractSettingService;
use Paydock\Enums\SettingsTabs;
use Paydock\Enums\WidgetSettings;
use Paydock\Services\SettingsService;
use Paydock\Services\Validation\WidgetValidationService;

class WidgetSettingService extends AbstractSettingService {
	private const CUSTOM_STYLES_PLACEHOLDER = '{
	"input":{
		"color": "rgb(0, 0, 0)",
		"border": "dashed red;",
		"border_radius": "30px",
		"background_color": "rgba(255, 255, 255, 0.9)",
		"height": "20px",
		"text_decoration": "underline",
		"font_size": "20px",
		"font_family": "serif",
		"transition": "margin-right 2s",
		"line_height": "20",
		"font_weight": "400",
		"padding": "2",
		"margin": "2"
	},
	"label":{
		"color": "rgb(0, 0, 0)",
		"text_decoration": "underline",
		"font_size": "20px",
		"font_family": "serif",
		"line_height": "20",
		"font_weight": "400",
		"padding": "2",
		"margin": "2"
	},
	"title":{
		"color": "rgb(0, 0, 0)",
		"text_decoration": "underline",
		"font_size": "20px",
		"font_family": "serif",
		"line_height": "20",
		"font_weight": "400",
		"padding": "2",
		"margin": "2",
		"text-align": "center"
	},
	"title_description":{
		"color": "rgb(0, 0, 0)",
		"text_decoration": "underline",
		"font_size": "20px",
		"font_family": "serif",
		"line_height": "20",
		"font_weight": "400",
		"padding": "2",
		"margin": "2",
		"text-align": "center"
	}
}';

	public function init_form_fields(): void {
		$service = SettingsService::getInstance();
		foreach ( WidgetSettings::cases() as $case ) {
			$key = $service->getOptionName( $this->id, [
				$case->name,
			] );

			if ( WidgetSettings::PAYMENT_CARD_TITLE()->name === $case->name ) {
				$this->form_fields[ $key . '_big_label' ] = [
					'type'  => 'big_label',
					'title' => __( 'Payment Methods:', 'paydock' ),
				];
				$this->form_fields[ $key . '_label' ]     = [
					'type'  => 'label',
					'title' => __( 'Cards', 'paydock' ),
				];
			} elseif ( WidgetSettings::PAYMENT_BANK_ACCOUNT_TITLE()->name === $case->name ||
			           WidgetSettings::PAYMENT_BANK_ACCOUNT_DESCRIPTION()->name === $case->name ) {
				continue;
			} elseif ( WidgetSettings::PAYMENT_WALLET_APPLE_PAY_TITLE()->name === $case->name ) {
				$this->form_fields[ $key . '_label' ] = [
					'type'  => 'label',
					'title' => __( 'Wallets', 'paydock' ),
				];
			} elseif ( WidgetSettings::PAYMENT_A_P_M_S_AFTERPAY_V1_TITLE()->name === $case->name ) {
				$this->form_fields[ $key . '_label' ] = [
					'type'  => 'label',
					'title' => __( 'APMs', 'paydock' ),
				];
			} elseif ( WidgetSettings::STYLE_BACKGROUND_COLOR()->name === $case->name ) {
				$this->form_fields[ $key . '_label' ] = [
					'type'  => 'big_label',
					'title' => __( 'Widget Styles:', 'paydock' ),
				];
			}

			$this->form_fields[ $key ] = [
				'type'    => $case->getInputType(),
				'title'   => $case->getTitle(),
				'default' => $case->getDefault(),
			];

			$options = $case->getOptions();

			if ( ! empty( $options ) && ( 'select' == $case->getInputType() ) ) {
				$this->form_fields[ $key ]['options'] = $options;
			} elseif ( 'textarea' == $case->getInputType() ) {
				$this->form_fields[ $key ]['placeholder'] = self::CUSTOM_STYLES_PLACEHOLDER;
				$this->form_fields[ $key ]['class']       = 'custom-textarea';
			}
		}
	}

	public function generate_color_picker_html( $key, $data ) {
		$value = $this->get_option( $key );
		$key   = $this->get_field_key( $key );

		$data['desc_tip'] = ! empty( $data['desc_tip'] ) ? $data['desc_tip'] : '';

		return $this->templateService
			->getAdminHtml( 'color-picker', compact( 'key', 'data', 'value' ) );
	}

	public function generate_min_max_html( $key, $data ) {
		if ( false !== strpos( $key, 'MAX' ) ) {
			return '';
		}

		$max_key = str_replace( 'MIN', 'MAX', $key );

		$keys = [
			'min' => $this->get_field_key( $key ),
			'max' => $this->get_field_key( $max_key ),
		];

		$min = $this->get_option( $key );
		$max = $this->get_option( $max_key );

		if ( empty( $min ) ) {
			$min = $data['default'];
		}

		if ( empty( $max ) ) {
			$max = '';
		}

		$value = [
			'min' => $min,
			'max' => $max,
		];

		$data['desc_tip'] = ! empty( $data['desc_tip'] ) ? $data['desc_tip'] : '';

		return $this->templateService
			->getAdminHtml( 'min-max', compact( 'keys', 'data', 'value' ) );
	}

	public function generate_textarea_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults  = [
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'type'              => 'text',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => [],
		];

		$data = wp_parse_args( $data, $defaults );

		return $this->templateService
			->getAdminHtml( 'textarea', compact( 'key', 'data', 'field_key' ) );
	}

	public function process_admin_options() {
		$this->init_settings();
		$validationService = new WidgetValidationService( $this );
		$this->settings    = array_merge( $this->settings, $validationService->getResult() );

		foreach ( $validationService->getErrors() as $error ) {
			$this->add_error( $error );
			\WC_Admin_Settings::add_error( $error );
		}

		$option_key = $this->get_option_key();
		do_action( 'woocommerce_update_option', [ 'id' => $option_key ] );

		return update_option(
			$option_key,
			apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings ),
			'yes'
		);
	}

	protected function getId(): string {
		return SettingsTabs::WIDGET()->value;
	}
}
