<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly ?>
<tr valign="top">
    <th scope="row" class="titledesc">
        <label><?php echo wp_kses_post( $data['title'] ); ?> Value</label>
		<?php
		echo wp_kses_post( $templateService->settingService->get_tooltip_html( $data ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped  --  the following require is safe because it is not a user input.
		?>
    </th>
    <td class="forminp">
        <fieldset>
            Min: <input name="<?php echo esc_attr( $keys['min'] ); ?>" class="input-text regular-input paydock"
                        type="text"
                        value="<?php echo esc_attr( $value['min'] ); ?>">
            Max: <input name="<?php echo esc_attr( $keys['max'] ); ?>" class="input-text regular-input paydock"
                        type="text"
                        value="<?php echo esc_attr( $value['max'] ); ?>">
        </fieldset>
    </td>
</tr>
