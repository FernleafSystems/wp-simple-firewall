<?php

if ( !class_exists( 'ICWP_WPSF_Processor_LoginProtect_GoogleAuthenticator', false ) ):

require_once( dirname(__FILE__).DIRECTORY_SEPARATOR.'base_wpsf.php' );

class ICWP_WPSF_Processor_LoginProtect_GoogleAuthenticator extends ICWP_WPSF_Processor_BaseWpsf {

	/**
	 * @var ICWP_WPSF_Processor_LoginProtect_Track
	 */
	private $oLoginTrack;

	/**
	 */
	public function run() {
		/** @var ICWP_WPSF_FeatureHandler_LoginProtect $oFO */
		$oFO = $this->getFeatureOptions();

		if ( $oFO->getIfUseLoginIntentPage() ) {
			add_filter( $oFO->doPluginPrefix( 'login-intent-form-fields' ), array( $this, 'addLoginIntentField' ) );
		}
		else {
			// after User has authenticated email/username/password
			add_filter( 'authenticate', array( $this, 'checkLoginForGA_Filter' ), 23, 2 );
			add_action( 'login_form', array( $this, 'printGaLoginField' ) );
		}

		add_action( 'personal_options_update', array( $this, 'handleUserProfileSubmit' ) );
		add_action( 'show_user_profile', array( $this, 'addGaOptionsToUserProfile' ) );

		if ( $this->getController()->getIsValidAdminArea( true ) ) {
			add_action( 'edit_user_profile_update', array( $this, 'handleEditOtherUserProfileSubmit' ) );
			add_action( 'edit_user_profile', array( $this, 'addGaOptionsToUserProfile' ) );
		}

		if ( $this->loadDataProcessor()->FetchGet( 'wpsf-action' ) == 'garemovalconfirm' ) {
			add_action( 'init', array( $this, 'validateUserGaRemovalLink' ), 10 );
		}
	}

	/**
	 * This MUST only ever be hooked into when the User is looking at their OWN profile, so we can use "current user"
	 * functions.  Otherwise we need to be careful of mixing up users.
	 * @param WP_User $oUser
	 */
	public function addGaOptionsToUserProfile( $oUser ) {
		/** @var ICWP_WPSF_FeatureHandler_LoginProtect $oFO */
		$oFO = $this->getFeatureOptions();
		$aData = array(
			'user_has_google_authenticator_validated' => $oFO->getHasGaValidated( $oUser ),
			'user_google_authenticator_secret' => $oFO->getGaSecret( $oUser ),
			'is_my_user_profile' => ( $oUser->ID == $this->loadWpUsersProcessor()->getCurrentWpUserId() ),
			'i_am_valid_admin' => $this->getController()->getHasPermissionToManage(),
			'user_to_edit_is_admin' => $this->loadWpUsersProcessor()->isUserAdmin( $oUser ),
			'strings' => array(
				'description_otp_code' => _wpsf__( 'Provide the current code generated by your Google Authenticator app.' ),
				'description_otp_code_ext' => _wpsf__( 'To reset this QR Code enter fake data here.' ),
				'description_chart_url' => _wpsf__( 'Use your Google Authenticator app to scan this QR code and enter the one time password below.' ),
				'description_ga_secret' => _wpsf__( 'If you have a problem with scanning the QR code enter this code manually into the app.' ),
				'description_remove_google_authenticator' => _wpsf__( 'Check the box to remove Google Authenticator login authentication.' ),
				'label_check_to_remove' => _wpsf__( 'Remove Google Authenticator' ),
				'label_enter_code' => _wpsf__( 'Google Authenticator Code' ),
				'label_ga_secret' => _wpsf__( 'Manual Code' ),
				'label_scan_qr_code' => _wpsf__( 'Scan This QR Code' ),
				'title' => _wpsf__( 'Google Authenticator' ),
				'sorry_cant_add_to_other_user' => _wpsf__( "Sorry, Google Authenticator may not be added to another user's account." ),
				'sorry_cant_remove_from_to_other_admins' => _wpsf__( "Sorry, Google Authenticator may only be removed from another user's account by a Shield Security Administrator." ),
				'provided_by' => sprintf( _wpsf__( 'Provided by %s' ), $this->getController()->getHumanName() ),
				'remove_more_info' => sprintf( _wpsf__( 'Understand how to remove Google Authenticator' ) )
			)
		);

		if ( !$aData['user_has_google_authenticator_validated'] ) {
			$sChartUrl = $this->loadGoogleAuthenticatorProcessor()->getGoogleQrChartUrl(
				$aData['user_google_authenticator_secret'],
				$oUser->get('user_login').'@'.$this->loadWpFunctionsProcessor()->getHomeUrl( true )
			);
			$aData[ 'chart_url' ] = $sChartUrl;
		}

		echo $this->getFeatureOptions()->renderTemplate( 'snippets/user_profile_googleauthenticator.php', $aData );
	}

