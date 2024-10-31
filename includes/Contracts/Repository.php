<?php

namespace Paydock\Contracts;

interface Repository {
	public const INDEX_POSTFIX = 'index';

	public function getFullTableName( string $table ): string;

	public function createTable(): void;

	public function dropTable(): void;
}
