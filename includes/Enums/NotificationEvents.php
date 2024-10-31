<?php

namespace Paydock\Enums;

use Paydock\Abstracts\AbstractEnum;

class NotificationEvents extends AbstractEnum {
	protected const TRANSACTION_SUCCESS = 'Transaction Success';
	protected const TRANSACTION_FAILURE = 'Transaction Failure';
	protected const REFUND_REQUESTED = 'Refund Requested';
	protected const REFUND_SUCCESS = 'Refund Successful';
	protected const REFUND_FAILURE = 'Refund Failed';
	protected const STANDALONE_FRAUD_CHECK_IN_REVIEW = 'Standalone Fraud Check In Review';
	protected const STANDALONE_FRAUD_CHECK_SUCCESS = 'Standalone Fraud Check Success';
	protected const STANDALONE_FRAUD_CHECK_FAILED = 'Standalone Fraud Check Failed';
	protected const STANDALONE_FRAUD_CHECK_IN_REVIEW_APPROVED = 'Standalone Fraud Check In Review Approved';
	protected const STANDALONE_FRAUD_CHECK_IN_REVIEW_DECLINED = 'Standalone Fraud Check In Review Declined';
	protected const FRAUD_CHECK_IN_REVIEW = 'Fraud Check In Review';
	protected const FRAUD_CHECK_IN_REVIEW_ASYNC_APPROVED = 'Fraud Check In Review Async Approved';
	protected const FRAUD_CHECK_IN_REVIEW_ASYNC_DECLINED = 'Fraud Check In Review Async Declined';
	protected const FRAUD_CHECK_TRANSACTION_IN_REVIEW_ASYNC_APPROVED = 'Fraud Check Transaction In Review Async Approved';
	protected const FRAUD_CHECK_TRANSACTION_IN_REVIEW_ASYNC_DECLINED = 'Fraud Check Transaction In Review Async Declined';
	protected const FRAUD_CHECK_SUCCESS = 'Fraud Check Success';
	protected const FRAUD_CHECK_FAILED = 'Fraud Check Failed';
	protected const FRAUD_CHECK_TRANSACTION_IN_REVIEW_APPROVED = 'Fraud Check Transaction In Review Approved';
	protected const FRAUD_CHECK_TRANSACTION_IN_REVIEW_DECLINED = 'Fraud Check Transaction In Review Declined';
	protected const STANDALONE_FRAUD_CHECK_IN_REVIEW_ASYNC_DECLINED = 'Standalone Fraud Check In Review Async Declined';
	protected const STANDALONE_FRAUD_CHECK_IN_REVIEW_ASYNC_APPROVED = 'Standalone Fraud Check In Review Async Approved';

	public static function events(): array {
		$result = [];

		foreach ( self::cases() as $type ) {
			$result[] = strtolower( $type->name );
		}

		return $result;
	}

	public static function toArray(): array {
		$result = [];

		foreach ( self::cases() as $type ) {
			$result[ $type->name ] = $type->value;
		}

		return $result;
	}
}
