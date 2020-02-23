<?php

namespace FernleafSystems\Wordpress\Plugin\Shield\Modules\License\Lib;

use FernleafSystems\Wordpress\Plugin\Shield\License\EddLicenseVO;
use FernleafSystems\Wordpress\Plugin\Shield\Modules\ModConsumer;
use FernleafSystems\Wordpress\Services\Services;

class LicenseHandler {

	use ModConsumer;

	/**
	 * @return $this
	 */
	public function clearLicense() {
		$this->getOptions()->setOpt( 'license_data', [] );
		return $this;
	}

	/**
	 * @return EddLicenseVO
	 */
	public function getLicense() {
		$aData = $this->getOptions()->getOpt( 'license_data', [] );
		return ( new EddLicenseVO() )->applyFromArray( is_array( $aData ) ? $aData : [] );
	}

	/**
	 * @return int
	 */
	protected function getLicenseActivatedAt() {
		return $this->getOptions()->getOpt( 'license_activated_at' );
	}

	/**
	 * @return int
	 */
	protected function getLicenseDeactivatedAt() {
		return $this->getOptions()->getOpt( 'license_deactivated_at' );
	}

	/**
	 * IMPORTANT: Method used by Shield Central. Modify with care.
	 * We test various data points:
	 * 1) the key is valid format
	 * 2) the official license status is 'valid'
	 * 3) the license is marked as "active"
	 * 4) the license hasn't expired
	 * 5) the time since the last check hasn't expired
	 * @return bool
	 */
	public function hasValidWorkingLicense() {
		$oLic = $this->getLicense();
		return $oLic->isValid() && $this->isLicenseActive();
	}

	/**
	 */
	public function deactivate() {
		if ( $this->isLicenseActive() ) {
			$this->clearLicense();
			$this->getOptions()->setOptAt( 'license_deactivated_at' );
			$this->sendLicenseDeactivatedEmail();
			$this->getCon()->fireEvent( 'lic_fail_deactivate' );
		}
		// force all options to resave i.e. reset premium to defaults.
		add_filter( $this->getCon()->prefix( 'force_options_resave' ), '__return_true' );
	}

	/**
	 * @return int
	 */
	public function getLicenseNotCheckedForInterval() {
		return Services::Request()->ts() - $this->getOptions()->getOpt( 'license_last_checked_at' );
	}

	/**
	 * @return bool
	 */
	public function isLicenseActive() {
		return ( $this->getLicenseActivatedAt() > 0 )
			   && ( $this->getLicenseDeactivatedAt() < $this->getLicenseActivatedAt() );
	}

	/**
	 * @return bool
	 */
	public function isLastVerifiedExpired() {
		return ( Services::Request()->ts() - $this->getLicense()->last_verified_at )
			   > $this->getOptions()->getDef( 'lic_verify_expire_days' )*DAY_IN_SECONDS;
	}

	/**
	 * @return bool
	 */
	public function isLastVerifiedGraceExpired() {
		$oOpts = $this->getOptions();
		$nGracePeriod = ( $oOpts->getDef( 'lic_verify_expire_days' )
						  + $oOpts->getDef( 'lic_verify_expire_grace_days' ) )*DAY_IN_SECONDS;
		return ( Services::Request()->ts() - $this->getLicense()->last_verified_at ) > $nGracePeriod;
	}

	/**
	 * @return bool
	 */
	public function isWithinVerifiedGraceExpired() {
		return $this->isLastVerifiedExpired() && !$this->isLastVerifiedGraceExpired();
	}

	/**
	 * Use the grace period (currently 3 days) to adjust when the license registration
	 * expires on this site. We consider a registration as expired if the last verified
	 * date is past, or the actual license is expired - whichever happens earlier -
	 * plus the grace period.
	 * @return int
	 */
	public function getRegistrationExpiresAt() {
		/** @var \ICWP_WPSF_FeatureHandler_License $oMod */
		$oMod = $this->getMod();
		$oOpts = $this->getOptions();

		$nVerifiedExpiredDays = $oOpts->getDef( 'lic_verify_expire_days' )
								+ $oOpts->getDef( 'lic_verify_expire_grace_days' );

		$oLic = $oMod->getLicenseHandler()->getLicense();
		return (int)min(
			$oLic->getExpiresAt() + $oOpts->getDef( 'lic_verify_expire_grace_days' )*DAY_IN_SECONDS,
			$oLic->last_verified_at + $nVerifiedExpiredDays*DAY_IN_SECONDS
		);
	}

