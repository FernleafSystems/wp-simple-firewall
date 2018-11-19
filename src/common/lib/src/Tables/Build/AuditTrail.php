<?php

namespace FernleafSystems\Wordpress\Plugin\Shield\Tables\Build;

use FernleafSystems\Wordpress\Plugin\Shield\Tables;
use FernleafSystems\Wordpress\Services\Services;

/**
 * Class AuditTrail
 * @package FernleafSystems\Wordpress\Plugin\Shield\Tables\Build
 */
class AuditTrail extends Base {

	/**
	 * Override this to apply table-specific query filters.
	 * @return $this
	 */
	protected function applyQueryFilters() {
		$aParams = $this->getParams();
		/** @var \ICWP_WPSF_Query_AuditTrail_Select $oSelector */
		$oSelector = $this->getQuerySelector();

		$oSelector->filterByContext( $aParams[ 'fContext' ] );

		$oIp = Services::IP();
		// If an IP is specified, it takes priority
		if ( $oIp->isValidIp( $aParams[ 'fIp' ] ) ) {
			$oSelector->filterByIp( $aParams[ 'fIp' ] );
		}
		else if ( $aParams[ 'fExludeYou' ] == 'Y' ) {
			$oSelector->filterByNotIp( $oIp->getRequestIp() );
		}

		// if username is provided, this takes priority over "logged-in" (even if it's invalid)
		if ( !empty( $aParams[ 'fUsername' ] ) ) {
			$oSelector->filterByUsername( $aParams[ 'fUsername' ] );
		}
		else if ( $aParams[ 'fLoggedIn' ] >= 0 ) {
			$oSelector->filterByIsLoggedIn( $aParams[ 'fLoggedIn' ] );
		}

		return $this;
	}

	/**
	 * Override to allow other parameter keys for building the table
	 * @return array
	 */
	protected function getCustomParams() {
		return array(
			'fIp'        => '',
			'fUsername'  => '',
			'fContext'   => '',
			'fLoggedIn'  => -1,
			'fExludeYou' => '',
		);
	}

	/**
	 * @return array[]
	 */
	protected function getEntriesFormatted() {
		$aEntries = array();

		$sYou = Services::IP()->getRequestIp();
		foreach ( $this->getEntriesRaw() as $nKey => $oEntry ) {
			/** @var \ICWP_WPSF_AuditTrailEntryVO $oEntry */
			$aE = $oEntry->getRawData();
			var_dump( $oEntry->message );
			$aE[ 'event' ] = str_replace( '_', ' ', sanitize_text_field( $oEntry->event ) );
			$aE[ 'message' ] = stripslashes( sanitize_text_field( $oEntry->message ) );
			$aE[ 'created_at' ] = $this->formatTimestampField( $oEntry->created_at );
			if ( $oEntry->getIp() == $sYou ) {
				$aE[ 'your_ip' ] = '<br /><small>('._wpsf__( 'Your IP' ).')</small>';
			}
			else {
				$aE[ 'your_ip' ] = '';
			}
			$aEntries[ $nKey ] = $aE;
		}
		return $aEntries;
	}

	/**
	 * @return Tables\Render\AuditTrail
	 */
	protected function getTableRenderer() {
		return new Tables\Render\AuditTrail();
	}
}