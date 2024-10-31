<?php

namespace Paydock\Enums;

use Paydock\Abstracts\AbstractEnum;

class ChargeStatuses extends AbstractEnum {
	protected const COMPLETE = 'Complete';
	protected const PENDING = 'Pending';
	protected const REQUESTED = 'Requested';
	protected const FAILED = 'Failed';
	protected const REFUND_REQUESTED = 'refund_requested';
	protected const VOID_REQUESTED = 'void_requested';
	protected const CAPTURE_REQUESTED = 'capture_requested';
	protected const REFUND_VOID_REQUESTED = 'refund_void_requested';
	protected const REFUNDED = 'refunded';
	protected const INPROGRESS = 'inprogress';
	protected const HELD = 'held';
	protected const ARCHIVED = 'archived';
	protected const NOT_AUTHENTICATED = 'not_authenticated';
	protected const PRE_AUTHENTICATED = 'pre_authenticated';
	protected const PRE_AUTHENTICATION_PENDING = 'Pre_authentication_pending';
	protected const INREVIEW = 'inreview';
	protected const DECLINED = 'declined';
	protected const CANCELLED = 'cancelled';
}