	public function sendLicenseWarningEmail() {
		/** @var \ICWP_WPSF_FeatureHandler_License $oMod */
		$oMod = $this->getMod();
		$oOpts = $this->getOptions();

		$bCanSend = Services::Request()
							->carbon()
							->subDay( 1 )->timestamp > $oOpts->getOpt( 'last_warning_email_sent_at' );

		if ( $bCanSend ) {
			$oOpts->setOptAt( 'last_warning_email_sent_at' );
			$oMod->saveModOptions();

			$aMessage = [
				__( 'Attempts to verify Shield Pro license has just failed.', 'wp-simple-firewall' ),
				sprintf( __( 'Please check your license on-site: %s', 'wp-simple-firewall' ), $oMod->getUrl_AdminPage() ),
				sprintf( __( 'If this problem persists, please contact support: %s', 'wp-simple-firewall' ), 'https://support.onedollarplugin.com/' )
			];
			$oMod->getEmailProcessor()
				 ->sendEmailWithWrap(
					 $oMod->getPluginDefaultRecipientAddress(),
					 'Pro License Check Has Failed',
					 $aMessage
				 );
			$this->getCon()->fireEvent( 'lic_fail_email' );
		}
	}

	public function sendLicenseDeactivatedEmail() {
		/** @var \ICWP_WPSF_FeatureHandler_License $oMod */
		$oMod = $this->getMod();
		$oOpts = $this->getOptions();

		$bCanSend = Services::Request()
							->carbon()
							->subDay( 1 )->timestamp > $oOpts->getOpt( 'last_deactivated_email_sent_at' );

		if ( $bCanSend ) {
			$oOpts->setOptAt( 'last_deactivated_email_sent_at' );
			$oMod->saveModOptions();

			$aMessage = [
				__( 'All attempts to verify Shield Pro license have failed.', 'wp-simple-firewall' ),
				sprintf( __( 'Please check your license on-site: %s', 'wp-simple-firewall' ), $oMod->getUrl_AdminPage() ),
				sprintf( __( 'If this problem persists, please contact support: %s', 'wp-simple-firewall' ), 'https://support.onedollarplugin.com/' )
			];
			$oMod->getEmailProcessor()
				 ->sendEmailWithWrap(
					 $oMod->getPluginDefaultRecipientAddress(),
					 '[Action May Be Required] Pro License Has Been Deactivated',
					 $aMessage
				 );
		}
	}

	/**
	 * License check normally only happens when the verification_at expires (~3 days)
	 * for a currently valid license.
	 * @param bool $bForceCheck
	 * @return $this
	 */
	public function verify( $bForceCheck = true ) {
		$bCheckRequired = $this->isLicenseCheckRequired() && $this->canLicenseCheck();
		if ( $bForceCheck || $bCheckRequired ) {
			( new Verify() )
				->setMod( $this->getMod() )
				->run();
		}
		return $this;
	}

	/**
	 * @return bool
	 */
	private function canLicenseCheck() {
		return !in_array( $this->getCon()->getShieldAction(), [ 'keyless_handshake', 'license_check' ] )
			   && $this->getIsLicenseNotCheckedFor( 20 )
			   && $this->canLicenseCheck_FileFlag();
	}

	/**
	 * @return bool
	 */
	private function isLicenseCheckRequired() {
		return ( $this->isLicenseMaybeExpiring() && $this->getIsLicenseNotCheckedFor( HOUR_IN_SECONDS*4 ) )
			   || ( $this->isLicenseActive()
					&& !$this->getLicense()->isReady() && $this->getIsLicenseNotCheckedFor( HOUR_IN_SECONDS ) )
			   || ( $this->hasValidWorkingLicense() && $this->isLastVerifiedExpired()
					&& $this->getIsLicenseNotCheckedFor( HOUR_IN_SECONDS*4 ) );
	}

	/**
	 * @return bool
	 */
	private function isLicenseMaybeExpiring() {
		return $this->isLicenseActive() &&
			   (
				   abs( Services::Request()->ts() - $this->getLicense()->getExpiresAt() )
				   < ( DAY_IN_SECONDS/2 )
			   );
	}

	/**
	 * @param int $nTimePeriod
	 * @return bool
	 */
	private function getIsLicenseNotCheckedFor( $nTimePeriod ) {
		return $this->getLicenseNotCheckedForInterval() > $nTimePeriod;
	}

	/**
	 * @return bool
	 */
	private function canLicenseCheck_FileFlag() {
		$oFs = Services::WpFs();
		$sFileFlag = $this->getCon()->getPath_Flags( 'license_check' );
		$nMtime = $oFs->exists( $sFileFlag ) ? $oFs->getModifiedTime( $sFileFlag ) : 0;
		return ( Services::Request()->ts() - $nMtime ) > MINUTE_IN_SECONDS;
	}
}
