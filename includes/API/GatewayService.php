<?php

namespace Paydock\API;

use Paydock\Abstracts\AbstractApiService;

class GatewayService extends AbstractApiService {
	const ENDPOINT = 'gateways';

	protected $allowedAction = [ 
		'get' => self::METHOD_GET,
		'search' => self::METHOD_GET,
	];

	private $id;

	public function get(): GatewayService {
		$this->setAction( 'get' );

		return $this;
	}

	public function setId( $id ): GatewayService {
		$this->id = $id;

		return $this;
	}

	public function search( array $parameters = [] ): GatewayService {
		$this->setAction( 'search' );
		$this->parameters = $parameters;

		return $this;
	}

	protected function buildEndpoint(): ?string {
		switch ( $this->action ) {
			case 'get':
				$result = self::ENDPOINT . '/' . urlencode( $this->id );
				break;
			default:
				$result = self::ENDPOINT . '?limit=1000';
		}

		return $result;
	}
}
