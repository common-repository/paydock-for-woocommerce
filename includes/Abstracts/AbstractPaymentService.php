<?php

namespace Paydock\Abstracts;

use Paydock\Services\SettingsService;
use WC_Payment_Gateway;

abstract class AbstractPaymentService extends WC_Payment_Gateway {
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->icon       = apply_filters( 'woocommerce_paydock_gateway_icon', '' );
		$this->has_fields = true;
		$this->supports   = [
			'products',
			'subscriptions',
			'subscription_cancellation',
			'subscription_suspension',
			'subscription_reactivation',
			'subscription_amount_changes',
			'subscription_date_changes',
			'multiple_subscriptions',
			'default_credit_card_form',
		];

		$this->method_title       = _x( 'Paydock payment', 'Paydock payment method', 'woocommerce-gateway-paydock' );
		$this->method_description = __( 'Allows Paydock payments.', 'woocommerce-gateway-paydock' );

		$this->init_settings();
	}


	public function woocommerce_before_checkout_form( $arg ) {

	}

	public function payment_scripts() {
		if ( ! is_checkout() || ! $this->is_available() ) {
			return '';
		}

		wp_enqueue_script( 'paydock-form', PAYDOCK_PLUGIN_URL . '/assets/js/frontend/form.js', [], time(), true );
		wp_enqueue_style(
			'paydock-widget-css',
			PAYDOCK_PLUGIN_URL . '/assets/css/frontend/widget.css',
			[],
			time()
		);

		wp_enqueue_script( 'paydock-api', SettingsService::getInstance()->getWidgetScriptUrl(), [], time(), true );
	}
}
