<?php

namespace FernleafSystems\Wordpress\Plugin\Shield\Modules\Firewall;

use FernleafSystems\Wordpress\Plugin\Shield\Modules\Base;

class Strings extends Base\Strings {

	/**
	 * @param string $section
	 * @return array
	 * @throws \Exception
	 */
	public function getSectionStrings( string $section ) :array {

		switch ( $section ) {

			case 'section_enable_plugin_feature_wordpress_firewall' :
				$titleShort = sprintf( '%s/%s', __( 'On', 'wp-simple-firewall' ), __( 'Off', 'wp-simple-firewall' ) );
				$title = sprintf( __( 'Enable Module: %s', 'wp-simple-firewall' ), $this->getMod()
																						->getMainFeatureName() );
				$summary = [
					sprintf( '%s - %s', __( 'Purpose', 'wp-simple-firewall' ), __( 'The Firewall is designed to analyse data sent to your website and block any requests that appear to be malicious.', 'wp-simple-firewall' ) ),
					sprintf( '%s - %s', __( 'Recommendation', 'wp-simple-firewall' ), sprintf( __( 'Keep the %s feature turned on.', 'wp-simple-firewall' ), __( 'Firewall', 'wp-simple-firewall' ) ) )
				];
				break;

			case 'section_firewall_blocking_options' :
				$title = __( 'Firewall Blocking Options', 'wp-simple-firewall' );
				$summary = [
					__( 'Here you choose what kind of malicious data to scan for.', 'wp-simple-firewall' ),
					sprintf( '%s - %s', __( 'Recommendation', 'wp-simple-firewall' ),
						__( 'Turn on as many options here as you can.', 'wp-simple-firewall' ) )
					.' '.__( 'If you find an incompatibility or something stops working, un-check 1 option at a time until you find the problem or review the Audit Trail.', 'wp-simple-firewall' ),
				];
				$titleShort = __( 'Firewall Blocking', 'wp-simple-firewall' );
				break;

			case 'section_choose_firewall_block_response' :
				$title = __( 'Choose Firewall Block Response', 'wp-simple-firewall' );
				$summary = [
					__( 'Here you choose how the plugin will respond when it detects malicious data.', 'wp-simple-firewall' ),
					sprintf( '%s - %s', __( 'Recommendation', 'wp-simple-firewall' ), sprintf( __( 'Choose the option "%s".', 'wp-simple-firewall' ), __( 'Die With Message', 'wp-simple-firewall' ) ) )
				];
				$titleShort = __( 'Firewall Response', 'wp-simple-firewall' );
				break;

			case 'section_whitelist' :
				$title = __( 'Whitelists - Pages, Parameters, and Users that bypass the Firewall', 'wp-simple-firewall' );
				$summary = [
					__( 'In principle you should not need to whitelist anything or anyone unless you have discovered a collision with another plugin.', 'wp-simple-firewall' ),
					sprintf( '%s - %s', __( 'Recommendation', 'wp-simple-firewall' ), __( 'Do not whitelist anything unless you are confident in what you are doing.', 'wp-simple-firewall' ) )
				];
				$titleShort = __( 'Whitelist', 'wp-simple-firewall' );
				break;

			default:
				return parent::getSectionStrings( $section );
		}

		return [
			'title'       => $title,
			'title_short' => $titleShort,
			'summary'     => ( isset( $summary ) && is_array( $summary ) ) ? $summary : [],
		];
	}

