<?php
namespace IPS\ventas\modules\admin\settings;

if(!\defined('\IPS\SUITE_UNIQUE_KEY')) {
	header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0').' 403 Forbidden');
	exit;
}

class _zpa extends \IPS\Dispatcher\Controller {
	public function execute() {
		\IPS\Dispatcher::i()->checkAcpPermission('zpa_manage');
		parent::execute();
	}

	protected function manage() {
		$form = new \IPS\Helpers\Form('formSettingsZPA', '__save');

		$form->addTab('v_zpa_general');
		$form->addHeader('v_zpa_extra');
		$form->add(new \IPS\Helpers\Form\Number('v_zpa_percent_extra', \IPS\Settings::i()->v_zpa_percent_extra, TRUE));
		$form->addHeader('v_zpa_reward');
		$form->add(new \IPS\Helpers\Form\Number('v_zpa_ph_reward_base', \IPS\Settings::i()->v_zpa_ph_reward_base, TRUE));
		$form->add(new \IPS\Helpers\Form\Number('v_zpa_ph_reward', \IPS\Settings::i()->v_zpa_ph_reward, TRUE));
		$form->add(new \IPS\Helpers\Form\Number('v_zpa_pz_reward_base', \IPS\Settings::i()->v_zpa_pz_reward_base, TRUE));
		$form->add(new \IPS\Helpers\Form\Number('v_zpa_pz_reward', \IPS\Settings::i()->v_zpa_pz_reward, TRUE));
		$form->add(new \IPS\Helpers\Form\Number('v_zpa_pl_reward_base', \IPS\Settings::i()->v_zpa_pl_reward_base, TRUE));
		$form->add(new \IPS\Helpers\Form\Number('v_zpa_pl_reward', \IPS\Settings::i()->v_zpa_pl_reward, TRUE));
		$form->add(new \IPS\Helpers\Form\Number('v_zpa_ps_reward_base', \IPS\Settings::i()->v_zpa_ps_reward_base, TRUE));
		$form->add(new \IPS\Helpers\Form\Number('v_zpa_ps_reward', \IPS\Settings::i()->v_zpa_ps_reward, TRUE));
		$form->add(new \IPS\Helpers\Form\Number('v_zpa_pd_reward_base', \IPS\Settings::i()->v_zpa_pd_reward_base, TRUE));
		$form->add(new \IPS\Helpers\Form\Number('v_zpa_pd_reward', \IPS\Settings::i()->v_zpa_pd_reward, TRUE));

		if($values = $form->values(TRUE)) {
			$form->saveAsSettings($values);

			\IPS\Data\Cache::i()->clearAll();
			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=ventas&module=settings&controller=zpa'), '__saved');
		}

		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('menu__ventas_settings_zpa');
		\IPS\Output::i()->output = $form;
	}
}