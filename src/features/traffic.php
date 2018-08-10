<?php

if ( class_exists( 'ICWP_WPSF_FeatureHandler_Traffic', false ) ) {
	return;
}

require_once( dirname( __FILE__ ).'/base_wpsf.php' );

class ICWP_WPSF_FeatureHandler_Traffic extends ICWP_WPSF_FeatureHandler_BaseWpsf {

	protected function doPostConstruction() {
		parent::doPostConstruction();
		$this->loadAutoload();
	}

	/**
	 * @return bool
	 */
	protected function isReadyToExecute() {
		return parent::isReadyToExecute() && !$this->isVisitorWhitelisted();
	}

	/**
	 * @return int
	 */
	protected function getAutoCleanAge() {
		$nAutoDays = $this->getOpt( 'auto_clean' );
		if ( $nAutoDays < 1 ) {
			$this->getOptionsVo()->resetOptToDefault( 'auto_clean' );
			$nAutoDays = $this->getOpt( 'auto_clean' );
		}
		return $nAutoDays;
	}

	/**
	 * @return int
	 */
	protected function getDefaultMaxEntries() {
		return $this->getDef( 'default_max_entries' );
	}

	/**
	 * @return int
	 */
	public function getMaxEntries() {
		$nMax = (int)$this->getOpt( 'max_entries' );
		if ( $nMax < 0 ) {
			$this->getOptionsVo()
				 ->setOpt( 'max_entries', $this->getDefaultMaxEntries() );
			$nMax = $this->getOpt( 'max_entries' );
		}
		return $nMax;
	}

	/**
	 * @return string
	 */
	public function getTrafficTableName() {
		return $this->prefix( $this->getDef( 'traffic_table_name' ), '_' );
	}

	/**
	 * @return bool
	 */
	public function isLogUsers() {
		return $this->getOptIs( 'log_users', 'Y' );
	}

	/**
	 * @return array
	 */
	protected function getContentCustomActionsData() {

		return array(
			'sLiveTrafficTable' => $this->renderLiveTrafficTable(),
			'sTitle'            => _wpsf__( 'Live Traffic Viewer' ),
			'ajax'              => array(
				'render_table' => $this->getAjaxActionData( 'render_traffic_table', true )
			)
		);
	}

	/**
	 * @param array $aAjaxResponse
	 * @return array
	 */
	public function handleAuthAjax( $aAjaxResponse ) {

		if ( empty( $aAjaxResponse ) ) {
			switch ( $this->loadDP()->request( 'exec' ) ) {

				case 'render_traffic_table':
					$aAjaxResponse = $this->ajaxExec_RenderAuditTable();
					break;

				default:
					break;
			}
		}
		return parent::handleAuthAjax( $aAjaxResponse );
	}

	protected function ajaxExec_RenderAuditTable() {
		$aParams = array_intersect_key( $_POST, array_flip( array( 'paged', 'order', 'orderby' ) ) );
		return array(
			'success' => true,
			'html'    => $this->renderLiveTrafficTable( $aParams )
		);
	}

	/**
	 * @param string $sContext
	 * @param array  $aParams
	 * @return string
	 */
	protected function renderLiveTrafficTable( $aParams = array() ) {
		$oTable = $this->getTableRenderer();

		// clean any params of nonsense
		foreach ( $aParams as $sKey => $sValue ) {
			if ( preg_match( '#[^a-z0-9_]#i', $sKey ) || preg_match( '#[^a-z0-9_]#i', $sValue ) ) {
				unset( $aParams[ $sKey ] );
			}
		}

		$aParams = array_merge(
			array(
				'orderby' => 'created_at',
				'order'   => 'DESC',
				'paged'   => 1,
			),
			$aParams
		);
		$nPage = (int)$aParams[ 'paged' ];

		/** @var ICWP_WPSF_Processor_Traffic $oTrafficPro */
		$oTrafficPro = $this->loadProcessor();
		$aEntries = $oTrafficPro->getProcessorLogger()
								->getTrafficEntrySelector()
								->setPage( $nPage )
								->setOrderBy( $aParams[ 'orderby' ], $aParams[ 'order' ] )
								->setLimit( $this->getDefaultPerPage() )
								->setResultsAsVo( true )
								->query();
		$oTable->setItemEntries( $this->formatEntriesForDisplay( $aEntries ) )
			   ->setPerPage( $this->getDefaultPerPage() )
			   ->prepare_items();
		ob_start();
		$oTable->display();
		return ob_get_clean();
	}

	/**
	 * Move to table
	 * @param ICWP_WPSF_TrafficEntryVO[] $aEntries
	 * @return array
	 */
	public function formatEntriesForDisplay( $aEntries ) {
		$sYou = $this->loadIpService()->getRequestIp();

		if ( is_array( $aEntries ) ) {
			foreach ( $aEntries as $nKey => $oEntry ) {

				$aEntry = $oEntry->getRawDataAsArray();
				$aGet = $oEntry->payload_get;
				$aPost = $oEntry->payload_post;
				$aEntry[ 'ip' ] = $oEntry->ip;
				$aEntry[ 'created_at' ] = $this->loadWp()->getTimeStampForDisplay( $aEntry[ 'created_at' ] );
				$aEntry[ 'path' ] .= !empty( $aGet ) ? '?'.http_build_query( $oEntry->payload_get ) : '';
				$aEntry[ 'payload' ] = $oEntry->payload_post;
				if ( $aEntry[ 'ip' ] == $sYou ) {
					$aEntry[ 'ip' ] .= '<br /><div style="font-size: smaller;">('._wpsf__( 'You' ).')</div>';
				}

				$aEntries[ $nKey ] = $aEntry;
			}
		}
		return $aEntries;
	}