	/**
	 * @param string $key
	 * @return array
	 * @throws \Exception
	 */
	public function getOptionStrings( string $key ) :array {

		$sModName = $this->getMod()->getMainFeatureName();

		switch ( $key ) {

			case 'enable_firewall' :
				$name = sprintf( __( 'Enable %s Module', 'wp-simple-firewall' ), $sModName );
				$summary = sprintf( __( 'Enable (or Disable) The %s Module', 'wp-simple-firewall' ), $sModName );
				$desc = sprintf( __( 'Un-Checking this option will completely disable the %s module.', 'wp-simple-firewall' ), $sModName );
				break;

			case 'include_cookie_checks' :
				$name = __( 'Include Cookies', 'wp-simple-firewall' );
				$summary = __( 'Also Test Cookie Values In Firewall Tests', 'wp-simple-firewall' );
				$desc = __( 'The firewall tests GET and POST, but with this option checked it will also check COOKIE values.', 'wp-simple-firewall' );
				break;

			case 'block_dir_traversal' :
				$name = __( 'Directory Traversals', 'wp-simple-firewall' );
				$summary = __( 'Block Directory Traversals', 'wp-simple-firewall' );
				$desc = sprintf( __( 'This will block directory traversal paths in in application parameters (e.g. %s, etc).', 'wp-simple-firewall' ), base64_decode( 'Li4vLCAuLi8uLi9ldGMvcGFzc3dk' ) );
				break;

			case 'block_sql_queries' :
				$name = __( 'SQL Queries', 'wp-simple-firewall' );
				$summary = __( 'Block SQL Queries', 'wp-simple-firewall' );
				$desc = sprintf( __( 'This will block sql in application parameters (e.g. %s, etc).', 'wp-simple-firewall' ), base64_decode( 'dW5pb24gc2VsZWN0LCBjb25jYXQoLCAvKiovLCAuLik=' ) );
				break;

			case 'block_wordpress_terms' :
				$name = __( 'WordPress Terms', 'wp-simple-firewall' );
				$summary = __( 'Block WordPress Specific Terms', 'wp-simple-firewall' );
				$desc = __( 'This will block WordPress specific terms in application parameters (wp_, user_login, etc.).', 'wp-simple-firewall' );
				break;

			case 'block_field_truncation' :
				$name = __( 'Field Truncation', 'wp-simple-firewall' );
				$summary = __( 'Block Field Truncation Attacks', 'wp-simple-firewall' );
				$desc = __( 'This will block field truncation attacks in application parameters.', 'wp-simple-firewall' );
				break;

			case 'block_php_code' :
				$name = __( 'PHP Code', 'wp-simple-firewall' );
				$summary = sprintf( __( 'Block %s', 'wp-simple-firewall' ), __( 'PHP Code Includes', 'wp-simple-firewall' ) );
				$desc = __( 'This will block any data that appears to try and include PHP files.', 'wp-simple-firewall' )
						.'<br />'.__( 'Will probably block saving within the Plugin/Theme file editors.', 'wp-simple-firewall' );
				break;

			case 'block_exe_file_uploads' :
				$name = __( 'Exe File Uploads', 'wp-simple-firewall' );
				$summary = __( 'Block Executable File Uploads', 'wp-simple-firewall' );
				$desc = __( 'This will block executable file uploads (.php, .exe, etc.).', 'wp-simple-firewall' );
				break;

			case 'block_leading_schema' :
				$name = __( 'Leading Schemas', 'wp-simple-firewall' );
				$summary = __( 'Block Leading Schemas (HTTPS / HTTP)', 'wp-simple-firewall' );
				$desc = __( 'This will block leading schemas http:// and https:// in application parameters (off by default; may cause problems with other plugins).', 'wp-simple-firewall' );
				break;

			case 'block_aggressive' :
				$name = __( 'Aggressive Scan', 'wp-simple-firewall' );
				$summary = __( 'Aggressively Block Data', 'wp-simple-firewall' );
				$desc = __( 'Employs a set of aggressive rules to detect and block malicious data submitted to your site.', 'wp-simple-firewall' )
						.'<br />'.sprintf( '%s - %s', __( 'Warning', 'wp-simple-firewall' ), __( 'May cause an increase in false-positive firewall blocks.', 'wp-simple-firewall' ) );
				break;

			case 'block_response' :
				$name = __( 'Block Response', 'wp-simple-firewall' );
				$summary = __( 'Choose how the firewall responds when it blocks a request', 'wp-simple-firewall' );
				$desc = __( 'We recommend dying with a message so you know what might have occurred when the firewall blocks you', 'wp-simple-firewall' );
				break;

			case 'block_send_email' :
				$name = __( 'Send Email Report', 'wp-simple-firewall' );
				$summary = __( 'When a visitor is blocked the firewall will send an email to the configured email address', 'wp-simple-firewall' );
				$desc = __( 'Use with caution - if you get hit by automated bots you may send out too many emails and you could get blocked by your host', 'wp-simple-firewall' );
				break;

			case 'page_params_whitelist' :
				$name = __( 'Whitelist Parameters', 'wp-simple-firewall' );
				$summary = __( 'Detail pages and parameters that are whitelisted (ignored by the firewall)', 'wp-simple-firewall' );
				$desc = __( 'This should be used with caution and you should only provide parameter names that you must have excluded', 'wp-simple-firewall' );
				break;

			case 'whitelist_admins' :
				$name = sprintf( __( 'Ignore %s', 'wp-simple-firewall' ), __( 'Administrators', 'wp-simple-firewall' ) );
				$summary = sprintf( __( 'Ignore %s', 'wp-simple-firewall' ), __( 'Administrators', 'wp-simple-firewall' ) );
				$desc = __( 'Authenticated administrator users will not be processed by the firewall rules.', 'wp-simple-firewall' );
				break;

			case 'text_firewalldie' :
				$name = __( 'Firewall Block Message', 'wp-simple-firewall' );
				$summary = __( 'Message Displayed To Visitor When A Firewall Block Is Triggered', 'wp-simple-firewall' );
				$desc = __( 'This is the message displayed to visitors that trigger the firewall.', 'wp-simple-firewall' );
				break;

			default:
				return parent::getOptionStrings( $key );
		}

		return [
			'name'        => $name,
			'summary'     => $summary,
			'description' => $desc,
		];
	}

