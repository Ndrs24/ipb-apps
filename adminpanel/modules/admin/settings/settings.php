<?php
namespace IPS\adminpanel\modules\admin\settings;

if(!\defined('\IPS\SUITE_UNIQUE_KEY')) {
	header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0').' 403 Forbidden');
	exit;
}

class _settings extends \IPS\Dispatcher\Controller {
	public function execute() {
		\IPS\Dispatcher::i()->checkAcpPermission('settings_manage');
		parent::execute();
	}

	protected function manage() {
		$form = new \IPS\Helpers\Form('formSettingsAdminPanel', '__save');

		$form->addTab('ap_tab_general');
		$form->add(new \IPS\Helpers\Form\YesNo('ap_enable', \IPS\Settings::i()->ap_enable, TRUE, array('togglesOn' => array('ap_access'))));
		$form->add(new \IPS\Helpers\Form\Select('ap_access', array_filter(explode(',', \IPS\Settings::i()->ap_access)), FALSE, array('options' => \IPS\Member\Group::groups(), 'multiple' => TRUE, 'parse' => 'normal'), NULL, NULL, NULL, 'ap_access'));

		$form->addTab('ap_tab_access');
		$form->addHeader('ap_tab_admin_access');
		$form->add(new \IPS\Helpers\Form\Select('ap_viplist_access', array_filter(explode(',', \IPS\Settings::i()->ap_viplist_access)), FALSE, array('options' => \IPS\Member\Group::groups(), 'multiple' => TRUE, 'parse' => 'normal')));
		$form->add(new \IPS\Helpers\Form\Select('ap_adminlist_access', array_filter(explode(',', \IPS\Settings::i()->ap_adminlist_access)), FALSE, array('options' => \IPS\Member\Group::groups(), 'multiple' => TRUE, 'parse' => 'normal')));
		$form->add(new \IPS\Helpers\Form\Select('ap_ministafflist_access', array_filter(explode(',', \IPS\Settings::i()->ap_ministafflist_access)), FALSE, array('options' => \IPS\Member\Group::groups(), 'multiple' => TRUE, 'parse' => 'normal')));
		$form->add(new \IPS\Helpers\Form\Select('ap_stafflist_access', array_filter(explode(',', \IPS\Settings::i()->ap_stafflist_access)), FALSE, array('options' => \IPS\Member\Group::groups(), 'multiple' => TRUE, 'parse' => 'normal')));
		$form->add(new \IPS\Helpers\Form\Select('ap_addadmin_access', array_filter(explode(',', \IPS\Settings::i()->ap_addadmin_access)), FALSE, array('options' => \IPS\Member\Group::groups(), 'multiple' => TRUE, 'parse' => 'normal')));
		$form->addHeader('ap_tab_ban_access');
		$form->add(new \IPS\Helpers\Form\Select('ap_banlist_access', array_filter(explode(',', \IPS\Settings::i()->ap_banlist_access)), FALSE, array('options' => \IPS\Member\Group::groups(), 'multiple' => TRUE, 'parse' => 'normal')));
		$form->add(new \IPS\Helpers\Form\Select('ap_editban_access', array_filter(explode(',', \IPS\Settings::i()->ap_editban_access)), FALSE, array('options' => \IPS\Member\Group::groups(), 'multiple' => TRUE, 'parse' => 'normal')));
		$form->addHeader('ap_tab_suspend_access');
		$form->add(new \IPS\Helpers\Form\Select('ap_suspendlist_access', array_filter(explode(',', \IPS\Settings::i()->ap_suspendlist_access)), FALSE, array('options' => \IPS\Member\Group::groups(), 'multiple' => TRUE, 'parse' => 'normal')));
		$form->add(new \IPS\Helpers\Form\Select('ap_editsuspend_access', array_filter(explode(',', \IPS\Settings::i()->ap_editsuspend_access)), FALSE, array('options' => \IPS\Member\Group::groups(), 'multiple' => TRUE, 'parse' => 'normal')));
		$form->addHeader('ap_tab_adv_access');
		$form->add(new \IPS\Helpers\Form\Select('ap_advlist_access', array_filter(explode(',', \IPS\Settings::i()->ap_advlist_access)), FALSE, array('options' => \IPS\Member\Group::groups(), 'multiple' => TRUE, 'parse' => 'normal')));
		$form->add(new \IPS\Helpers\Form\Select('ap_addadv_access', array_filter(explode(',', \IPS\Settings::i()->ap_addadv_access)), FALSE, array('options' => \IPS\Member\Group::groups(), 'multiple' => TRUE, 'parse' => 'normal')));

		$form->addTab('ap_tab_sql');
		$form->add(new \IPS\Helpers\Form\Text('ap_sql_host', \IPS\Settings::i()->ap_sql_host, TRUE));
		$form->add(new \IPS\Helpers\Form\Number('ap_sql_port', \IPS\Settings::i()->ap_sql_port, FALSE, array('max' => 65535, 'min' => 1)));
		$form->add(new \IPS\Helpers\Form\Text('ap_sql_user', \IPS\Settings::i()->ap_sql_user, TRUE));
		$form->add(new \IPS\Helpers\Form\Password('ap_sql_password', \IPS\Settings::i()->ap_sql_password, FALSE));
		$form->add(new \IPS\Helpers\Form\Text('ap_sql_database', \IPS\Settings::i()->ap_sql_database, TRUE));
		$form->add(new \IPS\Helpers\Form\Text('ap_sql_prefix', \IPS\Settings::i()->ap_sql_prefix, FALSE));
		$form->add(new \IPS\Helpers\Form\Text('ap_sql_charset', \IPS\Settings::i()->ap_sql_charset, FALSE, array('placeholder' => 'utf8')));

		if($values = $form->values(TRUE)) {
			try {
				$connection = array(
					'sql_host' => $values['ap_sql_host'],
					'sql_port' => $values['ap_sql_port'],
					'sql_user' => $values['ap_sql_user'],
					'sql_pass' => $values['ap_sql_password'],
					'sql_database' => $values['ap_sql_database'],
					'sql_tbl_prefix' => $values['ap_sql_prefix']
				);

				if($values['ap_sql_charset'] === 'utf8mb4') {
					$connection['sql_utf8mb4'] = TRUE;
				}

				$db = \IPS\Db::i('focs', $connection);

				if($values['ap_sql_charset'] && !\in_array($values['ap_sql_charset'], array('utf8', 'utf8mb4'))) {
					$charsets = [];

					while($row = $db->query("SHOW CHARACTER SET;")->fetch_assoc()) {
						$charsets[] = mb_strtolower($row['Charset']);
					}

					if(!\in_array(mb_strtolower($values['ap_sql_charset']), $charsets)) {
						throw new \InvalidArgumentException('invalid_charset');
					}
					
					$db->set_charset($values['ap_sql_charset']);
				}
			} catch(\InvalidArgumentException $e) {
				if($e->getMessage() == 'invalid_charset') {
					$form->error = \IPS\Member::loggedIn()->language()->addToStack('ap_sql_charset_error');
					return $form;
				} else {
					throw $e;
				}
			} catch(\Exception $e) {
				$form->error = \IPS\Member::loggedIn()->language()->addToStack('ap_sql_error');
				return $form;
			}

			$form->saveAsSettings($values);
			
			\IPS\Data\Cache::i()->clearAll();
			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=adminpanel&module=settings&controller=settings'), '__saved');
		}

		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('menu__adminpanel_adminpanel');
		\IPS\Output::i()->output = $form;
	}
}