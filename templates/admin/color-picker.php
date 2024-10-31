<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly ?>
<tr valign="top">
	<th scope="row" class="titledesc">
		<label for="<?php echo esc_attr( $key ); ?>">
			<?php echo wp_kses_post( $data['title'] ); ?>
			<?php echo wp_kses_post($templateService->settingService->get_tooltip_html( $data )); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped  --  the following require is safe because it is not a user input. ?>
		</label>
	</th>
	<td class="forminp">
		<fieldset>
			<input name="<?php echo esc_attr( $key ); ?>" class="input-text regular-input" type="color"
				value="<?php echo esc_attr( $value ); ?>">
		</fieldset>
	</td>
</tr>