	/**
	 * The only thing we can do is REMOVE Google Authenticator from an account that is not our own
	 * But, only admins can do this.  If Security Admin feature is enabled, then only they can do it.
	 *
	 * @param int $nSavingUserId
	 */
	public function handleEditOtherUserProfileSubmit( $nSavingUserId ) {
		/** @var ICWP_WPSF_FeatureHandler_LoginProtect $oFO */
		$oFO = $this->getFeatureOptions();
		$oDp = $this->loadDataProcessor();

		// Can only edit other users if you're admin/security-admin
		if ( $this->getController()->getHasPermissionToManage() ) {
			$oWpUsers = $this->loadWpUsersProcessor();
			$oSavingUser = $oWpUsers->getUserById( $nSavingUserId );

			$sShieldTurnOff = $oDp->FetchPost( 'shield_turn_off_google_authenticator' );
			if ( !empty( $sShieldTurnOff ) && $sShieldTurnOff == 'Y' ) {

				$bPermissionToRemoveGa = true;
				// if the current user has Google Authenticator on THEIR account, process their OTP.
				$oCurrentUser = $oWpUsers->getCurrentWpUser();
				if ( $oFO->getHasGaValidated( $oCurrentUser ) ) {
					$bPermissionToRemoveGa = $this->processUserGaOtp(
						$oCurrentUser,
						$oDp->FetchPost( 'shield_ga_otp_code' )
					);
				}

				if ( $bPermissionToRemoveGa ) {
					$this->processGaAccountRemoval( $oSavingUser );
					$this->loadAdminNoticesProcessor()
						 ->addFlashMessage(
							 _wpsf__( 'Google Authenticator was successfully removed from the account.' )
						 );
				}
				else {
					$this->loadAdminNoticesProcessor()
						 ->addFlashErrorMessage(
							 _wpsf__( 'Google Authenticator could not be removed from the account - ensure your code is correct.' )
						 );
				}
			}
		}
		else {
			// DO NOTHING EVER
		}
	}

	/**
	 * @param WP_User $oSavingUser
	 */
	protected function processGaAccountRemoval( $oSavingUser ) {
		/** @var ICWP_WPSF_FeatureHandler_LoginProtect $oFO */
		$oFO = $this->getFeatureOptions();
		$oWpUsers = $this->loadWpUsersProcessor();
		$oWpUsers->updateUserMeta( $oFO->prefixOptionKey( 'ga_validated' ), 'N', $oSavingUser->ID );
		$oWpUsers->updateUserMeta( $oFO->prefixOptionKey( 'ga_secret' ), '', $oSavingUser->ID );
	}

