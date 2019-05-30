<?php

use FernleafSystems\Wordpress\Plugin\Shield\Databases\IPs;
use FernleafSystems\Wordpress\Plugin\Shield\Modules\IPs\BotTrack;
use FernleafSystems\Wordpress\Services\Services;

class ICWP_WPSF_Processor_Ips extends ICWP_WPSF_BaseDbProcessor {

	/* Unused */
	const LIST_MANUAL_WHITE = 'MW';
	const LIST_MANUAL_BLACK = 'MB';
	const LIST_AUTO_BLACK = 'AB';

	/**
	 * @var bool
	 */
	protected $bVisitorIsWhitelisted;

	/**
	 * @param ICWP_WPSF_FeatureHandler_Ips $oModCon
	 */
	public function __construct( ICWP_WPSF_FeatureHandler_Ips $oModCon ) {
		parent::__construct( $oModCon, $oModCon->getDef( 'ip_lists_table_name' ) );
	}

	/**
	 */
	public function run() {
		if ( !$this->isReadyToRun() ) {
			return;
		}

		$this->processBlacklist();

		/** @var ICWP_WPSF_FeatureHandler_Ips $oFO */
		$oFO = $this->getMod();
		if ( $oFO->isAutoBlackListEnabled() ) {
			add_filter( $oFO->prefix( 'firewall_die_message' ), [ $this, 'fAugmentFirewallDieMessage' ] );
			add_action( $oFO->prefix( 'pre_plugin_shutdown' ), [ $this, 'doBlackMarkCurrentVisitor' ] );
		}
	}

	public function onWpInit() {
		parent::onWpInit();
		/** @var ICWP_WPSF_FeatureHandler_Ips $oFO */
		$oFO = $this->getMod();

		if ( $this->isReadyToRun() && $oFO->isAutoBlackListEnabled() && !Services::WpUsers()->isUserLoggedIn() ) {

			if ( !$oFO->isVerifiedBot() ) {
				if ( $oFO->isEnabledTrackXmlRpc() ) {
					( new BotTrack\TrackXmlRpc() )
						->setMod( $oFO )
						->run();
				}
				if ( $oFO->isEnabledTrack404() ) {
					( new BotTrack\Track404() )
						->setMod( $oFO )
						->run();
				}
				if ( $oFO->isEnabledTrackLoginFailed() ) {
					( new BotTrack\TrackLoginFailed() )
						->setMod( $oFO )
						->run();
				}
				if ( $oFO->isEnabledTrackLoginInvalid() ) {
					( new BotTrack\TrackLoginInvalid() )
						->setMod( $oFO )
						->run();
				}
				if ( $oFO->isEnabledTrackFakeWebCrawler() ) {
					( new BotTrack\TrackFakeWebCrawler() )
						->setMod( $oFO )
						->run();
				}
			}

			/** Always run link cheese regardless of the verified bot or not */
			if ( $oFO->isEnabledTrackLinkCheese() ) {
				( new BotTrack\TrackLinkCheese() )
					->setMod( $oFO )
					->run();
			}
		}
	}

	/**
	 * @param array $aNoticeAttributes
	 * @throws \Exception
	 */
	public function addNotice_visitor_whitelisted( $aNoticeAttributes ) {
		$oCon = $this->getCon();

		if ( $oCon->getIsPage_PluginAdmin() && $this->isCurrentIpWhitelisted() ) {
			$aRenderData = [
				'notice_attributes' => $aNoticeAttributes,
				'strings'           => [
					'title'             => sprintf( __( '%s is ignoring you', 'wp-simple-firewall' ), $oCon->getHumanName() ),
					'your_ip'           => sprintf( __( 'Your IP address is: %s', 'wp-simple-firewall' ), $this->ip() ),
					'notice_message'    => __( 'Your IP address is whitelisted and NO features you activate apply to you.', 'wp-simple-firewall' ),
					'including_message' => __( 'Including the hiding the WP Login page.', 'wp-simple-firewall' )
				]
			];
			$this->insertAdminNotice( $aRenderData );
		}
	}

