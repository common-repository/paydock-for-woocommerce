<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly ?>
<tr valign="top">
	<th scope="row" class="titledesc">
		<label for="<?php echo esc_html( $field_key ); ?>">
			<?php echo wp_kses_post( $data['title'] ); ?>
			<?php
			echo wp_kses_post($templateService->settingService->get_tooltip_html( $data )); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped  --  the following require is safe because it is not a user input.
			?>
		</label>
	</th>
	<td class="forminp">
		<fieldset>
			<div id="multiselect-paydock" class="multiselect-paydock select">
				<div class="value">Please select payment methods...</div>
				<div class="error-text">Value is required and can't be empty</div>
			</div>
			<input name="<?php echo esc_attr( $field_key ); ?>" style="visibility: hidden" id='card-select'
				value="<?php echo esc_attr( $value ); ?>">
		</fieldset>
	</td>
</tr>
