<?php

namespace FernleafSystems\Wordpress\Plugin\Shield\Modules\LoginGuard\Lib\TwoFactor\Provider;

use Dolondro\GoogleAuthenticator;
use FernleafSystems\Wordpress\Plugin\Shield\Modules\LoginGuard;
use FernleafSystems\Wordpress\Services\Services;

class GoogleAuth extends BaseProvider {

	const SLUG = 'ga';

	/**
	 * @var GoogleAuthenticator\Secret
	 */
	private $oWorkingSecret;

	/**
	 * @param \WP_User $user
	 * @return bool
	 */
	public function isProfileActive( \WP_User $user ) {
		return parent::isProfileActive( $user ) && $this->hasValidatedProfile( $user );
	}

	/**
	 * @inheritDoc
	 */
	public function renderUserProfileOptions( \WP_User $oUser ) {
		$oCon = $this->getCon();

		$bValidatedProfile = $this->hasValidatedProfile( $oUser );

		$aData = [
			'hrefs'   => [
				'src_chart_url' => $bValidatedProfile ? '' : $this->getGaRegisterChartUrl( $oUser ),
			],
			'vars'    => [
				'ga_secret' => $bValidatedProfile ? $this->getSecret( $oUser ) : $this->resetSecret( $oUser ),
			],
			'strings' => [
				'enter_auth_app_code'   => __( 'Enter the 6-digit code from your Authenticator App', 'wp-simple-firewall' ),
				'description_otp_code'  => __( 'Provide the current code generated by your Google Authenticator app.', 'wp-simple-firewall' ),
				'description_chart_url' => __( 'Use your Google Authenticator app to scan this QR code and enter the 6-digit one time password.', 'wp-simple-firewall' ),
				'description_ga_secret' => __( 'If you have a problem with scanning the QR code enter the long code manually into the app.', 'wp-simple-firewall' ),
				'desc_remove'           => __( 'Check the box to remove Google Authenticator login authentication.', 'wp-simple-firewall' ),
				'label_check_to_remove' => sprintf( __( 'Remove %s', 'wp-simple-firewall' ), __( 'Google Authenticator', 'wp-simple-firewall' ) ),
				'label_enter_code'      => __( 'Google Authenticator Code', 'wp-simple-firewall' ),
				'label_ga_secret'       => __( 'Manual Code', 'wp-simple-firewall' ),
				'label_scan_qr_code'    => __( 'Scan This QR Code', 'wp-simple-firewall' ),
				'title'                 => __( 'Google Authenticator', 'wp-simple-firewall' ),
				'cant_add_other_user'   => sprintf( __( "Sorry, %s may not be added to another user's account.", 'wp-simple-firewall' ), 'Google Authenticator' ),
				'cant_remove_admins'    => sprintf( __( "Sorry, %s may only be removed from another user's account by a Security Administrator.", 'wp-simple-firewall' ), __( 'Google Authenticator', 'wp-simple-firewall' ) ),
				'provided_by'           => sprintf( __( 'Provided by %s', 'wp-simple-firewall' ), $oCon->getHumanName() ),
				'remove_more_info'      => sprintf( __( 'Understand how to remove Google Authenticator', 'wp-simple-firewall' ) )
			],
		];

		return $this->getMod()
					->renderTemplate(
						'/snippets/user/profile/mfa/mfa_ga.twig',
						Services::DataManipulation()->mergeArraysRecursive( $this->getCommonData( $oUser ), $aData ),
						true
					);
	}

	/**
	 * @param \WP_User $oUser
	 * @return string
	 */
	public function getGaRegisterChartUrl( $oUser ) {
		$sUrl = '';
		if ( !empty( $oUser ) ) {
			try {
				$sUrl = ( new GoogleAuthenticator\QrImageGenerator\GoogleQrImageGenerator () )
					->generateUri(
						$this->getGaSecret( $oUser )
					);
			}
			catch ( \InvalidArgumentException $e ) {
			}
		}
		return $sUrl;
	}

	/**
	 * The only thing we can do is REMOVE Google Authenticator from an account that is not our own
	 * But, only admins can do this.  If Security Admin feature is enabled, then only they can do it.
	 * @inheritDoc
	 */
	public function handleEditOtherUserProfileSubmit( \WP_User $oUser ) {

		// Can only edit other users if you're admin/security-admin
		if ( $this->getCon()->isPluginAdmin() && Services::Request()->post( 'shield_turn_off_ga' ) === 'Y' ) {
			$this->processRemovalFromAccount( $oUser );
			$sMsg = __( 'Google Authenticator was successfully removed from the account.', 'wp-simple-firewall' );
			$this->getMod()->setFlashAdminNotice( $sMsg );
		}
	}