	/**
	 * @param array $aMessages
	 * @return array
	 */
	public function fAugmentFirewallDieMessage( $aMessages ) {
		if ( !is_array( $aMessages ) ) {
			$aMessages = [];
		}
		$aMessages[] = sprintf( '<p>%s</p>', $this->getTextOfRemainingTransgressions() );
		return $aMessages;
	}

	/**
	 * @return string
	 */
	private function getTextOfRemainingTransgressions() {
		return sprintf(
			$this->getMod()->getTextOpt( 'text_remainingtrans' ),
			$this->getRemainingTransgressions() - 1 // we take one off because it hasn't been incremented at this stage
		);
	}

	/**
	 * @param string $sIp
	 * @return string
	 */
	public function getRemainingTransgressions( $sIp = '' ) {
		/** @var ICWP_WPSF_FeatureHandler_Ips $oFO */
		$oFO = $this->getMod();
		if ( empty( $sIp ) ) {
			$sIp = $this->ip();
		}
		return $oFO->getOptTransgressionLimit() - $this->getTransgressions( $sIp );
	}

	/**
	 * The auto black list isn't a simple lookup, but rather has an auto expiration and a transgression count
	 * @param string $sIp
	 * @return int
	 */
	private function getTransgressions( $sIp ) {
		$oBlackIp = $this->getBlackListIp( $sIp );
		return ( $oBlackIp instanceof IPs\EntryVO ) ? $oBlackIp->getTransgressions() : 0;
	}

	protected function processBlacklist() {
		if ( $this->isCurrentIpWhitelisted() ) {
			return;
		}

		/** @var ICWP_WPSF_FeatureHandler_Ips $oFO */
		$oFO = $this->getMod();
		$sIp = $this->ip();
		$bKill = false;

		// TODO: *Maybe* Have a manual black list process first.

		// now try auto black list
		if ( !$bKill && $oFO->isAutoBlackListEnabled() ) {
			$bKill = $this->isIpToBeBlocked( $sIp );
		}

		if ( $bKill ) {
			$sAuditMessage = sprintf( __( 'Visitor found on the Black List and their connection was killed.', 'wp-simple-firewall' ), $sIp );
			$this->setIfLogRequest( false )// don't log traffic from killed requests
				 ->doStatIncrement( 'ip.connection.killed' )
				 ->addToAuditEntry( $sAuditMessage, 3, 'black_list_connection_killed' );
			$oFO->setOptInsightsAt( 'last_ip_block_at' );

			/** @var IPs\Update $oUp */
			$oUp = $this->getDbHandler()->getQueryUpdater();
			$oUp->updateLastAccessAt( $this->getAutoBlackListIp( $sIp ) );

			try {
				if ( $this->processAutoUnblockRequest() ) {
					return;
				}
			}
			catch ( \Exception $oE ) {
			}
			$this->renderKillPage();
		}
	}

	/**
	 * @throws \Exception
	 */
	private function processAutoUnblockRequest() {
		/** @var ICWP_WPSF_FeatureHandler_Ips $oFO */
		$oFO = $this->getMod();
		$oReq = Services::Request();

		if ( $oFO->isEnabledAutoUserRecover() && $oReq->isPost()
			 && $oReq->request( 'action' ) == $this->prefix() && $oReq->request( 'exec' ) == 'uau' ) {

			if ( check_admin_referer( $oReq->request( 'exec' ), 'exec_nonce' ) !== 1 ) {
				throw new \Exception( 'Nonce failed' );
			}
			if ( strlen( $oReq->post( 'icwp_wpsf_login_email' ) ) > 0 ) {
				throw new \Exception( 'Email should not be provided in honeypot' );
			}
			$sIp = $this->ip();
			if ( $oReq->post( 'ip' ) != $sIp ) {
				throw new \Exception( 'IP does not match' );
			}

			/** @var ICWP_WPSF_FeatureHandler_LoginProtect $oLoginFO */
			$oLoginFO = $this->getCon()->getModule( 'login_protect' );
			$sGasp = $oReq->post( $oLoginFO->getGaspKey() );
			if ( empty( $sGasp ) ) {
				throw new \Exception( 'GASP failed' );
			}

			if ( !$oFO->getCanIpRequestAutoUnblock( $sIp ) ) {
				throw new \Exception( 'IP already processed in the last 24hrs' );
			}
			$oFO->updateIpRequestAutoUnblockTs( $sIp );

			/** @var IPs\Delete $oDel */
			$oDel = $this->getDbHandler()->getQueryDeleter();
			$oDel->deleteIpFromBlacklists( $sIp );
			Services::Response()->redirectToHome();
		}

		return false;
	}

