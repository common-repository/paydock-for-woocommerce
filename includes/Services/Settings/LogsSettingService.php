<?php

namespace Paydock\Services\Settings;

use Paydock\Abstracts\AbstractSettingService;
use Paydock\Enums\SettingsTabs;
use Paydock\Repositories\LogRepository;

class LogsSettingService extends AbstractSettingService {
	public function generate_settings_html( $form_fields = [], $echo = true ): ?string {
		$page    = get_query_var( 'page_number' );
		$perPage = get_query_var( 'per_page' );
		$order   = get_query_var( 'order' );
		$orderBy = get_query_var( 'orderBy' );
		$page    = ! empty( $page ) ? sanitize_text_field( $page ) : 1;
		$perPage = ! empty( $perPage ) ? sanitize_text_field( $perPage ) : 50;
		$order   = ! empty( $order ) ? sanitize_text_field( $order ) : 'desc';
		$orderBy = ! empty( $orderBy ) ? sanitize_text_field( $orderBy ) : 'created_at';

		$tabs = $this->getTabs();
		$records = ( new LogRepository() )->getLogs( $page, $perPage, $orderBy, $order );

		if ( $echo ) {
			$this->templateService->includeAdminHtml( 'admin', compact( 'tabs', 'records' ) );
		} else {
			return $this->templateService->getAdminHtml( 'admin', compact( 'tabs', 'records' ) );
		}

		return null;
	}

	protected function getId(): string {
		return SettingsTabs::LOG()->value;
	}
}
