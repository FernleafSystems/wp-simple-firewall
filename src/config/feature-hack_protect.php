<?php
return
	sprintf(
	"---
slug: 'hack_protect'
properties:
  name: '%s'
  show_feature_menu_item: true
  storage_key: 'hack_protect' # should correspond exactly to that in the plugin.yaml
# Options Sections
sections:
  -
    slug: 'section_enable_plugin_feature_hack_protection_tools'
    primary: true
  -
    slug: 'section_plugin_vulnerabilities_scan'
  -
    slug: 'section_core_file_integrity_scan'
  -
    slug: 'section_non_ui'
    hidden: true

# Define Options
options:
  -
    key: 'enable_hack_protect'
    section: 'section_enable_plugin_feature_hack_protection_tools'
    default: 'Y'
    type: 'checkbox'
    link_info: ''
    link_blog: ''
  -
    key: 'enable_plugin_vulnerabilities_scan'
    section: 'section_plugin_vulnerabilities_scan'
    default: 'Y'
    type: 'checkbox'
    link_info: 'http://icwp.io/wpsf34'
    link_blog: 'http://icwp.io/wpsf35'
  -
    key: 'enable_core_file_integrity_scan'
    section: 'section_core_file_integrity_scan'
    default: 'Y'
    type: 'checkbox'
    link_info: 'http://icwp.io/wpsf36'
    link_blog: 'http://icwp.io/wpsf37'
  -
    key: 'attempt_auto_file_repair'
    section: 'section_core_file_integrity_scan'
    default: 'N'
    type: 'checkbox'
    link_info: 'http://icwp.io/wpsf36'
    link_blog: 'http://icwp.io/wpsf37'
  -
    key: 'current_plugin_version'
    section: 'section_non_ui'

# Definitions for constant data that doesn't need store in the options
definitions:
  plugin_vulnerabilities_data_source: 'https://raw.githubusercontent.com/FernleafSystems/wp-plugin-vulnerabilities/master/vulnerabilities.yaml'
  notifications_cron_name: 'plugin-vulnerabilities-notification'
  corechecksum_cron_name: 'core-checksum-notification'
  url_checksum_api: 'https://api.wordpress.org/core/checksums/1.0/'
  url_wordress_core_svn: 'https://core.svn.wordpress.org/'
  corechecksum_exclusions:
    - 'readme.html'
    - 'license.txt'
    - 'hello.php'
    - 'wp-config-sample.php'
    - 'akismet'
    - 'themes/twenty'
",
		_wpsf__( 'Hack Protection' )
	);