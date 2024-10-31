<?php

namespace Paydock\API;

use Paydock\Abstracts\AbstractApiService;

class CustomerService extends AbstractApiService {
	const ENDPOINT = 'customers';

	protected $allowedAction = [ 
		'create' => self::METHOD_POST,
	];

	public function create( array $params ): CustomerService {
		$this->parameters = $params;

		$this->setAction( 'create' );

		return $this;
	}

	protected function buildEndpoint(): ?string {
		return self::ENDPOINT;
	}
}