	/**
	 * This MUST only ever be hooked into when the User is looking at their OWN profile,
	 * so we can use "current user" functions.  Otherwise we need to be careful of mixing up users.
	 *
	 * @param int $nSavingUserId
	 */
	public function handleUserProfileSubmit( $nSavingUserId ) {
		/** @var ICWP_WPSF_FeatureHandler_LoginProtect $oFO */
		$oFO = $this->getFeatureOptions();
		$oDp = $this->loadDataProcessor();
		$oWpUsers = $this->loadWpUsersProcessor();
		$oWpNotices = $this->loadAdminNoticesProcessor();

		$oSavingUser = $oWpUsers->getUserById( $nSavingUserId );

		// If it's your own account, you CANT do anything without your OTP (except turn off via email).
		$sGaOtpCode = $oDp->FetchPost( 'shield_ga_otp_code' );
		$bCorrectGaOtp = $this->processUserGaOtp( $oSavingUser, $sGaOtpCode );

		$sMessageOtpInvalid = _wpsf__( 'Google Authenticator One Time Password (OTP) was not valid.' ).' '._wpsf__( 'Please try again.' );

		$sShieldTurnOff = $oDp->FetchPost( 'shield_turn_off_google_authenticator' );
		if ( !empty( $sShieldTurnOff ) && $sShieldTurnOff == 'Y' ) {

			if ( $bCorrectGaOtp ) {
				$this->processGaAccountRemoval( $oSavingUser );
				$this->loadAdminNoticesProcessor()
					 ->addFlashMessage(
						 _wpsf__( 'Google Authenticator was successfully removed from the account.' )
					 );
			}
			else if ( empty( $sGaOtpCode ) ) {
				// send email to confirm
				$bEmailSuccess = $this->sendEmailConfirmationGaRemoval( $oSavingUser );
				if ( $bEmailSuccess ) {
					$oWpNotices->addFlashMessage( _wpsf__( 'An email has been sent to you in order to confirm Google Authenticator removal' ) );
				}
				else {
					$oWpNotices->addFlashErrorMessage( _wpsf__( 'We tried to send an email for you to confirm Google Authenticator removal but it failed.' ) );
				}
			}
			else {
				$oWpNotices->addFlashErrorMessage( $sMessageOtpInvalid );
			}
			return;
		}

		// At this stage, if the OTP was empty, then we have no further processing to do.
		if ( empty( $sGaOtpCode ) ) {
			return;
		}

		// We're trying to validate our OTP to activate our GA
		if ( !$oFO->getHasGaValidated( $oSavingUser ) ) {

			if ( $bCorrectGaOtp ) {
				$oWpUsers->updateUserMeta( $oFO->prefixOptionKey( 'ga_validated' ), 'Y', $nSavingUserId );
				$oWpNotices->addFlashMessage( _wpsf__( 'Google Authenticator was successfully added to your account.' ) );
			}
			else {
				$oFO->resetGaSecret( $oSavingUser );
				$oWpNotices->addFlashErrorMessage( $sMessageOtpInvalid );
			}
		}
	}

	/**
	 * @param WP_User $oUser
	 * @return WP_Error|WP_User
	 */
	public function checkLoginForGA_Filter( $oUser ) {
		/** @var ICWP_WPSF_FeatureHandler_LoginProtect $oFO */
		$oFO = $this->getFeatureOptions();
		$oDp = $this->loadDataProcessor();
		$oLoginTrack = $this->getLoginTrack();

		// Mulifactor or not
		$bNeedToCheckThisFactor = $oFO->isChainedAuth() || !$this->getLoginTrack()->hasSuccessfulFactorAuth();
		$bErrorOnFailure = $bNeedToCheckThisFactor && $oLoginTrack->isFinalFactorRemainingToTrack();
		$oLoginTrack->addUnSuccessfulFactor( ICWP_WPSF_Processor_LoginProtect_Track::Factor_Google_Authenticator );

		if ( !$bNeedToCheckThisFactor || empty( $oUser ) || is_wp_error( $oUser ) ) {
			return $oUser;
		}

		$bIsUser = is_object( $oUser ) && ( $oUser instanceof WP_User );
		if ( $bIsUser && $oFO->getHasGaValidated( $oUser ) ) {

			$oError = new WP_Error();

			$sGaOtp = $oDp->FetchPost( $this->getLoginFormParameter(), '' );
			$bIsError = false;
			if ( empty( $sGaOtp ) ) {
				$bIsError = true;
				$oError->add( 'shield_google_authenticator_empty',
					_wpsf__( 'Whoops.' ).' '. _wpsf__( 'Did we forget to use the Google Authenticator?' ) );
			}
			else {
				$sGaOtp = preg_replace( '/[^0-9]/', '', $sGaOtp );
				if ( !$this->processUserGaOtp( $oUser, $sGaOtp ) ) {
					$bIsError = true;
					$oError->add( 'shield_google_authenticator_failed',
						_wpsf__( 'Oh dear.' ).' '. _wpsf__( 'Google Authenticator Code Failed.' ) );
				}
			}

			if ( $bIsError ) {
				if ( $bErrorOnFailure ) {
					$oUser = $oError;
				}
				$this->doStatIncrement( 'login.googleauthenticator.fail' );
			}
			else {
				$this->doStatIncrement( 'login.googleauthenticator.verified' );
				$oLoginTrack->addSuccessfulFactor( ICWP_WPSF_Processor_LoginProtect_Track::Factor_Google_Authenticator );
			}
		}
		return $oUser;
	}

	/**
	 * @param array $aFields
	 * @return array
	 */
	public function addLoginIntentField( $aFields ) {
		/** @var ICWP_WPSF_FeatureHandler_LoginProtect $oFO */
		$oFO = $this->getFeatureOptions();
		if ( $oFO->getHasGaValidated( $this->loadWpUsersProcessor()->getCurrentWpUser() ) ) {
			$aFields[] = $this->getGaLoginField();
		}
		return $aFields;
	}

