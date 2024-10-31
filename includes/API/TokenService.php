<?php

namespace Paydock\API;

use Paydock\Abstracts\AbstractApiService;

class TokenService extends AbstractApiService {
	const ENDPOINT = 'payment_sources/tokens';

	protected $allowedAction = [ 
		'create' => self::METHOD_POST,
	];

	public function create( array $params ): TokenService {
		$this->parameters = $params;

		$this->setAction( 'create' );

		return $this;
	}

	protected function buildEndpoint(): ?string {
		return self::ENDPOINT;
	}
}
