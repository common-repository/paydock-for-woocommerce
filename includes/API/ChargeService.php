<?php

namespace Paydock\API;

use Paydock\Abstracts\AbstractApiService;

class ChargeService extends AbstractApiService {
	const ENDPOINT = 'charges';
	const WALLETS_INITIALIZE_ENDPOINT = 'wallet';
	const STANDALONE_FRAUD_ENDPOINT = 'fraud';
	const STANDALONE_3DS_ENDPOINT = 'standalone-3ds';
	const CAPTURE_ENDPOINT = 'capture';
	const REFUNDS_ENDPOINT = 'refunds';
	const FRAUD_ATTACH_ENDPOINT = 'fraud/attach';

	protected $directCharge = null;

	protected $id = null;
	protected $allowedAction = [ 
		'create' => self::METHOD_POST,
		'update' => self::METHOD_POST,
		'wallet-initialize' => self::METHOD_POST,
		'standalone-fraud' => self::METHOD_POST,
		'standalone-3ds' => self::METHOD_POST,
		'capture' => self::METHOD_POST,
		'refunds' => self::METHOD_POST,
		'cancel-authorised' => self::METHOD_DELETE,
		'fraud-attach' => self::METHOD_POST,
	];

	public function create( array $params ): self {
		$this->parameters = $params;

		$this->setAction( 'create' );

		return $this;
	}

	public function update( string $id, array $params ): self {
		$this->id = $id;
		$this->parameters = $params;

		$this->setAction( 'update' );

		return $this;
	}

	public function standaloneFraud( array $params ): self {
		$this->parameters = $params;

		$this->setAction( 'standalone-fraud' );

		return $this;
	}

	public function standalone3Ds( array $params ): self {
		$this->parameters = $params;

		$this->setAction( 'standalone-3ds' );

		return $this;
	}

	public function walletsInitialize( array $params, ?bool $directCharge ): self {
		$this->parameters = $params;

		$this->setAction( 'wallet-initialize' );

		$this->directCharge = $directCharge;

		return $this;
	}

	public function capture( array $params ): self {
		$this->parameters = $params;

		$this->setAction( 'capture' );

		return $this;
	}


	public function refunds( array $params ): self {
		$this->parameters = $params;

		$this->setAction( 'refunds' );

		return $this;
	}

	public function cancelAuthorised( array $params ): self {
		$this->parameters = $params;

		$this->setAction( 'cancel-authorised' );

		return $this;
	}

	public function fraudAttach( string $id, array $params ): self {
		$this->id = $id;
		$this->parameters = $params;

		$this->setAction( 'fraud-attach' );

		return $this;
	}

	protected function buildEndpoint(): ?string {
		switch ( $this->action ) {
			case 'create':
				$result = self::ENDPOINT;
				if ( isset( $this->parameters['capture'] ) ) {
					$result .= '?capture=' . ( $this->parameters['capture'] ? 'true' : 'false' );
					unset( $this->parameters['capture'] );
				}
				break;
			case 'standalone-fraud':
				$result = self::ENDPOINT . '/' . self::STANDALONE_FRAUD_ENDPOINT;
				break;
			case 'standalone-3ds':
				$result = self::ENDPOINT . '/' . self::STANDALONE_3DS_ENDPOINT;
				break;
			case 'refunds':
				$result = self::ENDPOINT . '/' . $this->parameters['charge_id'] . '/' . self::REFUNDS_ENDPOINT;
				unset( $this->parameters['charge_id'] );
				break;
			case 'capture':
			case 'cancel-authorised':
				$result = self::ENDPOINT . '/' . $this->parameters['charge_id'] . '/' . self::CAPTURE_ENDPOINT;
				unset( $this->parameters['charge_id'] );
				break;
			case 'wallet-initialize':
				$result = self::ENDPOINT . '/' . self::WALLETS_INITIALIZE_ENDPOINT;
				break;
			case 'update':
				$result = self::ENDPOINT . '/' . $this->id;
				break;
			case 'fraud-attach':
				$result = self::ENDPOINT . '/' . $this->id . '/' . self::FRAUD_ATTACH_ENDPOINT;
				break;
			default:
				$result = self::ENDPOINT;
		}

		if ( isset( $this->directCharge ) && ! is_null( $this->directCharge ) ) {
			$result .= '?capture=' . ( $this->directCharge ? 'true' : 'false' );
		}

		return $result;
	}
}
