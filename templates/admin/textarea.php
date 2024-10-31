<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly ?>
<tr valign="top">
	<th scope="row" class="titledesc">
		<label for="<?php echo esc_attr( $field_key ); ?>">
			<?php echo wp_kses_post( $data['title'] ); ?>
			<?php echo wp_kses_post($templateService->settingService->get_tooltip_html( $data )); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped  --  the following require is safe because it is not a user input. ?>
		</label>
	</th>
	<td class="forminp">
		<fieldset>
			<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
			<textarea rows="16" cols="20" class="input-text regular-input <?php echo esc_attr( $data['class'] ); ?>"
				type="<?php echo esc_attr( $data['type'] ); ?>" name="<?php echo esc_attr( $field_key ); ?>"
				id="<?php echo esc_attr( $field_key ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>"
				placeholder="<?php echo esc_attr( $data['placeholder'] ); ?>" <?php disabled( $data['disabled'], true ); ?>
				<?php echo wp_kses_post($templateService->settingService->get_custom_attribute_html( $data )); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped  --  the following require is safe because it is not a user input. ?>
				><?php echo esc_textarea( $templateService->settingService->get_option( $key ) ); ?></textarea>
			<?php echo wp_kses_post($templateService->settingService->get_description_html( $data )); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped  --  the following require is safe because it is not a user input. ?>
		</fieldset>
	</td>
</tr>
