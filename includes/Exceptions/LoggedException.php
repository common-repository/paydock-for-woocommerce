<?php

namespace Paydock\Exceptions;

use Exception;

class LoggedException extends Exception {
	public $response = [];

	public function __construct( string $message = '', int $code = 0, $previous = null, array $response = [] ) {
		$this->response = $response;

		parent::__construct( $message, $code, $previous );
	}
}
