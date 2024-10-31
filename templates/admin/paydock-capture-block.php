<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly ?>
<script type="text/javascript">
    function paydockPaymentCapture() {
        jQuery('span.paydock-order-actions').hide();
        jQuery('div.wc-order-totals-items').slideUp();
        jQuery('#woocommerce-order-items').find('div.wc-order-partial-paid-items').show();
    }

    function cancelActionManualCapture() {
        jQuery('span.paydock-order-actions').show();
        jQuery('div.wc-order-totals-items').slideDown();
        jQuery('#woocommerce-order-items').find('div.wc-order-partial-paid-items').hide();
    }

    function validationManualCapture(elem) {
        const captureAmount = Number(jQuery('#capture_amount').val());
        const captureAmountBtn = jQuery('#woocommerce-order-items').find('div.wc-order-partial-paid-items .capture-amount-btn')
        const availableToCapture = Number(jQuery('div.wc-order-partial-paid-items').find('.available-to-capture').data('value'));
        if (captureAmount > availableToCapture) {
            alert("You cannot capture be greater than order total " + availableToCapture);
            elem.val(availableToCapture);
            captureAmountBtn.text(availableToCapture);
            return false;
        }

        if (captureAmount <= 0) {
            alert("This field should be positive");
            elem.val(availableToCapture);
            captureAmountBtn.text(availableToCapture);
            return false;
        }

        elem.val(captureAmount);
        captureAmountBtn.text(captureAmount);
        return true;
    }

    function handlePaydockPaymentCapture(orderId, operation) {
        if (operation === 'paydock-capture-charge') {
            let validSumManualCapture = validationManualCapture(jQuery('#capture_amount'));
            if (!validSumManualCapture) {
                return;
            }
        }
        const captureAmount = Number(jQuery('#capture_amount').val());
        jQuery.blockUI({
            message: '',
            overlayCSS: {backgroundColor: '#fff', opacity: 0.6, cursor: 'wait'}
        });
        jQuery.ajax({
            url: '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: {
                action: operation,
                order_id: orderId,
                amount: captureAmount,
                _wpnonce: '<?php echo esc_attr( wp_create_nonce( 'capture-or-cancel' ) ); ?>'
            },
            success: function (response) {
                if (response.data.message !== undefined) {
                    alert(response.data.message);
                }
                if (response.success == 1) {
                    location.reload();
                } else {
                    jQuery.unblockUI();
                }
            }
        });
    }
</script>
<span class="paydock-order-actions">
	<?php if ( 'paydock-authorize' == $order->get_status() ) : ?>
        <button type="button"
                onclick="paydockPaymentCapture(<?php echo esc_attr( $order->get_id() ); ?>, 'paydock-capture-charge')"
                class="button">
			Capture charge
		</button>
	<?php endif; ?>
	<button type="button"
            onclick="handlePaydockPaymentCapture(<?php echo esc_attr( $order->get_id() ); ?>, 'paydock-cancel-authorised')"
            class="button">
		Cancel charge
	</button>
</span>
<div class="wc-order-data-row wc-order-partial-paid-items wc-order-data-row-toggle" style="display: none;">
    <table class="wc-order-totals">
        <tr>
            <td class="label"><?php esc_html_e( 'Total available to capture', 'woocommerce' ); ?>:</td>
            <td class="total available-to-capture" data-value="<?php echo esc_html( $order->get_total() ); ?>">
				<?php echo wp_kses_post( wc_price( $order->get_total(), [ 'currency' => $order->get_currency() ] ) ); ?>
            </td>
        </tr>
        <tr>
            <td class="label">
                <label for="refund_amount">
					<?php esc_html_e( 'Capture amount', 'woocommerce' ); ?>:
                </label>
            </td>
            <td class="total">
                <input type="text" id="capture_amount" onchange="validationManualCapture(jQuery(this))"
                       name="capture_amount"
                       value="<?php echo esc_attr( $order->get_total() ); ?>"
                       class="wc_input_price"/>
                <div class="clear"></div>
            </td>
        </tr>

    </table>
    <div class="clear"></div>
    <div class="refund-actions manual-capture-actions">
		<?php /* translators: capture amount  */ ?>
        <button type="button"
                onclick="handlePaydockPaymentCapture(<?php echo esc_attr( $order->get_id() ); ?>, 'paydock-capture-charge')"
                class="button button-primary">
			<?php esc_html_e( 'Capture ', 'woocommerce' );
			$currency_symbol = get_woocommerce_currency_symbol( $order->get_currency() );;
			echo esc_html( $currency_symbol );
			?>
            <span class="capture-amount-btn">
                <?php echo esc_attr( $order->get_total() ); ?>
            </span>
			<?php esc_html_e( ' manually', 'woocommerce' ); ?>
        </button>
        <button type="button" class="button cancel-action"
                onclick="cancelActionManualCapture()"><?php esc_html_e( 'Cancel', 'woocommerce' ); ?></button>
        <div class="clear"></div>
    </div>
</div>
