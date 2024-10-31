<?php

namespace Paydock\Repositories;

use Paydock\Services\HashService;

class UserTokenRepository {
	const CARD_TOKENS_KEY = 'paydock_card_tokens';

	private $cache;
	private $userId;

	public function __construct() {
		if ( ! is_user_logged_in() ) {
			throw new \Exception( 'User not logged in' );
		}

		$this->userId = get_current_user_id();
	}

	public function getUserToken( string $token ): array {
		$tokens = $this->getUserTokens();

		$vaultToken = [];

		foreach ( $tokens as $item ) {
			if ( $item['vault_token'] === $token ) {
				$vaultToken = $item;
				break;
			}
		}

		return $vaultToken;
	}

	public function getUserTokens(): array {
		if ( null === $this->cache ) {
			$userMeta = get_user_meta( $this->userId, self::CARD_TOKENS_KEY, true );

			if ( ! empty( $userMeta ) ) {
				foreach ( $userMeta as $index => $item ) {
					if ( ! empty( $item['vault_token'] ) ) {
						$userMeta[ $index ]['vault_token'] = HashService::decrypt( $item['vault_token'] );
					}
				}
			}

			$this->cache = array_values( is_array( $userMeta ) ? $userMeta : [] );

			if ( empty( $this->cache ) ) {
				$this->cache = [];
			}
		}

		return $this->cache;
	}

	public function saveUserToken( array $token ) {
		$tokens = $this->getUserTokens();
		if ( ! empty( $tokens ) ) {
			$tokens = array_filter( $tokens, function ($item) use ($token) {
				$result = true;
				switch ( $token['type'] ) {
					case 'card':
						$result = $item['card_number_bin'] . $item['card_number_last4'] !== $token['card_number_bin'] . $token['card_number_last4'];
						break;
					case 'bank_account':
						$result = $item['account_routing'] . $item['account_number'] !== $token['account_routing'] . $token['account_number'];
						break;
				}

				return $result;
			} );
		}

		$tokens[] = $token;

		foreach ( $tokens as $index => $item ) {
			if ( ! empty( $item['vault_token'] ) ) {
				$tokens[ $index ]['vault_token'] = HashService::encrypt( $item['vault_token'] );
			}
		}

		$result = update_user_meta( $this->userId, self::CARD_TOKENS_KEY, $tokens );

		$this->cleanCache();

		return $result;
	}

	private function cleanCache(): void {
		$this->cache = null;
	}

	public function updateUserToken( string $token, $data ) {
		$tokens = $this->getUserTokens();

		$tokens = array_map( function ($value) use ($token, $data) {
			if ( $value['vault_token'] === $token ) {
				$value = array_merge( $value, $data );
			}

			return $value;
		}, $tokens );

		foreach ( $tokens as $index => $item ) {
			if ( ! empty( $item['vault_token'] ) ) {
				$tokens[ $index ]['vault_token'] = HashService::encrypt( $item['vault_token'] );
			}
		}

		$result = update_user_meta( $this->userId, self::CARD_TOKENS_KEY, $tokens );

		$this->cleanCache();

		return $result;
	}

	public function deleteAllUserTokens() {
		$result = delete_user_meta( $this->userId, self::CARD_TOKENS_KEY );

		$this->cleanCache();

		return $result;
	}
}
