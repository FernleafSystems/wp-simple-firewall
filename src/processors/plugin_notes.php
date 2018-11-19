<?php

if ( class_exists( 'ICWP_WPSF_Processor_Plugin_Notes' ) ) {
	return;
}

require_once( dirname( __FILE__ ).'/basedb.php' );

class ICWP_WPSF_Processor_Plugin_Notes extends ICWP_WPSF_BaseDbProcessor {

	/**
	 * @param ICWP_WPSF_FeatureHandler_Plugin $oModCon
	 */
	public function __construct( ICWP_WPSF_FeatureHandler_Plugin $oModCon ) {
		parent::__construct( $oModCon, $oModCon->getDbNameNotes() );
	}

	public function run() {
	}

	/**
	 * @return string
	 */
	public function getCreateTableSql() {
		$sSqlTables = "CREATE TABLE %s (
			id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			wp_username varchar(255) NOT NULL DEFAULT 'unknown',
			note TEXT,
			created_at int(15) UNSIGNED NOT NULL DEFAULT 0,
			deleted_at int(15) UNSIGNED NOT NULL DEFAULT 0,
 			PRIMARY KEY  (id)
		) %s;";
		return sprintf( $sSqlTables, $this->getTableName(), $this->loadDbProcessor()->getCharCollate() );
	}

	/**
	 * @return array
	 */
	protected function getTableColumnsByDefinition() {
		$aDef = $this->getMod()->getDef( 'db_notes_table_columns' );
		return ( is_array( $aDef ) ? $aDef : array() );
	}

	/**
	 * @return \FernleafSystems\Wordpress\Plugin\Shield\Databases\AdminNotes\Insert
	 */
	public function getQueryInserter() {
		return ( new \FernleafSystems\Wordpress\Plugin\Shield\Databases\AdminNotes\Insert() )
			->setTable( $this->getTableName() );
	}

	/**
	 * @return \FernleafSystems\Wordpress\Plugin\Shield\Databases\AdminNotes\Delete
	 */
	public function getQueryDeleter() {
		return ( new \FernleafSystems\Wordpress\Plugin\Shield\Databases\AdminNotes\Delete() )
			->setTable( $this->getTableName() );
	}

	/**
	 * @return \FernleafSystems\Wordpress\Plugin\Shield\Databases\AdminNotes\Select
	 */
	public function getQuerySelector() {
		return ( new \FernleafSystems\Wordpress\Plugin\Shield\Databases\AdminNotes\Select() )
			->setTable( $this->getTableName() )
			->setResultsAsVo( true );
	}

	/**
	 * @return string
	 */
	protected function queryGetDir() {
		return parent::queryGetDir().'notes/';
	}
}