	/**
	 * @return LiveTrafficTable
	 */
	protected function getTableRenderer() {
		$this->requireCommonLib( 'Components/Tables/LiveTrafficTable.php' );
		/** @var ICWP_WPSF_Processor_Traffic $oTrafficPro */
		$oTrafficPro = $this->loadProcessor();
		$nCount = $oTrafficPro->getProcessorLogger()
							  ->getTrafficEntryCounter()
							  ->all();
		return ( new LiveTrafficTable() )->setTotalRecords( $nCount );
	}

	/**
	 * @return int
	 */
	protected function getDefaultPerPage() {
		return $this->getDef( 'default_per_page' );
	}

	/**
	 * @param array $aOptionsParams
	 * @return array
	 * @throws Exception
	 */
	protected function loadStrings_SectionTitles( $aOptionsParams ) {

		$sSectionSlug = $aOptionsParams[ 'slug' ];
		switch ( $sSectionSlug ) {

			case 'section_enable_plugin_feature_traffic' :
				$sTitle = sprintf( _wpsf__( 'Enable Module: %s' ), $this->getMainFeatureName() );
				$aSummary = array(
					sprintf( _wpsf__( 'Purpose - %s' ), _wpsf__( 'Creates and Manages User Sessions.' ) ),
					sprintf( _wpsf__( 'Recommendation - %s' ), sprintf( _wpsf__( 'Keep the %s feature turned on.' ), _wpsf__( 'User Management' ) ) )
				);
				$sTitleShort = sprintf( _wpsf__( '%s/%s Module' ), _wpsf__( 'Enable' ), _wpsf__( 'Disable' ) );
				break;

			case 'section_traffic_options' :
				$sTitle = _wpsf__( 'Live Traffic Options' );
				$aSummary = array(
					sprintf( _wpsf__( 'Purpose - %s' ), _wpsf__( 'Provides finer control over the live traffic system.' ) ),
					sprintf( _wpsf__( 'Recommendation - %s' ), sprintf( _wpsf__( 'These settings are dependent on your requirements.' ), _wpsf__( 'User Management' ) ) )
				);
				$sTitleShort = _wpsf__( 'Traffic Logging Options' );
				break;

			default:
				throw new Exception( sprintf( 'A section slug was defined but with no associated strings. Slug: "%s".', $sSectionSlug ) );
		}
		$aOptionsParams[ 'title' ] = $sTitle;
		$aOptionsParams[ 'summary' ] = ( isset( $aSummary ) && is_array( $aSummary ) ) ? $aSummary : array();
		$aOptionsParams[ 'title_short' ] = $sTitleShort;
		return $aOptionsParams;
	}

	/**
	 * @param array $aOptionsParams
	 * @return array
	 * @throws Exception
	 */
	protected function loadStrings_Options( $aOptionsParams ) {

		$sKey = $aOptionsParams[ 'key' ];
		switch ( $sKey ) {

			case 'enable_traffic' :
				$sName = sprintf( _wpsf__( 'Enable %s Module' ), $this->getMainFeatureName() );
				$sSummary = sprintf( _wpsf__( 'Enable (or Disable) The %s Module' ), $this->getMainFeatureName() );
				$sDescription = sprintf( _wpsf__( 'Un-Checking this option will completely disable the %s module.' ), $this->getMainFeatureName() );
				break;

			case 'log_users' :
				$sName = _wpsf__( 'Include Users' );
				$sSummary = _wpsf__( 'Include Traffic From Logged-In Users' );
				$sDescription = _wpsf__( 'Check this option on to include traffic requests from logged-in users.' );
				break;

			case 'auto_clean' :
				$sName = _wpsf__( 'Auto Clean' );
				$sSummary = _wpsf__( 'Enable Traffic Log Auto Cleaning' );
				$sDescription = _wpsf__( 'Requests older than the number of days specified will be automatically cleaned from the database.' );
				break;

			case 'max_entries' :
				$sName = _wpsf__( 'Max Log Length' );
				$sSummary = _wpsf__( 'Maximum Traffic Log Length To Keep' );
				$sDescription = _wpsf__( 'Automatically remove any traffic log entries when this limit is exceeded.' );
				break;

			default:
				throw new Exception( sprintf( 'An option has been defined but without strings assigned to it. Option key: "%s".', $sKey ) );
		}

		$aOptionsParams[ 'name' ] = $sName;
		$aOptionsParams[ 'summary' ] = $sSummary;
		$aOptionsParams[ 'description' ] = $sDescription;
		return $aOptionsParams;
	}
}