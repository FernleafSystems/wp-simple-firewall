<?php

use FernleafSystems\Wordpress\Plugin\Shield;
use FernleafSystems\Wordpress\Plugin\Shield\Modules\CommentsFilter;
use FernleafSystems\Wordpress\Services\Services;

class ICWP_WPSF_FeatureHandler_CommentsFilter extends ICWP_WPSF_FeatureHandler_BaseWpsf {

	/**
	 * @return Shield\Modules\Plugin\Lib\Captcha\CaptchaConfigVO
	 */
	public function getCaptchaCfg() {
		$oCfg = parent::getCaptchaCfg();
		$sStyle = $this->getOpt( 'google_recaptcha_style_comments' );
		if ( $sStyle !== 'default' && $this->isPremium() ) {
			$oCfg->theme = $sStyle;
			$oCfg->invisible = $oCfg->theme == 'invisible';
		}
		return $oCfg;
	}

	protected function ensureCorrectCaptchaConfig() {
		/** @var CommentsFilter\Options $oOpts */
		$oOpts = $this->getOptions();

		$sStyle = $oOpts->getOpt( 'google_recaptcha_style_comments' );
		if ( $this->isPremium() ) {
			$oCfg = $this->getCaptchaCfg();
			if ( $oCfg->provider == $oCfg::PROV_GOOGLE_RECAP2 ) {
				if ( !$oCfg->invisible && $sStyle == 'invisible' ) {
					$oOpts->setOpt( 'google_recaptcha_style_comments', 'default' );
				}
			}
		}
		elseif ( !in_array( $sStyle, [ 'disabled', 'default' ] ) ) {
			$oOpts->setOpt( 'google_recaptcha_style_comments', 'default' );
		}
	}

	/**
	 * @return bool
	 */
	public function getApprovedMinimum() {
		return $this->getOpt( 'trusted_commenter_minimum', 1 );
	}

	/**
	 * @return string[]
	 */
	public function getTrustedRoles() {
		$aRoles = [];
		if ( $this->isPremium() ) {
			$aRoles = $this->getOpt( 'trusted_user_roles', [] );
		}
		return is_array( $aRoles ) ? $aRoles : [];
	}

	/**
	 * @param string $sOptKey
	 * @return string
	 */
	public function getTextOptDefault( $sOptKey ) {

		switch ( $sOptKey ) {
			case 'custom_message_checkbox':
				$sText = __( "I'm not a spammer.", 'wp-simple-firewall' );
				break;
			case 'custom_message_alert':
				$sText = __( "Please check the box to confirm you're not a spammer.", 'wp-simple-firewall' );
				break;
			case 'custom_message_comment_wait':
				$sText = __( "Please wait %s seconds before posting your comment.", 'wp-simple-firewall' );
				break;
			case 'custom_message_comment_reload':
				$sText = __( "Please reload this page to post a comment.", 'wp-simple-firewall' );
				break;
			default:
				$sText = parent::getTextOptDefault( $sOptKey );
				break;
		}
		return $sText;
	}

	protected function doExtraSubmitProcessing() {
		/** @var Shield\Modules\CommentsFilter\Options $oOpts */
		$oOpts = $this->getOptions();
		if ( $oOpts->getTokenExpireInterval() != 0 && $oOpts->getTokenCooldown() > $oOpts->getTokenExpireInterval() ) {
			$oOpts->resetOptToDefault( 'comments_cooldown_interval' );
			$oOpts->resetOptToDefault( 'comments_token_expire_interval' );
		}

		$aCommentsFilters = $oOpts->getOpt( 'enable_comments_human_spam_filter_items' );
		if ( empty( $aCommentsFilters ) || !is_array( $aCommentsFilters ) ) {
			$oOpts->resetOptToDefault( 'enable_comments_human_spam_filter_items' );
		}

		// clean roles
		$oOpts->setOpt( 'trusted_user_roles',
			array_unique( array_filter( array_map(
				function ( $sRole ) {
					return preg_replace( '#[^\sa-z0-9_-]#i', '', trim( strtolower( $sRole ) ) );
				},
				$this->getTrustedRoles()
			) ) )
		);

		$this->ensureCorrectCaptchaConfig();
	}

	/**
	 * @return string[]
	 */
	public function getHumanSpamFilterItems() {
		$aItems = $this->getOpt( 'enable_comments_human_spam_filter_items' );
		return is_array( $aItems ) ? $aItems : [];
	}