	/**
	 * @param \WP_User $oUser
	 * @return $this
	 */
	protected function processRemovalFromAccount( $oUser ) {
		$this->setProfileValidated( $oUser, false )
			 ->resetSecret( $oUser );
		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function handleUserProfileSubmit( \WP_User $oUser ) {
		$sOtp = $this->fetchCodeFromRequest();

		if ( Services::Request()->post( 'shield_turn_off_ga' ) === 'Y' ) {
			$sFlash = __( 'Google Authenticator was successfully removed from the account.', 'wp-simple-firewall' );
			$this->processRemovalFromAccount( $oUser );
			$this->getMod()->setFlashAdminNotice( $sFlash );
			/**
			 * $sFlash = __( 'An email has been sent to you in order to confirm Google Authenticator removal', 'wp-simple-firewall' );
			 * $sFlash = __( 'We tried to send an email for you to confirm Google Authenticator removal but it failed.', 'wp-simple-firewall' );
			 */
		}
		elseif ( !empty( $sOtp ) && !$this->hasValidatedProfile( $oUser ) ) { // Add GA to profile
			$bValidOtp = $this->processOtp( $oUser, $sOtp );
			if ( $bValidOtp ) {
				$this->setProfileValidated( $oUser );
				$sFlash = sprintf(
					__( '%s was successfully added to your account.', 'wp-simple-firewall' ),
					__( 'Google Authenticator', 'wp-simple-firewall' )
				);
			}
			else {
				$this->resetSecret( $oUser );
				$sFlash = __( 'One Time Password (OTP) was not valid.', 'wp-simple-firewall' )
						  .' '.__( 'Please try again.', 'wp-simple-firewall' );
			}
			$this->getMod()->setFlashAdminNotice( $sFlash, !$bValidOtp );
		}
	}

	/**
	 * @return array
	 */
	public function getFormField() {
		return [
			'name'        => $this->getLoginFormParameter(),
			'type'        => 'text',
			'value'       => '',
			'placeholder' => __( 'Please use your Google Authenticator App to retrieve your code.', 'wp-simple-firewall' ),
			'text'        => __( 'Google Authenticator Code', 'wp-simple-firewall' ),
			'help_link'   => 'https://shsec.io/wpsf42',
			'extras'      => [
				'onkeyup' => "this.value=this.value.replace(/[^\d]/g,'')"
			]
		];
	}

	/**
	 * @param \WP_User $user
	 * @param string   $otp
	 * @return bool
	 */
	protected function processOtp( \WP_User $user, $otp ) {
		return $this->validateGaCode( $user, $otp );
	}

	/**
	 * @param \WP_User $oUser
	 * @param string   $sOtpCode
	 * @return bool
	 */
	public function validateGaCode( $oUser, $sOtpCode ) {
		$bValidOtp = false;
		if ( preg_match( '#^[0-9]{6}$#', $sOtpCode ) ) {
			try {
				$bValidOtp = ( new GoogleAuthenticator\GoogleAuthenticator() )
					->authenticate( $this->getSecret( $oUser ), $sOtpCode );
			}
			catch ( \Exception $oE ) {
			}
			catch ( \Psr\Cache\CacheException $oE ) {
			}
		}
		return $bValidOtp;
	}

	/**
	 * @param \WP_User $oUser
	 * @param bool     $bIsSuccess
	 */
	protected function auditLogin( $oUser, $bIsSuccess ) {
		$this->getCon()->fireEvent(
			$bIsSuccess ? 'googleauth_verified' : 'googleauth_fail',
			[
				'audit' => [
					'user_login' => $oUser->user_login,
					'method'     => 'Google Authenticator',
				]
			]
		);
	}

	/**
	 * @param \WP_User $oUser
	 * @return string
	 */
	protected function genNewSecret( \WP_User $oUser ) {
		try {
			return $this->getGaSecret( $oUser )->getSecretKey();
		}
		catch ( \InvalidArgumentException $oE ) {
			return '';
		}
	}

	/**
	 * @param \WP_User $oUser
	 * @return GoogleAuthenticator\Secret
	 */
	private function getGaSecret( $oUser ) {
		if ( !isset( $this->oWorkingSecret ) ) {
			$this->oWorkingSecret = ( new GoogleAuthenticator\SecretFactory() )
				->create(
					sanitize_user( $oUser->user_login ),
					preg_replace( '#[^0-9a-z]#i', '', Services::WpGeneral()->getSiteName() )
				);
		}
		return $this->oWorkingSecret;
	}

	/**
	 * @param \WP_User $user
	 * @return string
	 */
	protected function getSecret( \WP_User $user ) {
		$sSec = parent::getSecret( $user );
		return empty( $sSec ) ? $this->resetSecret( $user ) : $sSec;
	}

	/**
	 * @param string $secret
	 * @return bool
	 */
	protected function isSecretValid( $secret ) {
		return parent::isSecretValid( $secret ) && ( strlen( $secret ) == 16 );
	}

	/**
	 * @return bool
	 */
	public function isProviderEnabled() {
		/** @var LoginGuard\Options $oOpts */
		$oOpts = $this->getOptions();
		return $oOpts->isEnabledGoogleAuthenticator();
	}
}