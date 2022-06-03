<?php
namespace IPS\adminpanel\modules\front\main;

if(!\defined('\IPS\SUITE_UNIQUE_KEY')) {
	header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0').' 403 Forbidden');
	exit;
}

class _statusVip extends \IPS\Dispatcher\Controller {
	public $connection = array();
	public $db;

	public function execute() {
		$this->connection = array(
			'sql_host' => \IPS\Settings::i()->ap_sql_host,
			'sql_port' => \IPS\Settings::i()->ap_sql_port,
			'sql_user' => \IPS\Settings::i()->ap_sql_user,
			'sql_pass' => \IPS\Settings::i()->ap_sql_password,
			'sql_database' => \IPS\Settings::i()->ap_sql_database,
			'sql_tbl_prefix' => \IPS\Settings::i()->ap_sql_prefix
		);

		if(\IPS\Settings::i()->ap_sql_charset == 'utf8mb4') {
			$this->connection['sql_utf8mb4'] = TRUE;
		}

		$this->db = \IPS\Db::i('focs', $this->connection);
		parent::execute();
	}

	protected function manage() {
		$member_id = \IPS\Member::loggedIn()->member_id;

		if(!$member_id) {
			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=core&module=system&controller=login', 'front', 'login'));
		}

		\IPS\Session::i()->setLocation(\IPS\Http\Url::internal('app=adminpanel&module=main&controller=statusVip', 'front', 'statusVip'), array(), 'ap_viewing_statusVip');

		\IPS\Output::i()->title	= \IPS\Member::loggedIn()->language()->addToStack('menu__adminpanel_statusVip');
		\IPS\Output::i()->breadcrumb['module'] = array(NULL, \IPS\Member::loggedIn()->language()->addToStack('menu__adminpanel_statusVip'));
		\IPS\Output::i()->sidebar['enabled'] = FALSE;

		$data = [];
		$c = 0;

		foreach($this->db->select('a.id, a.name, a.finish, a.server_id, a.suspend_id, s.staff_name, s.staff_forum_id, s.start AS s_start, s.finish AS s_finish, s.reason AS s_reason, s.server_id AS s_server_id', array('gral_admins', 'a'), array('a.access=? AND a.forum_id=?', 'b', $member_id), 'a.id ASC')->join(array('gral_suspends', 's'), 's.admin_id=a.id', 'LEFT') as $row) {
			++$c;

			if(isset($row['server_id'])) {
				$row['server_id'] = \IPS\Application::load('adminpanel')->getServerName($row['server_id']);
			}

			if(isset($row['s_server_id'])) {
				$row['s_server_id'] = \IPS\Application::load('adminpanel')->getServerName($row['s_server_id']);
			}

			$data[] = $row;
		}

		if(!$c) {
			\IPS\Output::i()->error('No se ha detectado ningún VIP. Inténtalo más tarde o consigue tu VIP en la <a href="/ventas/">sección de ventas</a> de la comunidad.', 'DG/ESTADOVIP/3', 403, '');
		}

		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('main')->statusVip($data);
	}

	protected function config() {
		$member_id = \IPS\Member::loggedIn()->member_id;

		if(!$member_id) {
			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=core&module=system&controller=login', 'front', 'login'));
		}

		$id = \IPS\Request::i()->id;

		try {
			$row = $this->db->select('*', 'gral_admins', array('id=? AND forum_id=?', $id, $member_id), 'id ASC', 1)->first();

			$form = new \IPS\Helpers\Form('ap_sv_config', '__save_now');
			$form->class = 'ipsForm_fullWidth';

			$form->add(new \IPS\Helpers\Form\Text('ap_sv_config_name', $row['name'], FALSE));
			$form->add(new \IPS\Helpers\Form\Password('ap_sv_config_password', NULL, FALSE));

			if($values = $form->values()) {
				if(!empty($values['ap_sv_config_name']) &&
				!empty($values['ap_sv_config_password'])) {
					$this->db->update('gral_admins', array(
						'name' => $values['ap_sv_config_name'],
						'password' => md5($values['ap_sv_config_password'])
					), array('id=? AND forum_id=?', $id, $member_id));
				} else if(!empty($values['ap_sv_config_name'])) {
					$this->db->update('gral_admins', array(
						'name' => $values['ap_sv_config_name']
					), array('id=? AND forum_id=?', $id, $member_id));
				} else if(!empty($values['ap_sv_config_password'])) {
					$this->db->update('gral_admins', array(
						'password' => md5($values['ap_sv_config_password'])
					), array('id=? AND forum_id=?', $id, $member_id));
				}

				\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=adminpanel&module=main&controller=statusVip', 'front', 'statusVip'), '__saved');
			}

			\IPS\Output::i()->title = 'Configuración';
			\IPS\Output::i()->output = $form->customTemplate(array(\call_user_func_array(array(\IPS\Theme::i(), 'getTemplate'), array('forms', 'core')), 'popupTemplate'));
		} catch(\UnderflowException $e) {
			\IPS\Output::i()->error('No se ha detectado ningún VIP. Inténtalo más tarde o consigue tu VIP en la <a href="/ventas/">sección de ventas</a> de la comunidad.', 'DG/ESTADOVIP/3', 403, '');
		}
	}
}