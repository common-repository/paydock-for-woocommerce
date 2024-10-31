<?php

namespace Paydock\API;

use Paydock\Abstracts\AbstractApiService;

class ServiceService extends AbstractApiService {
	const ENDPOINT = 'services';

	protected $allowedAction = [ 
		'get' => self::METHOD_GET,
		'search' => self::METHOD_GET,
	];

	private $id;

	public function get(): ServiceService {
		$this->setAction( 'get' );

		return $this;
	}

	public function setId( $id ): ServiceService {
		$this->id = $id;

		return $this;
	}

	public function search( array $parameters = [] ): ServiceService {
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
