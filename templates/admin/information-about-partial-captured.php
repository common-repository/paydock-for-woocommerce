<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly ?>

<table class="wc-order-totals" style="border-top: 1px solid #999; color: #5b841b; margin-top:12px; padding-top:12px">
    <tbody>
    <tr>
        <td class="labe">Captured Amount:</td>
        <td width="1%"></td>
        <td class="total  captured-amount">
				<span class="woocommerce-Price-amount">
					<bdi>
						<span class="woocommerce-Price-currencySymbol">
                            <?php
                            $currency_symbol = get_woocommerce_currency_symbol( $order->get_currency() );;
                            echo esc_html( $currency_symbol );
                            ?>
						</span>
						<span class="amount">
                            <?php echo number_format( (float) $capturedAmount, 2, '.', '' ); ?>
                        </span>
						<span class="available-to-refund-amount hidden">
                            <?php echo number_format( $capturedAmount - $order->get_total_refunded(), 2, '.', '' ) ?>
                        </span>
					</bdi>
				</span>
        </td>
    </tr>
    </tbody>
</table>
