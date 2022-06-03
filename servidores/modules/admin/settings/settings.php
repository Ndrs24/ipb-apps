<?php
namespace IPS\servidores\modules\admin\settings;

if(!\defined('\IPS\SUITE_UNIQUE_KEY')) {
	header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0').' 403 Forbidden');
	exit;
}

class _settings extends \IPS\Dispatcher\Controller {
	public function execute() {
		\IPS\Dispatcher::i()->checkAcpPermission("settings_manage");
		parent::execute();
	}

	protected function manage() {
		$form = new \IPS\Helpers\Form('s_settings', '__save_now');

		$form->addTab('s_settings_sql');
		$form->add(new \IPS\Helpers\Form\Text('s_sql_host', \IPS\Settings::i()->s_sql_host, TRUE));
		$form->add(new \IPS\Helpers\Form\Number('s_sql_port', \IPS\Settings::i()->s_sql_port, FALSE, array('max' => 65535, 'min' => 1)));
		$form->add(new \IPS\Helpers\Form\Text('s_sql_user', \IPS\Settings::i()->s_sql_user, TRUE));
		$form->add(new \IPS\Helpers\Form\Password('s_sql_password', \IPS\Settings::i()->s_sql_password, FALSE));
		$form->add(new \IPS\Helpers\Form\Text('s_sql_database', \IPS\Settings::i()->s_sql_database, TRUE));
		$form->add(new \IPS\Helpers\Form\Text('s_sql_prefix', \IPS\Settings::i()->s_sql_prefix, FALSE));
		$form->add(new \IPS\Helpers\Form\Text('s_sql_charset', \IPS\Settings::i()->s_sql_charset, FALSE, array('placeholder' => 'utf8')));

		if($values = $form->values(TRUE)) {
			try {
				$connection = array(
					'sql_host' => $values['s_sql_host'],
					'sql_port' => $values['s_sql_port'],
					'sql_user' => $values['s_sql_user'],
					'sql_pass' => $values['s_sql_password'],
					'sql_database' => $values['s_sql_database'],
					'sql_tbl_prefix' => $values['s_sql_prefix']
				);

				if($values['s_sql_charset'] === 'utf8mb4') {
					$connection['sql_utf8mb4'] = TRUE;
				}

				$db = \IPS\Db::i('focs', $connection);

				if($values['s_sql_charset'] && !\in_array($values['s_sql_charset'], array('utf8', 'utf8mb4'))) {
					$charsets = [];

					while($row = $db->query("SHOW CHARACTER SET;")->fetch_assoc()) {
						$charsets[] = mb_strtolower($row['Charset']);
					}

					if(!\in_array(mb_strtolower($values['s_sql_charset']), $charsets)) {
						throw new \InvalidArgumentException('invalid_charset');
					}
					
					$db->set_charset($values['s_sql_charset']);
				}
			} catch(\InvalidArgumentException $e) {
				if($e->getMessage() == 'invalid_charset') {
					$form->error = \IPS\Member::loggedIn()->language()->addToStack('s_sql_charset_error');
					return $form;
				} else {
					throw $e;
				}
			} catch(\Exception $e) {
				$form->error = \IPS\Member::loggedIn()->language()->addToStack('s_sql_error');
				return $form;
			}

			$form->saveAsSettings($values);

			\IPS\Data\Cache::i()->clearAll();
			\IPS\Output::i()->redirect(\IPS\Http\Url::internal("app=servidores&module=settings&controller=settings"), '__saved');
		}

		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack("menu__servidores_settings");
		\IPS\Output::i()->output = $form;
	}
}