<?php

namespace Paydock\API;

use Paydock\Abstracts\AbstractApiService;

class NotificationService extends AbstractApiService {
	const ENDPOINT = 'notifications';

	protected $allowedAction = [ 
		'create' => self::METHOD_POST,
		'search' => self::METHOD_GET,
	];

	private $id;

	public function create( $params ): NotificationService {
		$this->setAction( 'create' );
		$this->parameters = $params;

		return $this;
	}

	public function search( array $parameters = [] ): NotificationService {
		$this->setAction( 'search' );
		$this->parameters = $parameters;

		return $this;
	}

	protected function buildEndpoint(): ?string {
		switch ( $this->action ) {
			case 'create':
				$result = self::ENDPOINT;
				break;
			default:
				$result = self::ENDPOINT . '?limit=-1';
		}

		return $result;
	}
}
