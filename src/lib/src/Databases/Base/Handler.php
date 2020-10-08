<?php

namespace FernleafSystems\Wordpress\Plugin\Shield\Databases\Base;

use FernleafSystems\Wordpress\Plugin\Shield\Modules\ModConsumer;
use FernleafSystems\Wordpress\Services\Services;

/**
 * Class Handler
 * @package FernleafSystems\Wordpress\Plugin\Shield\Databases\Base
 */
abstract class Handler {

	use ModConsumer;

	/**
	 * The defined table columns.
	 * @var array
	 */
	protected $aColDef;

	/**
	 * The actual table columns.
	 * @var array
	 */
	protected $aColActual;

	/**
	 * @var string
	 */
	protected $sTable;

	/**
	 * @var bool
	 */
	private $bIsReady;

	/**
	 * @var TableBuilder
	 */
	private $builder;

	public function __construct() {
	}

	public function autoCleanDb() {
	}

	/**
	 * @param int $nAutoExpireDays
	 * @return $this
	 */
	public function tableCleanExpired( $nAutoExpireDays ) {
		$nAutoExpire = $nAutoExpireDays*DAY_IN_SECONDS;
		if ( $nAutoExpire > 0 ) {
			$this->deleteRowsOlderThan( Services::Request()->ts() - $nAutoExpire );
		}
		return $this;
	}

	/**
	 * @param int $nTimeStamp
	 * @return bool
	 */
	public function deleteRowsOlderThan( $nTimeStamp ) {
		return $this->isReady() && $this->getQueryDeleter()
										->addWhereOlderThan( $nTimeStamp )
										->query();
	}

	/**
	 * @return string[]
	 */
	public function getColumnsActual() {
		if ( empty( $this->aColActual ) ) {
			$this->aColActual = Services::WpDb()->getColumnsForTable( $this->getTable(), 'strtolower' );
		}
		return is_array( $this->aColActual ) ? $this->aColActual : [];
	}

	public function getTable() :string {
		return Services::WpDb()->getPrefix()
			   .esc_sql( $this->getCon()->prefixOption( $this->getTableSlug() ) );
	}

	/**
	 * @return string
	 */
	protected function getTableSlug() {
		return empty( $this->sTable ) ? $this->getDefaultTableName() : $this->sTable;
	}

	/**
	 * @return Insert|mixed
	 */
	public function getQueryInserter() {
		$sClass = $this->getNamespace().'Insert';
		/** @var Insert $o */
		$o = new $sClass();
		return $o->setDbH( $this );
	}

	/**
	 * @return Delete|mixed
	 */
	public function getQueryDeleter() {
		$sClass = $this->getNamespace().'Delete';
		/** @var Delete $o */
		$o = new $sClass();
		return $o->setDbH( $this );
	}

	/**
	 * @return Select|mixed
	 */
	public function getQuerySelector() {
		$sClass = $this->getNamespace().'Select';
		/** @var Select $o */
		$o = new $sClass();
		return $o->setDbH( $this )
				 ->setResultsAsVo( true );
	}

	/**
	 * @return Update|mixed
	 */
	public function getQueryUpdater() {
		$sClass = $this->getNamespace().'Update';
		/** @var Update $o */
		$o = new $sClass();
		return $o->setDbH( $this );
	}

	/**
	 * @return EntryVO|mixed
	 */
	public function getVo() {
		$sClass = $this->getNamespace().'EntryVO';
		return new $sClass();
	}

	/**
	 * @return string
	 */
	public function getSqlCreate() {
		return $this->getDefaultCreateTableSql();
	}

	/**
	 * @param string $sCol
	 * @return bool
	 */
	public function hasColumn( $sCol ) {
		return in_array( strtolower( $sCol ), array_map( 'strtolower', $this->getColumnsActual() ) );
	}

	/**
	 * @return $this
	 * @throws \Exception
	 */
	protected function tableCreate() {
		$this->getTableBuilder()->create();
		return $this;
	}