	private function renderKillPage() {

		/** @var ICWP_WPSF_FeatureHandler_Ips $oFO */
		$oFO = $this->getMod();
		$oCon = $this->getCon();
		/** @var ICWP_WPSF_FeatureHandler_LoginProtect $oLoginFO */
		$oLoginFO = $oCon->getModule( 'login_protect' );

		$sUniqId = 'uau'.uniqid();

		$sIp = $this->ip();
		$nTimeRemaining = max( floor( $oFO->getAutoExpireTime()/60 ), 0 );
		$aData = [
			'strings' => [
				'title'   => sprintf( __( "You've been blocked by the %s plugin", 'wp-simple-firewall' ),
					sprintf( '<a href="%s" target="_blank">%s</a>',
						$oCon->getPluginSpec()[ 'meta' ][ 'url_repo_home' ],
						$oCon->getHumanName()
					)
				),
				'lines'   => [
					sprintf( __( 'Time remaining on black list: %s', 'wp-simple-firewall' ),
						sprintf( _n( '%s minute', '%s minutes', $nTimeRemaining, 'wp-simple-firewall' ), $nTimeRemaining )
					),
					sprintf( __( 'You tripped the security plugin defenses a total of %s times making you a suspect.', 'wp-simple-firewall' ), $oFO->getOptTransgressionLimit() ),
					sprintf( __( 'If you believe this to be in error, please contact the site owner and quote your IP address below.', 'wp-simple-firewall' ) ),
				],
				'your_ip' => 'Your IP address',
				'unblock' => [
					'title'   => __( 'Auto-Unblock Your IP', 'wp-simple-firewall' ),
					'you_can' => __( 'You can automatically unblock your IP address by clicking the button below.', 'wp-simple-firewall' ),
					'button'  => __( 'Unblock My IP Address', 'wp-simple-firewall' ),
				],
			],
			'vars'    => [
				'nonce'        => $oFO->getNonceActionData( 'uau' ),
				'ip'           => $sIp,
				'gasp_element' => $oFO->renderTemplate(
					'snippets/gasp_js.php',
					[
						'sCbName'   => $oLoginFO->getGaspKey(),
						'sLabel'    => $oLoginFO->getTextImAHuman(),
						'sAlert'    => $oLoginFO->getTextPleaseCheckBox(),
						'sMustJs'   => __( 'You MUST enable Javascript to be able to login', 'wp-simple-firewall' ),
						'sUniqId'   => $sUniqId,
						'sUniqElem' => 'icwp_wpsf_login_p'.$sUniqId,
						'strings'   => [
							'loading' => __( 'Loading', 'wp-simple-firewall' )
						]
					]
				),
			],
			'flags'   => [
				'is_autorecover'   => $oFO->isEnabledAutoUserRecover(),
				'is_uau_permitted' => $oFO->getCanIpRequestAutoUnblock( $sIp ),
			],
		];
		Services::WpGeneral()
				->wpDie(
					$oFO->renderTemplate( '/snippets/blacklist_die.twig', $aData, true )
				);
	}

	/**
	 */
	public function doBlackMarkCurrentVisitor() {
		/** @var ICWP_WPSF_FeatureHandler_Ips $oFO */
		$oFO = $this->getMod();

		if ( $oFO->isAutoBlackListEnabled() && !$this->getCon()->isPluginDeleting()
			 && $oFO->getIfIpTransgressed() && !$oFO->isVerifiedBot() && !$this->isCurrentIpWhitelisted() ) {

			$this->processTransgression();
		}
	}