	/**
	 */
	public function printGaLoginField() {
		echo $this->getGaLoginField();
	}

	/**
	 * @return string
	 */
	protected function getGaLoginField() {
		$sHtml =
			'<p class="shield-google-authenticator-otp">
				<label for="_%s">%s<span class="shield-ga-help-link"> [%s]</span><br /><span class="shield-ga-inline-help">(%s)</span><br />
					<input type="text" name="%s" id="_%s" class="input" value="" autocomplete="off" maxlength="6"
					onkeyup="this.value=this.value.replace(/[^\d]/g,\'\')" />
				</label>
			</p>
		';
		$sParam = $this->getLoginFormParameter();
		return sprintf( $sHtml,
			$sParam,
			_wpsf__( 'Google Authenticator Code' ),
			'<a href="http://icwp.io/wpsf42" target="_blank" style="font-weight: bolder; margin:0 3px">&#63;</a>',
			_wpsf__( 'Use only if setup on your account' ),
			$sParam,
			$sParam
		);
	}

	/**
	 * @param WP_User $oUser
	 * @return bool
	 */
	protected function sendEmailConfirmationGaRemoval( $oUser ) {
		$bSendSuccess = false;

		$aEmailContent = array();
		$aEmailContent[] = _wpsf__( 'You have requested the removal of Google Authenticator from your WordPress account.' )
			. _wpsf__( 'Please click the link below to confirm.' );
		$aEmailContent[] = $this->generateGaRemovalConfirmationLink();

		$sRecipient = $oUser->get( 'user_email' );
		if ( is_email( $sRecipient ) ) {
			$sEmailSubject = _wpsf__( 'Google Authenticator Removal Confirmation' );
			$bSendSuccess = $this->getEmailProcessor()->sendEmailTo( $sRecipient, $sEmailSubject, $aEmailContent );
		}
		return $bSendSuccess;
	}

	/**
	 */
	public function validateUserGaRemovalLink() {
		// Must be already logged in for this link to work.
		$oWpCurrentUser = $this->loadWpUsersProcessor()->getCurrentWpUser();
		if ( empty( $oWpCurrentUser )  ) {
			return;
		}

		// Session IDs must be the same
		$sSessionId = $this->loadDataProcessor()->FetchGet( 'sessionid' );
		if ( empty( $sSessionId ) || ( $sSessionId !== $this->getController()->getSessionId() ) ) {
			return;
		}

		$this->processGaAccountRemoval( $oWpCurrentUser );
		$this->loadAdminNoticesProcessor()
			 ->addFlashMessage( _wpsf__( 'Google Authenticator was successfully removed from this account.' ) );
		$this->loadWpFunctionsProcessor()->redirectToAdmin();
	}

	/**
	 * @param WP_User $oUser
	 * @param string  $sGaOtpCode
	 * @return bool
	 */
	protected function processUserGaOtp( $oUser, $sGaOtpCode ) {
		/** @var ICWP_WPSF_FeatureHandler_LoginProtect $oFO */
		$oFO = $this->getFeatureOptions();
		$bValidOtp = false;
		if ( !empty( $sGaOtpCode ) && preg_match( '#^[0-9]{6}$#', $sGaOtpCode ) ) {
			$bValidOtp = $this->loadGoogleAuthenticatorProcessor()
							  ->verifyOtp( $oFO->getGaSecret( $oUser ), $sGaOtpCode );

		}
		return $bValidOtp;
	}

	/**
	 * @return string
	 */
	protected function generateGaRemovalConfirmationLink() {
		$aQueryArgs = array(
			'wpsf-action'	=> 'garemovalconfirm',
			'sessionid'		=> $this->getController()->getSessionId()
		);
		return add_query_arg( $aQueryArgs, $this->loadWpFunctionsProcessor()->getUrl_WpAdmin() );
	}

	/**
	 * @return string
	 */
	protected function getLoginFormParameter() {
		return $this->getFeatureOptions()->prefixOptionKey( 'ga_otp' );
	}

	/**
	 * @return ICWP_WPSF_Processor_LoginProtect_Track
	 */
	public function getLoginTrack() {
		return $this->oLoginTrack;
	}

	/**
	 * @param ICWP_WPSF_Processor_LoginProtect_Track $oLoginTrack
	 * @return $this
	 */
	public function setLoginTrack( $oLoginTrack ) {
		$this->oLoginTrack = $oLoginTrack;
		return $this;
	}
}
endif;