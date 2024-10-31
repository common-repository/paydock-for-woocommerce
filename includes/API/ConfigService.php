<?php

namespace Paydock\API;

use Paydock\Enums\ConfigAPI;

class ConfigService {
	public static $environment = null;
	public static $accessToken = null;
	public static $secretKey = null;
	public static $publicKey = null;

	public static function init( string $environment, string $secretKeyOrAccessToken, ?string $publicKey = null ) {
		self::$environment = $environment;

		if ( self::isAccessToken( $secretKeyOrAccessToken ) ) {
			self::$secretKey = null;
			self::$accessToken = $secretKeyOrAccessToken;
		} else {
			self::$secretKey = $secretKeyOrAccessToken;
			self::$accessToken = null;
		}

		self::$publicKey = $publicKey;
	}

	public static function isAccessToken( string $token ): bool {
		return count( explode( '.', $token ) ) === 3;
	}

	public static function buildApiUrl( ?string $endpoint = null ): string {
		if ( ConfigAPI::PRODUCTION_ENVIRONMENT()->value === self::$environment ) {
			return ConfigAPI::PRODUCTION_API_URL()->value . $endpoint;
		}

		return ConfigAPI::SANDBOX_API_URL()->value . $endpoint;
	}
}