	/**
	 * @param bool $bTruncate
	 * @return bool
	 */
	public function tableDelete( $bTruncate = false ) {
		$table = $this->getTable();
		$DB = Services::WpDb();
		$mResult = !$this->tableExists() ||
				   ( $bTruncate ? $DB->doTruncateTable( $table ) : $DB->doDropTable( $table ) );
		$this->reset();
		return $mResult !== false;
	}

	/**
	 * @return bool
	 */
	public function tableExists() {
		return Services::WpDb()->getIfTableExists( $this->getTable() );
	}

	/**
	 * @return $this
	 * @throws \Exception
	 */
	public function tableInit() {
		if ( !$this->isReady() ) {

			$this->tableCreate();

			if ( !$this->isReady( true ) ) {
				$this->tableDelete();
				$this->tableCreate();
			}
		}
		return $this;
	}

	/**
	 * @param int $nRowsLimit
	 * @return $this;
	 */
	public function tableTrimExcess( $nRowsLimit ) {
		try {
			$this->getQueryDeleter()
				 ->deleteExcess( $nRowsLimit );
		}
		catch ( \Exception $oE ) {
		}
		return $this;
	}

	/**
	 * @param bool $bReTest
	 * @return bool
	 */
	public function isReady( $bReTest = false ) {
		if ( $bReTest ) {
			$this->reset();
		}

		if ( !isset( $this->bIsReady ) ) {
			try {
				$this->bIsReady = $this->tableExists() && $this->verifyTableStructure();
			}
			catch ( \Exception $e ) {
				$this->bIsReady = false;
			}
		}

		return $this->bIsReady;
	}

	/**
	 * @param string[] $aColumns
	 * @return $this
	 */
	public function setColumnsDefinition( $aColumns ) {
		$this->aColDef = $aColumns;
		return $this;
	}

	public function setTable( string $sTable ) :self {
		$this->sTable = $sTable;
		return $this;
	}

	/**
	 * @return string[]
	 */
	public function getColumns() :array {
		return array_keys( $this->getColumnsDefinition() );
	}

	protected function getDefaultCreateTableSql() :string {
		throw new \Exception( 'No SQL' );
	}

	protected function getDefaultTableName() :string {
		throw new \Exception( 'No table name' );
	}

	/**
	 * @return string[]
	 */
	public function getColumnsDefinition() :array {
		return $this->getTableBuilder()->enumerateColumns();
	}

	/**
	 * @return string[]
	 */
	protected function getCustomColumns() :array {
		return [];
	}

	/**
	 * @return string[]
	 */
	protected function getTimestampColumns() :array {
		return [];
	}

	protected function getTableBuilder() :TableBuilder {
		$builder = new TableBuilder();
		$builder->table = $this->getTable();
		$builder->cols_custom = $this->getCustomColumns();
		$builder->cols_timestamps = $this->getTimestampColumns();
		return $builder;
	}

	/**
	 * @return bool
	 * @throws \Exception
	 */
	protected function verifyTableStructure() {
		$aColDef = array_map( 'strtolower', $this->getColumns() );
		if ( empty( $aColDef ) ) {
			throw new \Exception( 'Could not verify table structure as no columns definition provided' );
		}

		$aColActual = $this->getColumnsActual();
		return ( count( array_diff( $aColActual, $aColDef ) ) <= 0
				 && ( count( array_diff( $aColDef, $aColActual ) ) <= 0 ) );
	}

	/**
	 * @return string
	 */
	private function getNamespace() {
		try {
			$sNS = ( new \ReflectionClass( $this ) )->getNamespaceName();
		}
		catch ( \Exception $oE ) {
			$sNS = __NAMESPACE__;
		}
		return rtrim( $sNS, '\\' ).'\\';
	}

	/**
	 * @return $this
	 */
	private function reset() {
		unset( $this->bIsReady );
		unset( $this->aColActual );
		return $this;
	}

	/**
	 * @return string[]
	 * @deprecated 10.0
	 */
	public function enumerateColumns() :array {
		return $this->getTableBuilder()->enumerateColumns();
	}
}