	/**
	 */
	private function processTransgression() {
		/** @var ICWP_WPSF_FeatureHandler_Ips $oFO */
		$oFO = $this->getMod();

		$oBlackIp = $this->getAutoBlackListIp( $this->ip() );
		if ( !$oBlackIp instanceof IPs\EntryVO ) {
			$oBlackIp = $this->addIpToList( $this->ip(), ICWP_WPSF_FeatureHandler_Ips::LIST_AUTO_BLACK, 'auto' );
		}

		if ( $oBlackIp instanceof IPs\EntryVO ) {
			$nLimit = $oFO->getOptTransgressionLimit();
			$nCurrentTrans = $oBlackIp->transgressions;
			// At this stage we know it's a transgression. But is it an outright block?
			$bBlock = ( $oFO->getIpAction() == 'block' ) || ( $nLimit - $nCurrentTrans == 1 );
			$nToIncrement = $bBlock ? ( $nLimit - $nCurrentTrans ) : $oFO->getIpAction();

			/** @var IPs\Update $oUp */
			$oUp = $this->getDbHandler()->getQueryUpdater();
			$oUp->incrementTransgressions( $oBlackIp, $nToIncrement );

			$oFO->setOptInsightsAt( 'last_transgression_at' );
			$this->doStatIncrement( 'ip.transgression.incremented' );

			if ( $bBlock ) {
				$oFO->setOptInsightsAt( 'last_ip_block_at' );
				$sAuditMessage = sprintf(
					__( 'IP blocked after incrementing offenses from %s to %s.', 'wp-simple-firewall' ),
					$nCurrentTrans,
					$oBlackIp->transgressions
				);
				$this->addToAuditEntry( $sAuditMessage, 2, 'ip_transgression_blocked' );
			}
			else {
				$sAuditMessage = sprintf(
					__( 'Auto Black List offenses counter was incremented from %s to %s.', 'wp-simple-firewall' ),
					$nCurrentTrans,
					$oBlackIp->transgressions
				);
				$this->addToAuditEntry( $sAuditMessage, 2, 'ip_transgression_increment' );
			}
		}
	}

	/**
	 * @return bool
	 */
	public function isCurrentIpWhitelisted() {
		if ( !isset( $this->bVisitorIsWhitelisted ) ) {
			$this->bVisitorIsWhitelisted = $this->isIpOnWhiteList( $this->ip() );
		}
		return $this->bVisitorIsWhitelisted;
	}

	/**
	 * @return IPs\EntryVO[]
	 */
	public function getWhitelistIpsData() {
		/** @var IPs\Select $oSelect */
		$oSelect = $this->getDbHandler()->getQuerySelector();
		return $oSelect->allFromList( ICWP_WPSF_FeatureHandler_Ips::LIST_MANUAL_WHITE );
	}

	/**
	 * @return string[]
	 */
	public function getWhitelistIps() {
		$aIps = [];
		foreach ( $this->getWhitelistIpsData() as $oIp ) {
			$aIps[] = $oIp->ip;
		}
		return $aIps;
	}

	/**
	 * @param string $sIp
	 * @return bool
	 */
	public function isIpOnManualBlackList( $sIp ) {
		return $this->isIpOnList( $sIp, ICWP_WPSF_FeatureHandler_Ips::LIST_MANUAL_BLACK );
	}

	/**
	 * @param string $sIp
	 * @return bool
	 */
	public function isIpOnWhiteList( $sIp ) {
		return $this->isIpOnList( $sIp, ICWP_WPSF_FeatureHandler_Ips::LIST_MANUAL_WHITE );
	}

	/**
	 * @param string $sIp
	 * @return bool|array - will return the associative array of the single row data
	 */
	public function isIpToBeBlocked( $sIp ) {
		/** @var ICWP_WPSF_FeatureHandler_Ips $oFO */
		$oFO = $this->getMod();
		$oIp = $this->getBlackListIp( $sIp );
		return ( $oIp instanceof IPs\EntryVO && $oIp->getTransgressions() >= $oFO->getOptTransgressionLimit() );
	}

