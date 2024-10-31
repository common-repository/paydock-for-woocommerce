<?php

namespace Paydock\Abstracts;

use Paydock\API\ConfigService;
use WP_Error;

abstract class AbstractApiService {
	const METHOD_GET = 'GET';
	const METHOD_POST = 'POST';
	const METHOD_DELETE = 'DELETE';

	protected $action;
	protected $parameters = [];
	protected $allowedAction = [];

	public function call(): array {
		$url  = ConfigService::buildApiUrl( $this->buildEndpoint() );
		$args = [
			'headers' => [
				'content-type' => 'application/json',
			],
		];

		if ( ! empty( ConfigService::$secretKey ) ) {
			$args['headers']['x-user-secret-key'] = ConfigService::$secretKey;
		}

		if ( ! empty( ConfigService::$accessToken ) ) {
			$args['headers']['x-access-token'] = ConfigService::$accessToken;
		}

		if ( ! empty( ConfigService::$publicKey ) ) {
			$args['headers']['x-user-public-key'] = ConfigService::$publicKey;
		}

		$args['headers']['X-paydock-Meta'] = 'V'
		                                     . PAYDOCK_PLUGIN_VERSION
		                                     . '_woocommerce_'
		                                     . WC()->version;

		switch ( $this->allowedAction[ $this->action ] ) {
			case 'POST':
				$args['body'] = wp_json_encode( $this->parameters, JSON_PRETTY_PRINT );
				$parsed_args  = wp_parse_args( $args, [
					'method'  => 'POST',
					'timeout' => 10,
				] );
				break;
			case 'DELETE':
				$parsed_args = wp_parse_args( $args, [
					'method'  => 'DELETE',
					'timeout' => 10,
				] );
				break;
			default:
				$parsed_args = wp_parse_args( $args, [
					'method'  => 'GET',
					'timeout' => 10,
				] );
		}

		$request = _wp_http_get_object()->request( $url, $parsed_args );

		if ( $request instanceof WP_Error ) {
			return [ 'status' => 403, 'error' => $request ];
		}

		$body = json_decode( $request['body'], true );

		if ( null === $body && json_last_error() !== JSON_ERROR_NONE ) {
			return [
				'status' => 403,
				'error'  => [ 'message' => 'Oops! We\'re experiencing some technical difficulties at the moment. Please try again later. ' ],
				'body'   => $request['body']
			];
		}

		return $body;
	}

	protected function setAction( $action ): void {
		if ( empty( $this->allowedAction[ $action ] ) ) {
			/* translators: %s: Missing action name. */
			throw new \LogicException( esc_html( sprintf( __( 'Not allowed action: %s', 'paydock' ), $action ) ) );
		}

		$this->action = $action;
	}
}
