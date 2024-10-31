<?php

namespace Paydock\Abstracts;

use Paydock\Enums\SettingsTabs;
use Paydock\PaydockPlugin;
use Paydock\Services\Assets\AdminAssetsService;
use Paydock\Services\TemplateService;

abstract class AbstractSettingService extends \WC_Payment_Gateway {
	public $currentSection = null;
	protected $templateService;

	public function __construct() {
		$available_sections = array_map( function ( $item ) {
			return strtolower( $item->value );
		}, SettingsTabs::allCases() );

		$section = filter_input( INPUT_GET, 'section', FILTER_SANITIZE_STRING );

		if ( in_array( $section, $available_sections ) ) {
			$this->currentSection = $section;
		}
		$this->id                 = $this->getId();
		$this->enabled            = $this->get_option( 'enabled' );
		$this->method_title       = __( 'Paydock Gateway', 'paydock' );
		$this->method_description = __(
			'Paydock simplify how you manage your payments. Reduce costs, technical headaches & streamline compliance using Paydock\'s payment orchestration.',
			'paydock'
		);

		$this->title = __( 'Paydock Gateway', 'paydock' );

		$this->icon = plugins_url( 'assets/images/logo.svg' );

		$this->init_settings();
		$this->init_form_fields();

		if ( is_admin() ) {
			new AdminAssetsService();
			$this->templateService = new TemplateService( $this );
		}

	}

	abstract protected function getId(): string;

	public function parentGenerateSettingsHtml( $formFields = [], $echo = true ): ?string {
		return parent::generate_settings_html( $formFields, $echo );
	}

	public function generate_settings_html( $form_fields = [], $echo = true ): ?string {
		if ( empty( $form_fields ) ) {
			$form_fields = $this->get_form_fields();
		}

		$tabs = $this->getTabs();

		if ( $echo ) {
			$this->templateService->includeAdminHtml( 'admin', compact( 'tabs', 'form_fields' ) );
		} else {
			return $this->templateService->getAdminHtml( 'admin', compact( 'tabs', 'form_fields' ) );
		}

		return null;
	}

	protected function getTabs(): array {
		return [
			SettingsTabs::LIVE_CONNECTION()->value    => [
				'label'  => __( 'Live Connection' ),
				'active' => SettingsTabs::LIVE_CONNECTION()->value == $this->currentSection,
			],
			SettingsTabs::SANDBOX_CONNECTION()->value => [
				'label'  => __( 'Sandbox Connection' ),
				'active' => SettingsTabs::SANDBOX_CONNECTION()->value == $this->currentSection,
			],
			SettingsTabs::WIDGET()->value             => [
				'label'  => __( 'Widget Configuration' ),
				'active' => SettingsTabs::WIDGET()->value == $this->currentSection,
			],
			SettingsTabs::LOG()->value                => [
				'label'  => __( 'Logs' ),
				'active' => SettingsTabs::LOG()->value == $this->currentSection,
			],
		];
	}

	public function generate_label_html( $key, $value ) {
		return $this->templateService->getAdminHtml( 'label', compact( 'key', 'value' ) );
	}

	public function generate_big_label_html( $key, $value ) {
		return $this->templateService->getAdminHtml( 'big-label', compact( 'key', 'value' ) );
	}

	public function generate_card_select_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );

		$defaults = [
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'type'              => 'text',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => [],
			'options'           => [],
		];

		$data  = wp_parse_args( $data, $defaults );
		$value = $this->get_option( $key );

		return $this->templateService->getAdminHtml(
			'card-select',
			compact(
				'data',
				'value',
				'field_key',
				'data'
			)
		);
	}
}
