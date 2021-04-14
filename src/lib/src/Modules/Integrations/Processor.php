<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\Shield\Modules\Integrations;

use FernleafSystems\Wordpress\Plugin\Shield\Modules\BaseShield;

class Processor extends BaseShield\Processor {

	protected function run() {
		/** @var ModCon $mod */
		$mod = $this->getMod();
		$mod->getControllerMWP()->execute();
		
		( new Lib\Bots\Spam\SpamController() )
			->setMod( $this->getMod() )
			->execute();
		( new Lib\Bots\UserForms\UserFormsController() )
			->setMod( $this->getMod() )
			->execute();
	}
}