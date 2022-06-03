<?php
namespace IPS\ventas\modules\admin\settings;

if(!\defined('\IPS\SUITE_UNIQUE_KEY')) {
	header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
	exit;
}

class _settings extends \IPS\Dispatcher\Controller {
	public function execute() {
		\IPS\Dispatcher::i()->checkAcpPermission('settings_manage');
		parent::execute();
	}

	protected function manage() {
		$form = new \IPS\Helpers\Form('formSettingsVentas', '__save');

		$form->addTab('v_general');
		$form->add(new \IPS\Helpers\Form\YesNo('v_enable', \IPS\Settings::i()->v_enable, TRUE));
		$form->add(new \IPS\Helpers\Form\YesNo('v_show_terms', \IPS\Settings::i()->v_show_terms, TRUE));
		$form->add(new \IPS\Helpers\Form\Select('v_access', array_filter(explode(',', \IPS\Settings::i()->v_access)), false, array('options' => \IPS\Member\Group::groups(), 'multiple' => true, 'parse' => 'normal')));
		$form->add(new \IPS\Helpers\Form\Select('v_access_list', array_filter(explode(',', \IPS\Settings::i()->v_access_list)), false, array('options' => \IPS\Member\Group::groups(), 'multiple' => true, 'parse' => 'normal')));

		$form->addTab('v_region');
		$form->add(new \IPS\Helpers\Form\YesNo('v_region_per', \IPS\Settings::i()->v_region_per, TRUE));
		$form->add(new \IPS\Helpers\Form\YesNo('v_region_per_ze', \IPS\Settings::i()->v_region_per_ze, TRUE));
		$form->add(new \IPS\Helpers\Form\YesNo('v_region_per_zpu', \IPS\Settings::i()->v_region_per_zpu, TRUE));
		$form->add(new \IPS\Helpers\Form\YesNo('v_region_per_zpa', \IPS\Settings::i()->v_region_per_zpa, TRUE));

		$form->add(new \IPS\Helpers\Form\YesNo('v_region_arg', \IPS\Settings::i()->v_region_arg, TRUE));
		$form->add(new \IPS\Helpers\Form\YesNo('v_region_arg_ze', \IPS\Settings::i()->v_region_arg_ze, TRUE));
		$form->add(new \IPS\Helpers\Form\YesNo('v_region_arg_zpu', \IPS\Settings::i()->v_region_arg_zpu, TRUE));
		$form->add(new \IPS\Helpers\Form\YesNo('v_region_arg_zpa', \IPS\Settings::i()->v_region_arg_zpa, TRUE));

		$form->add(new \IPS\Helpers\Form\YesNo('v_region_pp', \IPS\Settings::i()->v_region_pp, TRUE));
		$form->add(new \IPS\Helpers\Form\YesNo('v_region_pp_ze', \IPS\Settings::i()->v_region_pp_ze, TRUE));
		$form->add(new \IPS\Helpers\Form\YesNo('v_region_pp_zpu', \IPS\Settings::i()->v_region_pp_zpu, TRUE));
		$form->add(new \IPS\Helpers\Form\YesNo('v_region_pp_zpa', \IPS\Settings::i()->v_region_pp_zpa, TRUE));
		
		$form->add(new \IPS\Helpers\Form\YesNo('v_region_cl', \IPS\Settings::i()->v_region_cl, TRUE));
		$form->add(new \IPS\Helpers\Form\YesNo('v_region_cl_ze', \IPS\Settings::i()->v_region_cl_ze, TRUE));
		$form->add(new \IPS\Helpers\Form\YesNo('v_region_cl_zpu', \IPS\Settings::i()->v_region_cl_zpu, TRUE));
		$form->add(new \IPS\Helpers\Form\YesNo('v_region_cl_zpa', \IPS\Settings::i()->v_region_cl_zpa, TRUE));

		$form->addTab('v_settings_sql');
		$form->add(new \IPS\Helpers\Form\Text('v_sql_host', \IPS\Settings::i()->v_sql_host, TRUE));
		$form->add(new \IPS\Helpers\Form\Number('v_sql_port', \IPS\Settings::i()->v_sql_port, FALSE, array('max' => 65535, 'min' => 1)));
		$form->add(new \IPS\Helpers\Form\Text('v_sql_user', \IPS\Settings::i()->v_sql_user, TRUE));
		$form->add(new \IPS\Helpers\Form\Password('v_sql_password', \IPS\Settings::i()->v_sql_password, FALSE));
		$form->add(new \IPS\Helpers\Form\Text('v_sql_database', \IPS\Settings::i()->v_sql_database, TRUE));
		$form->add(new \IPS\Helpers\Form\Text('v_sql_prefix', \IPS\Settings::i()->v_sql_prefix, FALSE));
		$form->add(new \IPS\Helpers\Form\Text('v_sql_charset', \IPS\Settings::i()->v_sql_charset, FALSE, array('placeholder' => 'utf8')));

		if($values = $form->values(TRUE)) {
			try {
				$connection = array(
					'sql_host' => $values['v_sql_host'],
					'sql_port' => $values['v_sql_port'],
					'sql_user' => $values['v_sql_user'],
					'sql_pass' => $values['v_sql_password'],
					'sql_database' => $values['v_sql_database'],
					'sql_tbl_prefix' => $values['v_sql_prefix']
				);

				if($values['v_sql_charset'] === 'utf8mb4') {
					$connection['sql_utf8mb4'] = TRUE;
				}

				$db = \IPS\Db::i('focs', $connection);

				if($values['v_sql_charset'] && !\in_array($values['v_sql_charset'], array('utf8', 'utf8mb4'))) {
					$charsets = [];

					while($row = $db->query("SHOW CHARACTER SET;")->fetch_assoc()) {
						$charsets[] = mb_strtolower($row['Charset']);
					}

					if(!\in_array(mb_strtolower($values['v_sql_charset']), $charsets)) {
						throw new \InvalidArgumentException('invalid_charset');
					}
					
					$db->set_charset($values['v_sql_charset']);
				}
			} catch(\InvalidArgumentException $e) {
				if($e->getMessage() == 'invalid_charset') {
					$form->error = \IPS\Member::loggedIn()->language()->addToStack('v_sql_charset_error');
					return $form;
				} else {
					throw $e;
				}
			} catch(\Exception $e) {
				$form->error = \IPS\Member::loggedIn()->language()->addToStack('v_sql_error');
				return $form;
			}

			$form->saveAsSettings($values);

			\IPS\Data\Cache::i()->clearAll();
			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=ventas&module=settings&controller=settings'), '__saved');
		}

		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('menu__ventas_settings_settings');
		\IPS\Output::i()->output = $form;
	}
}