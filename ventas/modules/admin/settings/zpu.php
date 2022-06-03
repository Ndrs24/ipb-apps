<?php
namespace IPS\ventas\modules\admin\settings;

if(!\defined('\IPS\SUITE_UNIQUE_KEY')) {
	header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0').' 403 Forbidden');
	exit;
}

class _zpu extends \IPS\Dispatcher\Controller {
	public function execute() {
		\IPS\Dispatcher::i()->checkAcpPermission('zpu_manage');
		parent::execute();
	}

	protected function manage() {
		$form = new \IPS\Helpers\Form('formSettingsZPU', '__save');

		$form->addTab('v_zpu_general');
		$form->addHeader('v_zpu_extra');
		$form->add(new \IPS\Helpers\Form\Number('v_zpu_percent_extra', \IPS\Settings::i()->v_zpu_percent_extra, TRUE));
		$form->addHeader('v_zpu_reward');
		$form->add(new \IPS\Helpers\Form\Number('v_zpu_ps_reward_base', \IPS\Settings::i()->v_zpu_ps_reward_base, TRUE));
		$form->add(new \IPS\Helpers\Form\Number('v_zpu_ps_reward', \IPS\Settings::i()->v_zpu_ps_reward, TRUE));

		if($values = $form->values(TRUE)) {
			$form->saveAsSettings($values);

			\IPS\Data\Cache::i()->clearAll();
			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=ventas&module=settings&controller=zpu'), '__saved');
		}

		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('menu__ventas_settings_zpu');
		\IPS\Output::i()->output = $form;
	}
}