<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\Shield\Modules\Integrations\Lib\SpamHandlers;

class WpForo extends Base {

	const SLUG = 'wpforo';

	protected function run() {
		foreach ( $this->getFiltersToMonitor() as $filter ) {
			add_filter( $filter, function ( array $args, $forum ) {

				$status = $args[ 'status' ] ?? null;
				if ( $status !== 1 && $this->isSpamBot() ) {
					if ( !empty( WPF()->current_userid ) ) {
						WPF()->moderation->ban_for_spam( WPF()->current_userid );
					}
					$args[ 'status' ] = 1; // 1 signifies not approved
				}

				return $args;
			}, 1000, 2 );
		}
	}

	private function getFiltersToMonitor() :array {
		return [
			'wpforo_add_topic_data_filter',
			'wpforo_edit_topic_data_filter',
			'wpforo_add_post_data_filter',
			'wpforo_edit_post_data_filter',
		];
	}

	protected function getFormProvider() :string {
		return 'wpForo';
	}

	protected function isPluginInstalled() :bool {
		return function_exists( 'WPF' ) && @class_exists( 'wpForo' ) && !empty( WPF()->tools_antispam[ 'spam_filter' ] );
	}
}