	/**
	 * @param string $sIp
	 * @param string $sList
	 * @return bool
	 */
	private function isIpOnList( $sIp, $sList ) {
		$bOnList = false;

		/** @var IPs\Select $oSelect */
		$oSelect = $this->getDbHandler()->getQuerySelector();
		foreach ( $oSelect->allFromList( $sList ) as $oIp ) {
			try {
				if ( Services::IP()->checkIp( $sIp, $oIp->ip ) ) {
					$bOnList = true;
					break;
				}
			}
			catch ( \Exception $oE ) {
			}
		}

		return $bOnList;
	}

	/**
	 * @param string $sIp
	 * @param string $sLabel
	 * @return IPs\EntryVO|null
	 */
	public function addIpToWhiteList( $sIp, $sLabel = '' ) {
		return $this->addIpToManualList( $sIp, ICWP_WPSF_FeatureHandler_Ips::LIST_MANUAL_WHITE, $sLabel );
	}

	/**
	 * @param string $sIp
	 * @param string $sLabel
	 * @return IPs\EntryVO|null
	 */
	public function addIpToBlackList( $sIp, $sLabel = '' ) {
		return $this->addIpToManualList( $sIp, ICWP_WPSF_FeatureHandler_Ips::LIST_MANUAL_BLACK, $sLabel );
	}

	/**
	 * @param string $sIp
	 * @param string $sList
	 * @param string $sLabel
	 * @return IPs\EntryVO|null
	 */
	private function addIpToManualList( $sIp, $sList, $sLabel = '' ) {
		$oDbh = $this->getDbHandler();

		/** @var IPs\Select $oSelect */
		$oSelect = $oDbh->getQuerySelector();
		/** @var IPs\EntryVO $oIp */
		$oIp = $oSelect->filterByIp( $sIp )
					   ->filterByList( $sList )
					   ->first();

		if ( empty( $oIp ) ) {
			$oIp = $this->addIpToList( $sIp, $sList, $sLabel );
		}
		else if ( $sLabel != $oIp->getLabel() ) {
			/** @var IPs\Update $oUp */
			$oUp = $oDbh->getQueryUpdater();
			$oUp->updateLabel( $oIp, $sLabel );
		}
		return $oIp;
	}

	/**
	 * ADDITION OF ANY IP TO ANY LIST SHOULD GO THROUGH HERE.
	 * @param string $sIp
	 * @param string $sList
	 * @param string $sLabel
	 * @return IPs\EntryVO|null
	 */
	private function addIpToList( $sIp, $sList, $sLabel = '' ) {
		$oIp = null;

		/** @var ICWP_WPSF_FeatureHandler_Ips $oFO */
		$oFO = $this->getMod();

		// Never add a reserved IP to any black list
		if ( $sList == self::LIST_MANUAL_WHITE || !in_array( $sIp, $oFO->getReservedIps() ) ) {
			$oDbh = $this->getDbHandler();

			// delete any previous old entries as we go.
			/** @var IPs\Delete $oDel */
			$oDel = $oDbh->getQueryDeleter();
			$oDel->deleteIpOnList( $sIp, $sList );

			/** @var IPs\EntryVO $oTempIp */
			$oTempIp = $oDbh->getVo();
			$oTempIp->ip = $sIp;
			$oTempIp->list = $sList;
			$oTempIp->label = empty( $sLabel ) ? __( 'No Label', 'wp-simple-firewall' ) : trim( $sLabel );

			if ( $oDbh->getQueryInserter()->insert( $oTempIp ) ) {
				/** @var IPs\EntryVO $oIp */
				$oIp = $this->getDbHandler()
							->getQuerySelector()
							->setWheresFromVo( $oTempIp )
							->first();
			}
		}

		return $oIp;
	}

	/**
	 * The auto black list isn't a simple lookup, but rather has an auto expiration
	 * @param string $sIp
	 * @return IPs\EntryVO|null
	 */
	protected function getBlackListIp( $sIp ) {
		/** @var ICWP_WPSF_FeatureHandler_Ips $oFO */
		$oFO = $this->getMod();
		/** @var IPs\Select $oSelect */
		$oSelect = $this->getDbHandler()->getQuerySelector();
		/** @var IPs\EntryVO $oIp */
		$oIp = $oSelect->filterByIp( $sIp )
					   ->filterByLists( [
						   ICWP_WPSF_FeatureHandler_Ips::LIST_AUTO_BLACK,
						   ICWP_WPSF_FeatureHandler_Ips::LIST_MANUAL_BLACK
					   ] )
					   ->filterByLastAccessAfter( $this->time() - $oFO->getAutoExpireTime() )
					   ->first();
		return $oIp;
	}

