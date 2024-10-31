<?php

namespace Paydock\Repositories;

use Paydock\Abstracts\AbstractRepository;
use Paydock\Contracts\Repository;

class LogRepository extends AbstractRepository implements Repository {
	private const AVAILABLE_SORT = [ 
		'id',
		'created_at',
		'operation',
		'status',
	];
	public const DEFAULT = 0;
	public const SUCCESS = 1;
	public const ERROR = 2;
	public const AVAILABLE_TYPES = [ 
		self::DEFAULT ,
		self::SUCCESS,
		self::ERROR,
	];
	protected $table = 'logs';

	public function getLogs(
		int $page = 1,
		int $perPage = 50,
		string $orderBy = 'created_at',
		string $order = 'desc'
	): array {
		$page = max( $page, 1 );
		$orderBy = in_array( $orderBy, self::AVAILABLE_SORT, true ) ? $orderBy : 'created_at';
		$order = 'asc' == $order ? 'asc' : 'desc';

		$fullTableName = $this->getFullTableName( $this->table );
		$offset = ( $page - 1 ) * $perPage;

		$result = [];

		$result['data'] = $this->wordpressDB->get_results(
			"SELECT * FROM `$fullTableName` ORDER BY `$orderBy` $order LIMIT $perPage OFFSET $offset;"
		);

		$result['count'] = $this->wordpressDB->get_row(
			"SELECT COUNT(*) as `count` FROM `$fullTableName`;"
		)->count;

		$result['from'] = $result['count'] > 0 ? $offset + 1 : 0;
		$max = $page * $perPage;
		$result['to'] = $max < $result['count'] ? $max : $result['count'];
		$result['last_page'] = $result['count'] > 0 ? ceil( $result['count'] / $perPage ) : 1;
		$result['current'] = $page;
		$result['order'] = $order;
		$result['orderBy'] = $orderBy;

		return $result;
	}

	public function createLogRecord(
		string $id,
		string $operation,
		string $status,
		string $message,
		int $type = self::DEFAULT
	): void {
		if ( ! in_array( $type, self::AVAILABLE_TYPES, true ) ) {
			$type = self::DEFAULT;
		}

		$this->wordpressDB->insert(
			$this->getFullTableName( $this->table ),
			compact( 'operation', 'status', 'type', 'id', 'message' )
		);
	}

	protected function getTableDeclaration(): string {
		$fullTableName = $this->getFullTableName( $this->table );
		$indexTypeName = implode( '_', [ $fullTableName, 'type', Repository::INDEX_POSTFIX ] );
		$indexCreatedAtdName = implode( '_', [ $fullTableName, 'created_at', Repository::INDEX_POSTFIX ] );
		$indexGatewayName = implode( '_', [ $fullTableName, 'gateway', Repository::INDEX_POSTFIX ] );

		return "
			CREATE TABLE IF NOT EXISTS `$fullTableName` (
				`status` varchar(255) NOT NULL ,
				`created_at` datetime default CURRENT_TIMESTAMP,
				`operation` varchar(255) NOT NULL,
				`type` integer NOT NULL,
				`message`  varchar(255),
				`id`  varchar(255)
			);
			CREATE INDEX `$indexTypeName`
			ON `$fullTableName` (`type`);

			CREATE INDEX `$indexCreatedAtdName`
			ON `$fullTableName` (`created_at`);

			CREATE INDEX `$indexGatewayName`
			ON `$fullTableName` (`gateway`);";
	}
}