	/**
	 * @return string[][]
	 */
	protected function getAuditMessages() :array {
		/** @var \ICWP_WPSF_FeatureHandler_Firewall $mod */
		$mod = $this->getMod();

		$aMsgs = [
			'check_skip'                 => [
				sprintf( __( 'Skipping firewall checking for this visit: %s.', 'wp-simple-firewall' ), __( 'Parsing the URI failed', 'wp-simple-firewall' ) )
			],
			'blockparam_dirtraversal'    => [
				sprintf( __( 'Firewall Trigger: %s.', 'wp-simple-firewall' ), __( 'Directory Traversal', 'wp-simple-firewall' ) )
			],
			'blockparam_wpterms'         => [
				sprintf( __( 'Firewall Trigger: %s.', 'wp-simple-firewall' ), __( 'WordPress Terms', 'wp-simple-firewall' ) )
			],
			'blockparam_fieldtruncation' => [
				sprintf( __( 'Firewall Trigger: %s.', 'wp-simple-firewall' ), __( 'Field Truncation', 'wp-simple-firewall' ) )
			],
			'blockparam_sqlqueries'      => [
				sprintf( __( 'Firewall Trigger: %s.', 'wp-simple-firewall' ), __( 'SQL Queries', 'wp-simple-firewall' ) )
			],
			'blockparam_schema'          => [
				sprintf( __( 'Firewall Trigger: %s.', 'wp-simple-firewall' ), __( 'Leading Schema', 'wp-simple-firewall' ) )
			],
			'blockparam_aggressive'      => [
				sprintf( __( 'Firewall Trigger: %s.', 'wp-simple-firewall' ), __( 'Aggressive Rules', 'wp-simple-firewall' ) )
			],
			'blockparam_phpcode'         => [
				sprintf( __( 'Firewall Trigger: %s.', 'wp-simple-firewall' ), __( 'PHP Code', 'wp-simple-firewall' ) )
			],
			'block_exefile'              => [
				sprintf( __( 'Firewall Trigger: %s.', 'wp-simple-firewall' ), __( 'EXE File Uploads', 'wp-simple-firewall' ) )
			],
			'fw_email_success'           => [
				__( 'Successfully sent Firewall Block email alert to: %s', 'wp-simple-firewall' )
			],
			'fw_email_fail'              => [
				__( 'Failed to send Firewall Block email alert to: %s', 'wp-simple-firewall' )
			],
		];

		foreach ( $aMsgs as $sKey => &$aMsg ) {

			if ( strpos( $sKey, 'blockparam_' ) === 0 ) {
				$aMsg[] = __( 'Page parameter failed firewall check.', 'wp-simple-firewall' );
				$aMsg[] = __( 'The offending parameter was "%s" with a value of "%s".', 'wp-simple-firewall' );
			}

			if ( strpos( $sKey, 'block' ) === 0 ) {

				switch ( $mod->getBlockResponse() ) {
					case 'redirect_die':
						$sBlkResp = __( 'Visitor connection was killed with wp_die()', 'wp-simple-firewall' );
						break;
					case 'redirect_die_message':
						$sBlkResp = __( 'Visitor connection was killed with wp_die() and a message', 'wp-simple-firewall' );
						break;
					case 'redirect_home':
						$sBlkResp = __( 'Visitor was sent HOME', 'wp-simple-firewall' );
						break;
					case 'redirect_404':
						$sBlkResp = __( 'Visitor was sent 404', 'wp-simple-firewall' );
						break;
					default:
						$sBlkResp = __( 'Unknown', 'wp-simple-firewall' );
						break;
				}
				$aMsg[] = sprintf( __( 'Firewall Block Response: %s.', 'wp-simple-firewall' ), $sBlkResp );
			}
		}

		return $aMsgs;
	}
}