	/**
	 * The auto black list isn't a simple lookup, but rather has an auto expiration
	 * @param string $sIp
	 * @return IPs\EntryVO|null
	 */
	protected function getAutoBlackListIp( $sIp ) {
		/** @var ICWP_WPSF_FeatureHandler_Ips $oFO */
		$oFO = $this->getMod();
		/** @var IPs\Select $oSelect */
		$oSelect = $this->getDbHandler()->getQuerySelector();
		/** @var IPs\EntryVO $oIp */
		$oIp = $oSelect->filterByIp( $sIp )
					   ->filterByList( ICWP_WPSF_FeatureHandler_Ips::LIST_AUTO_BLACK )
					   ->filterByLastAccessAfter( $this->time() - $oFO->getAutoExpireTime() )
					   ->first();
		return $oIp;
	}

	/**
	 * The auto black list isn't a simple lookup, but rather has an auto expiration
	 * @param string $sIp
	 * @return IPs\EntryVO|null
	 */
	protected function getManualBlackListIp( $sIp ) {
		/** @var ICWP_WPSF_FeatureHandler_Ips $oFO */
		$oFO = $this->getMod();
		/** @var IPs\Select $oSelect */
		$oSelect = $this->getDbHandler()->getQuerySelector();
		/** @var IPs\EntryVO $oIp */
		$oIp = $oSelect->filterByIp( $sIp )
					   ->filterByList( ICWP_WPSF_FeatureHandler_Ips::LIST_MANUAL_BLACK )
					   ->filterByLastAccessAfter( $this->time() - $oFO->getAutoExpireTime() )
					   ->first();
		return $oIp;
	}

	/**
	 * @return string
	 */
	public function getCreateTableSql() {
		return "CREATE TABLE %s (
				id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
				ip varchar(40) NOT NULL DEFAULT '',
				label varchar(255) NOT NULL DEFAULT '',
				list varchar(4) NOT NULL DEFAULT '',
				ip6 tinyint(1) NOT NULL DEFAULT 0,
				is_range tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
				transgressions tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
				last_access_at int(15) UNSIGNED NOT NULL DEFAULT 0,
				created_at int(15) UNSIGNED NOT NULL DEFAULT 0,
				deleted_at int(15) UNSIGNED NOT NULL DEFAULT 0,
				PRIMARY KEY  (id)
			) %s;";
	}

	/**
	 * @return array
	 */
	protected function getTableColumnsByDefinition() {
		$aDef = $this->getMod()->getDef( 'ip_list_table_columns' );
		return is_array( $aDef ) ? $aDef : [];
	}

	/**
	 * @return \FernleafSystems\Wordpress\Plugin\Shield\Databases\IPs\Handler
	 */
	protected function createDbHandler() {
		return new \FernleafSystems\Wordpress\Plugin\Shield\Databases\IPs\Handler();
	}

	/**
	 * @return int
	 */
	protected function getAutoExpirePeriod() {
		/** @var ICWP_WPSF_FeatureHandler_Ips $oFO */
		$oFO = $this->getMod();
		return $oFO->getAutoExpireTime();
	}

	/**
	 * We only clean-up expired black list IPs
	 * @return bool
	 */
	public function cleanupDatabase() {
		if ( $this->getDbHandler()->isTable() ) {
			/** @var IPs\Delete $oDel */
			$oDel = $this->getDbHandler()->getQueryDeleter();
			$oDel->filterByLists( [ self::LIST_AUTO_BLACK, self::LIST_MANUAL_BLACK ] )
				 ->filterByLastAccessBefore( $this->time() - $this->getAutoExpirePeriod() )
				 ->query();
		}
		return true;
	}
}