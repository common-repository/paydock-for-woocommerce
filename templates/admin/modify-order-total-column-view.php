<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly ?>
<del aria-hidden="true">
    <?php wc_price( $order->get_total(), [ 'currency' => $order->get_currency() ] ) ?></del>
<ins><?php wc_price( $capturedAmount, [ 'currency' => $order->get_currency() ] ) ?></ins>
