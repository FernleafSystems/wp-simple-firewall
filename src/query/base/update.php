<?php

if ( class_exists( 'ICWP_WPSF_Query_BaseUpdate', false ) ) {
	return;
}

require_once( dirname( __FILE__ ).'/insert.php' );

/**
 * @deprecated
 */
class ICWP_WPSF_Query_BaseUpdate extends ICWP_WPSF_Query_BaseInsert {

	/**
	 * @var array
	 */
	protected $aUpdateWheres;

	/**
	 * @return array
	 */
	public function getUpdateData() {
		return $this->getInsertData();
	}

	/**
	 * @return array
	 */
	public function getUpdateWheres() {
		return is_array( $this->aUpdateWheres ) ? $this->aUpdateWheres : array();
	}

	/**
	 * @param array $aSetData
	 * @return $this
	 */
	public function setUpdateData( $aSetData ) {
		return $this->setInsertData( $aSetData );
	}

	/**
	 * @param array $aUpdateWheres
	 * @return $this
	 */
	public function setUpdateWheres( $aUpdateWheres ) {
		$this->aUpdateWheres = $aUpdateWheres;
		return $this;
	}

	/**
	 * @param int $nId
	 * @return $this
	 */
	public function setUpdateId( $nId ) {
		$this->aUpdateWheres = array( 'id' => $nId );
		return $this;
	}

	/**
	 * @param ICWP_WPSF_BaseEntryVO $oEntry
	 * @param array                 $aUpdateData
	 * @return bool
	 */
	public function updateEntry( $oEntry, $aUpdateData = array() ) {
		$bSuccess = $this->updateById( $oEntry->id, $aUpdateData );
		// TODO: run through update data and determine if anything actually needs updating
		if ( $bSuccess ) {
			foreach ( $aUpdateData as $sCol => $mVal ) {
				$oEntry->{$sCol} = $mVal;
			}
		}
		return $bSuccess;
	}

	/**
	 * @param int   $nId
	 * @param array $aUpdateData
	 * @return bool true is success or no update necessary
	 */
	public function updateById( $nId, $aUpdateData = array() ) {
		$bSuccess = true;

		if ( !empty( $aUpdateData ) ) {
			$mResult = $this
				->setUpdateId( $nId )
				->setUpdateData( $aUpdateData )
				->query();
			$bSuccess = $mResult === 1;
		}
		return $bSuccess;
	}

	/**
	 * @return int|false
	 */
	public function query() {
		return $this->loadDbProcessor()
					->updateRowsFromTableWhere(
						$this->getTable(),
						$this->getUpdateData(),
						$this->getUpdateWheres()
					);
	}
}