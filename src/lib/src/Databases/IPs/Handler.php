<?php

namespace FernleafSystems\Wordpress\Plugin\Shield\Databases\IPs;

use FernleafSystems\Wordpress\Plugin\Shield\Databases\Base;
use FernleafSystems\Wordpress\Plugin\Shield\Modules\Ips\Options;
use FernleafSystems\Wordpress\Services\Services;

class Handler extends Base\EnumeratedColumnsHandler {

	public function autoCleanDb() {
		/** @var Options $opts */
		$opts = $this->getOptions();
		/** @var Delete $del */
		$del = $this->getQueryDeleter();
		$del->filterByBlacklist()
			->filterByLastAccessBefore( Services::Request()->ts() - $opts->getAutoExpireTime() )
			->query();
	}

	/**
	 * @param int $nTimeStamp
	 * @return bool
	 */
	public function deleteRowsOlderThan( $nTimeStamp ) {
		return $this->getQueryDeleter()
					->addWhereOlderThan( $nTimeStamp, 'last_access_at' )
					->addWhere( 'list', \ICWP_WPSF_FeatureHandler_Ips::LIST_MANUAL_WHITE, '!=' )
					->query();
	}

	/**
	 * @return string
	 */
	protected function getDefaultTableName() :string {
		/** @var Options $opts */
		$opts = $this->getOptions();
		return $opts->getDbTable_IPs();
	}

	/**
	 * @return string[]
	 */
	protected function getColumnsAsArray() :array {
		return $this->getOptions()->getDef( 'ip_list_table_columns' );
	}
}