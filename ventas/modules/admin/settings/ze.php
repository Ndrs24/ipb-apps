<?php
namespace IPS\ventas\modules\admin\settings;

if(!\defined('\IPS\SUITE_UNIQUE_KEY')) {
	header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0').' 403 Forbidden');
	exit;
}

class _ze extends \IPS\Dispatcher\Controller {
	public function execute() {
		\IPS\Dispatcher::i()->checkAcpPermission('ze_manage');
		parent::execute();
	}

	protected function manage() {
		$form = new \IPS\Helpers\Form('formSettingsZE', '__save');

		$form->addTab('v_ze_general');
		$form->addHeader('v_ze_extra');
		$form->add(new \IPS\Helpers\Form\Number('v_ze_percent_extra', \IPS\Settings::i()->v_ze_percent_extra, TRUE));
		$form->addHeader('v_ze_reward');
		$form->add(new \IPS\Helpers\Form\Number('v_ze_pl_reward_base', \IPS\Settings::i()->v_ze_pl_reward_base, TRUE));
		$form->add(new \IPS\Helpers\Form\Number('v_ze_pl_reward', \IPS\Settings::i()->v_ze_pl_reward, TRUE));

		if($values = $form->values(TRUE)) {
			$form->saveAsSettings($values);

			\IPS\Data\Cache::i()->clearAll();
			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=ventas&module=settings&controller=ze'), '__saved');
		}

		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('menu__ventas_settings_ze');
		\IPS\Output::i()->output = $form;
	}
}