	/**
	 * @param array $aAllData
	 * @return array
	 */
	public function addInsightsConfigData( $aAllData ) {
		$aThis = [
			'strings'      => [
				'title' => __( 'SPAM Blocking', 'wp-simple-firewall' ),
				'sub'   => __( 'Block Bot & Human Comment SPAM', 'wp-simple-firewall' ),
			],
			'key_opts'     => [],
			'href_options' => $this->getUrl_AdminPage()
		];

		if ( !$this->isModOptEnabled() ) {
			$aThis[ 'key_opts' ][ 'mod' ] = $this->getModDisabledInsight();
		}
		else {
			$aThis[ 'key_opts' ][ 'bot' ] = [
				'name'    => __( 'Bot SPAM', 'wp-simple-firewall' ),
				'enabled' => $this->isEnabledGaspCheck() || $this->isEnabledCaptcha(),
				'summary' => ( $this->isEnabledGaspCheck() || $this->isEnabledCaptcha() ) ?
					__( 'Bot SPAM comments are blocked', 'wp-simple-firewall' )
					: __( 'There is no protection against Bot SPAM comments', 'wp-simple-firewall' ),
				'weight'  => 2,
				'href'    => $this->getUrl_DirectLinkToSection( 'section_bot_comment_spam_protection_filter' ),
			];
			$aThis[ 'key_opts' ][ 'human' ] = [
				'name'    => __( 'Human SPAM', 'wp-simple-firewall' ),
				'enabled' => $this->isEnabledHumanCheck(),
				'summary' => $this->isEnabledHumanCheck() ?
					__( 'Comments posted by humans are checked for SPAM', 'wp-simple-firewall' )
					: __( "Comments posted by humans aren't checked for SPAM", 'wp-simple-firewall' ),
				'weight'  => 1,
				'href'    => $this->getUrl_DirectLinkToSection( 'section_human_spam_filter' ),
			];
		}

		$aAllData[ $this->getSlug() ] = $aThis;
		return $aAllData;
	}

	/**
	 * @param string $sSection
	 * @return array
	 */
	protected function getSectionWarnings( $sSection ) {
		$aWarnings = [];

		switch ( $sSection ) {
			case 'section_recaptcha':
				$oP = $this->getCon()->getModule_Plugin();
				if ( !$this->getCaptchaCfg()->ready ) {
					$aWarnings[] = sprintf(
						__( 'Please remember to supply reCAPTCHA keys: %s', 'wp-simple-firewall' ),
						sprintf( '<a href="%s" target="_blank">%s</a>', $oP->getUrl_DirectLinkToSection( 'section_third_party_captcha' ), __( 'reCAPTCHA Settings' ) )
					);
				}
				break;
		}

		return $aWarnings;
	}

	/**
	 * @return bool
	 */
	public function isEnabledCaptcha() {
		return $this->isModOptEnabled() && !$this->isOpt( 'google_recaptcha_style_comments', 'disabled' )
			   && $this->getCaptchaCfg()->ready;
	}

	/**
	 * @return bool
	 */
	public function isEnabledGaspCheck() {
		return $this->isModOptEnabled() && $this->isOpt( 'enable_comments_gasp_protection', 'Y' );
	}

	/**
	 * @return bool
	 */
	public function isEnabledHumanCheck() {
		return $this->isModOptEnabled() && $this->isOpt( 'enable_comments_human_spam_filter', 'Y' );
	}

	/**
	 * @param bool $bEnabled
	 * @return $this
	 */
	public function setEnabledGasp( $bEnabled = true ) {
		return $this->setOpt( 'enable_comments_gasp_protection', $bEnabled ? 'Y' : 'N' );
	}

	/**
	 * @return string
	 */
	protected function getNamespaceBase() {
		return 'CommentsFilter';
	}

	protected function updateHandler() {
		$oFs = Services::WpFs();
		if ( $oFs->exists( $this->getSpamBlacklistFile() ) ) {
			$oFs->deleteFile( $this->getSpamBlacklistFile() );
		}
		$oOpts = $this->getOptions();
		if ( !$oOpts->isOpt( 'enable_google_recaptcha_comments', 'Y' ) ) {
			$oOpts->setOpt( 'google_recaptcha_style_comments', 'disabled' );
		}
		$this->ensureCorrectCaptchaConfig();
	}

	/**
	 * @return string
	 */
	public function getSpamBlacklistFile() {
		return $this->getCon()->getPluginCachePath( 'spamblacklist.txt' );
	}
}