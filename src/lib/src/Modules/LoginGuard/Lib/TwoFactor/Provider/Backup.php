<?php

namespace FernleafSystems\Wordpress\Plugin\Shield\Modules\LoginGuard\Lib\TwoFactor\Provider;

use FernleafSystems\Wordpress\Plugin\Shield\Modules\LoginGuard;
use FernleafSystems\Wordpress\Services\Services;

class Backup extends BaseProvider {

	const SLUG = 'backupcode';
	const STANDALONE = false;

	/**
	 * @inheritDoc
	 */
	public function renderUserProfileOptions( \WP_User $user ) {
		$oCon = $this->getCon();

		$aData = [
			'strings' => [
				'button_gen_code'       => __( 'Generate ONE-Time Backup 2FA Login Code', 'wp-simple-firewall' ),
				'button_del_code'       => __( 'Delete Login Backup Code', 'wp-simple-firewall' ),
				'not_available'         => __( 'Backup login codes are not available if you do not have any other two-factor authentication modes active.', 'wp-simple-firewall' ),
				'description_code'      => __( 'Click to generate a backup login code for your two-factor authentication.', 'wp-simple-firewall' ),
				'description_code_ext1' => sprintf( '%s: %s',
					__( 'Important', 'wp-simple-firewall' ),
					__( 'This code will be displayed only once and you may use it to verify your login only once.', 'wp-simple-firewall' )
					.' '.__( 'Store it somewhere safe.', 'wp-simple-firewall' ) ),
				'description_code_ext2' => __( 'Generating a new code will replace your existing code.', 'wp-simple-firewall' ),
				'label_enter_code'      => __( 'Create Backup 2FA Login Code', 'wp-simple-firewall' ),
				'title'                 => __( 'Backup Login Code', 'wp-simple-firewall' ),
				'cant_add_other_user'   => sprintf( __( "Sorry, %s may not be added to another user's account.", 'wp-simple-firewall' ), 'Backup Codes' ),
				'cant_remove_admins'    => sprintf( __( "Sorry, %s may only be removed from another user's account by a Security Administrator.", 'wp-simple-firewall' ), __( 'Backup Codes', 'wp-simple-firewall' ) ),
				'provided_by'           => sprintf( __( 'Provided by %s', 'wp-simple-firewall' ), $oCon->getHumanName() ),
				'remove_more_info'      => sprintf( __( 'Understand how to remove Google Authenticator', 'wp-simple-firewall' ) )
			]
		];

		return $this->getMod()
					->renderTemplate(
						'/snippets/user/profile/mfa/mfa_backup.twig',
						Services::DataManipulation()->mergeArraysRecursive( $this->getCommonData( $user ), $aData ),
						true
					);
	}

	/**
	 * @inheritDoc
	 */
	public function renderUserEditProfileOptions( \WP_User $user ) {
		// Allow no actions to be taken on other user profiles
	}

	/**
	 * @return array
	 */
	public function getFormField() {
		return [
			'name'        => $this->getLoginFormParameter(),
			'type'        => 'text',
			'value'       => '',
			'placeholder' => __( 'Please use your Backup Code to login.', 'wp-simple-firewall' ),
			'text'        => __( 'Login Backup Code', 'wp-simple-firewall' ),
			'help_link'   => '',
		];
	}

	/**
	 * @param \WP_User $user
	 * @return bool
	 */
	public function hasValidatedProfile( $user ) {
		return $this->hasValidSecret( $user );
	}

	/**
	 * @param \WP_User $user
	 * @return $this
	 */
	public function postSuccessActions( \WP_User $user ) {
		$this->deleteSecret( $user );
		$this->sendBackupCodeUsedEmail( $user );
		return $this;
	}

	/**
	 * Backup Code are 1-time only and if you have MFA, then we need to remove all the other tracking factors
	 * @param \WP_User $user
	 * @param string   $otp
	 * @return bool
	 */
	protected function processOtp( \WP_User $user, $otp ) {
		return $this->validateBackupCode( $user, $otp );
	}

	/**
	 * @param \WP_User $user
	 * @param string   $sOtpCode
	 * @return bool
	 */
	private function validateBackupCode( $user, $sOtpCode ) {
		return wp_check_password( str_replace( '-', '', $sOtpCode ), $this->getSecret( $user ) );
	}

	/**
	 * @param \WP_User $user
	 * @param bool     $bIsSuccess
	 */
	protected function auditLogin( \WP_User $user, $bIsSuccess ) {
		$this->getCon()->fireEvent(
			$bIsSuccess ? '2fa_backupcode_verified' : '2fa_backupcode_fail',
			[
				'audit' => [
					'user_login' => $user->user_login,
					'method'     => 'Backup Code',
				]
			]
		);
	}

	/**
	 * @param \WP_User $user
	 * @return string
	 */
	protected function genNewSecret( \WP_User $user ) {
		return wp_generate_password( 25, false );
	}

	/**
	 * @return bool
	 */
	public function isProviderEnabled() {
		/** @var LoginGuard\Options $oOpts */
		$oOpts = $this->getOptions();
		return $oOpts->isEnabledBackupCodes();
	}

	/**
	 * @param \WP_User $user
	 * @param string   $sNewSecret
	 * @return $this
	 */
	protected function setSecret( $user, $sNewSecret ) {
		parent::setSecret( $user, wp_hash_password( $sNewSecret ) );
		return $this;
	}

	/**
	 * @param \WP_User $user
	 */
	private function sendBackupCodeUsedEmail( \WP_User $user ) {
		$aEmailContent = [
			__( 'This is a quick notice to inform you that your Backup Login code was just used.', 'wp-simple-firewall' ),
			__( "Your WordPress account had only 1 backup login code.", 'wp-simple-firewall' )
			.' '.__( "You must go to your profile and regenerate a new code if you want to use this method again.", 'wp-simple-firewall' ),
			'',
			sprintf( '<strong>%s</strong>', __( 'Login Details', 'wp-simple-firewall' ) ),
			sprintf( '%s: %s', __( 'URL', 'wp-simple-firewall' ), Services::WpGeneral()->getHomeUrl() ),
			sprintf( '%s: %s', __( 'Username', 'wp-simple-firewall' ), $user->user_login ),
			sprintf( '%s: %s', __( 'IP Address', 'wp-simple-firewall' ), Services::IP()->getRequestIp() ),
			'',
			__( 'Thank You.', 'wp-simple-firewall' ),
		];

		$sTitle = sprintf( __( "Notice: %s", 'wp-simple-firewall' ), __( "Backup Login Code Just Used", 'wp-simple-firewall' ) );
		$this->getMod()
			 ->getEmailProcessor()
			 ->sendEmailWithWrap( $user->user_email, $sTitle, $aEmailContent );
	}
}