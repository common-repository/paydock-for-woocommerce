<?php

namespace Paydock\Repositories;

class UserCustomerRepository {
	const USER_CUSTOMERS_KEY = 'paydock_card_customers';

	private $cache;
	private $userId;

	public function __construct() {
		if ( ! is_user_logged_in() ) {
			throw new \Exception( 'User not logged in' );
		}

		$this->userId = get_current_user_id();
	}

	public function getUserCustomer( string $customers ): array {
		$customerItems = $this->getUserCustomers();

		$customer = [];

		foreach ( $customerItems as $item ) {
			if ( $item['_id'] === $customers ) {
				$customer = $item;
				break;
			}
		}

		return $customer;
	}

	public function getUserCustomers(): array {
		if ( null === $this->cache ) {
			$this->cache = get_user_meta( $this->userId, self::USER_CUSTOMERS_KEY, true );

			if ( empty( $this->cache ) ) {
				$this->cache = [];
			}
		}

		return $this->cache;
	}

	public function saveUserCustomer( array $customer ) {
		$customers = $this->getUserCustomers();
		$customers[] = $customer;

		$result = update_user_meta( $this->userId, self::USER_CUSTOMERS_KEY, $customers );

		$this->cleanCache();

		return $result;
	}

	private function cleanCache(): void {
		$this->cache = null;
	}

	public function updateUserCustomer( string $customerId, $data ) {
		$customers = $this->getUserCustomers();

		$customers = array_map( function ($value) use ($customerId, $data) {
			if ( $value['_id'] === $customerId ) {
				$value = array_merge( $value, $data );
			}

			return $value;
		}, $customers );

		$result = update_user_meta( $this->userId, self::USER_CUSTOMERS_KEY, $customers );

		$this->cleanCache();

		return $result;
	}

	public function deleteAllUserCustomers() {
		$result = delete_user_meta( $this->userId, self::USER_CUSTOMERS_KEY );

		$this->cleanCache();

		return $result;
	}
}
