<?php

namespace Paydock\Abstracts;

use Paydock\Contracts\Repository;
use Paydock\PaydockPlugin;

abstract class AbstractRepository implements Repository {
	protected $wordpressDB;
	protected $tablePrefix;

	public function __construct() {
		global $table_prefix, $wpdb;

		$this->wordpressDB = $wpdb;
		$this->tablePrefix = $table_prefix;
	}

	public function createTable(): void {
		require_once ( ABSPATH . 'wp-admin/includes/upgrade.php' );

		dbDelta( $this->getTableDeclaration() );
	}

	abstract protected function getTableDeclaration(): string;

	public function dropTable(): void {
		$this->wordpressDB->query( 'DROP TABLE IF EXISTS ' . $this->getFullTableName( $this->table ) );
	}

	public function getFullTableName( string $table ): string {
		return $this->tablePrefix . PaydockPlugin::PLUGIN_PREFIX . '_' . $table;
	}
}
