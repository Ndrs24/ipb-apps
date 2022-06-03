<?php
namespace IPS\ventas\modules\admin\settings;

if(!\defined('\IPS\SUITE_UNIQUE_KEY')) {
	header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0').' 403 Forbidden');
	exit;
}

class _benefit extends \IPS\Dispatcher\Controller {
	public function execute() {
		\IPS\Dispatcher::i()->checkAcpPermission('benefit_manage');
		parent::execute();
	}

	protected function manage() {
		$form = new \IPS\Helpers\Form('formSettingsBenefits', '__save');

		$form->addTab('v_b_general');
		$form->addHeader('v_b_promo_desc');
		$form->add(new \IPS\Helpers\Form\YesNo('v_b_promo_enable', \IPS\Settings::i()->v_b_promo_enable, TRUE, array('togglesOn' => array('v_b_promo_extra_double', 'v_b_promo_extra'))));
		$form->add(new \IPS\Helpers\Form\YesNo('v_b_promo_extra_double', \IPS\Settings::i()->v_b_promo_extra_double, FALSE, array('togglesOff' => array('v_b_promo_extra')), NULL, NULL, NULL, 'v_b_promo_extra_double'));
		$form->add(new \IPS\Helpers\Form\Number('v_b_promo_extra', \IPS\Settings::i()->v_b_promo_extra, FALSE, array('max' => 12, 'min' => 0), NULL, NULL, NULL, 'v_b_promo_extra'));

		if($values = $form->values(TRUE)) {
			$form->saveAsSettings($values);

			\IPS\Data\Cache::i()->clearAll();
			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=ventas&module=settings&controller=benefit'), '__saved');
		}

		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('menu__ventas_settings_benefit');
		\IPS\Output::i()->output = $form;
	}
}