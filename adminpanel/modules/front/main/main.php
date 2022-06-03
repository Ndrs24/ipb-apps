<?php
namespace IPS\adminpanel\modules\front\main;

if(!\defined('\IPS\SUITE_UNIQUE_KEY')) {
	header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0').' 403 Forbidden');
	exit;
}

class _main extends \IPS\Dispatcher\Controller {
	public $url;
	public $urlVipList;
	public $urlAdminList;
	public $urlMiniStaffList;
	public $urlStaffList;
	public $urlMyBans;
	public $urlBanList;
	public $urlSuspendList;
	public $urlAdvList;
	public $connection = array();
	public $db;
	public $adminAccess = array(
		'abcdefghijklpqrstuvy' => 'Director',
		'abcdefghijkpqrstuvy' => 'Super moderador',
		'abcdefghijkpqrsuvy' => 'Moderador',
		'abcdefghijkpqruvy' => 'Desarrollador',
		'abcdefghijkpquvy' => 'Manager',
		'abcdefghijkpuvy' => 'Capitán',
		'abcdeijuvy' => 'Admin CS',
		'b' => 'VIP',
	);

	public function execute() {
		$this->url = \IPS\Http\Url::internal('app=adminpanel&module=main&controller=main', 'front', 'adminpanel');
		$this->urlVipList = \IPS\Http\Url::internal('app=adminpanel&module=main&controller=main&area=vipList', 'front', 'adminpanel_vipList');
		$this->urlAdminList = \IPS\Http\Url::internal('app=adminpanel&module=main&controller=main&area=adminList', 'front', 'adminpanel_adminList');
		$this->urlMiniStaffList = \IPS\Http\Url::internal('app=adminpanel&module=main&controller=main&area=miniStaffList', 'front', 'adminpanel_miniStaffList');
		$this->urlStaffList = \IPS\Http\Url::internal('app=adminpanel&module=main&controller=main&area=staffList', 'front', 'adminpanel_staffList');
		$this->urlMyBans = \IPS\Http\Url::internal('app=adminpanel&module=main&controller=main&area=myBans', 'front', 'adminpanel_myBans');
		$this->urlBanList = \IPS\Http\Url::internal('app=adminpanel&module=main&controller=main&area=banList', 'front', 'adminpanel_banList');
		$this->urlSuspendList = \IPS\Http\Url::internal('app=adminpanel&module=main&controller=main&area=suspendList', 'front', 'adminpanel_suspendList');
		$this->urlAdvList = \IPS\Http\Url::internal('app=adminpanel&module=main&controller=main&area=advList', 'front', 'adminpanel_advList');

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

		\IPS\Session::i()->setLocation($this->url, array(), '__viewing');

		\IPS\Output::i()->title	= \IPS\Member::loggedIn()->language()->addToStack('menu__adminpanel_adminpanel');
		\IPS\Output::i()->breadcrumb['module'] = array($this->url, \IPS\Member::loggedIn()->language()->addToStack('menu__adminpanel_adminpanel'));
		\IPS\Output::i()->sidebar['enabled'] = FALSE;

		\IPS\Output::i()->cssFiles = array_merge(\IPS\Output::i()->cssFiles, \IPS\Theme::i()->css('main.css', 'adminpanel', 'front'));
		parent::execute();
	}

	protected function manage() {
		if(!\IPS\Settings::i()->ap_enable) {
			\IPS\Output::i()->error('La sección -Admin Panel- está deshabilitada temporalmente.', 'DG/AP/0', 403, '');
		}

		if(!\IPS\Member::loggedIn()->member_id) {
			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=core&module=system&controller=login', 'front', 'login'));
		}

		if(!\IPS\Member::loggedIn()->inGroup(array_filter(explode(',', \IPS\Settings::i()->ap_access)))) {
			\IPS\Output::i()->error('No tienes permisos para acceder a la sección.', 'DG/AP/1', 403, '');
		}

		$area = \IPS\Request::i()->area ?: 'statusAdmin';
		$method_name = "_{$area}";

		if(method_exists($this, $method_name)) {
			$output = $this->$method_name();
		}

		if(!\IPS\Request::i()->isAjax()) {
			if(\IPS\Request::i()->service) {
				$area = "{$area}_" . \IPS\Request::i()->service;
			}

			$canVipList = \IPS\Application::load('adminpanel')->canVipList();
			$canAdminList = \IPS\Application::load('adminpanel')->canAdminList();
			$canMiniStaffList = \IPS\Application::load('adminpanel')->canMiniStaffList();
			$canStaffList = \IPS\Application::load('adminpanel')->canStaffList();
			$canAddAdmin = \IPS\Application::load('adminpanel')->canAddAdmin();
			$canBanList = \IPS\Application::load('adminpanel')->canBanList();
			$canSuspendList = \IPS\Application::load('adminpanel')->canSuspendList();
			$canAdvList = \IPS\Application::load('adminpanel')->canAdvList();
			$canAddAdv = \IPS\Application::load('adminpanel')->canAddAdv();

			if($output) {
				\IPS\Output::i()->output .= \IPS\Theme::i()->getTemplate('main')->main($area, $output, $canVipList, $canAdminList, $canMiniStaffList, $canStaffList, $canAddAdmin, $canBanList, $canSuspendList, $canAdvList, $canAddAdv);
			}
		} else if($output) {
			\IPS\Output::i()->output .= $output;
		}
	}

	protected function _statusAdmin() {
		$member_id = \IPS\Member::loggedIn()->member_id;

		if(!$member_id) {
			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=core&module=system&controller=login', 'front', 'login'));
		}

		$data = [];
		$c = 0;

		foreach($this->db->select('a.id, a.name, a.access, a.finish, a.server_id, a.suspend_id, s.staff_name, s.staff_forum_id, s.start AS s_start, s.finish AS s_finish, s.reason AS s_reason, s.server_id AS s_server_id', array('gral_admins', 'a'), array('a.access<>? AND a.forum_id=?', 'b', $member_id), 'a.id ASC')->join(array('gral_suspends', 's'), 's.admin_id=a.id', 'LEFT') as $row) {
			++$c;

			if(isset($row['access'])) {
				$row['access'] = \IPS\Application::load('adminpanel')->getAccessName($row['access']);
			}

			if(isset($row['server_id'])) {
				$row['server_id'] = \IPS\Application::load('adminpanel')->getServerName($row['server_id']);
			}

			if(isset($row['s_server_id'])) {
				$row['s_server_id'] = \IPS\Application::load('adminpanel')->getServerName($row['s_server_id']);
			}

			$data[] = $row;
		}

		if(!$c) {
			\IPS\Output::i()->error('No se ha detectado ningún Admin CS. Inténtalo más tarde o consigue tu Admin CS en la <a href="/ventas/">sección de ventas</a> de la comunidad.', 'DG/AP/2', 403, '');
		}

		return \IPS\Theme::i()->getTemplate('main')->statusAdmin($data);
	}

	protected function statusAdminConfig() {
		$member_id = \IPS\Member::loggedIn()->member_id;

		if(!$member_id) {
			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=core&module=system&controller=login', 'front', 'login'));
		}

		$id = \IPS\Request::i()->id;

		try {
			$row = $this->db->select('*', 'gral_admins', array('id=? AND forum_id=?', $id, $member_id), 'id ASC', 1)->first();

			$form = new \IPS\Helpers\Form('formSettingsStatusAdmin', '__save_now');	
			$form->class = 'ipsForm_fullWidth';

			$demo = array(
				0 => 'No grabar demos',
				1 => 'Grabar demos por fecha',
				2 => 'Grabar demos por fecha y hora',
				3 => 'Grabar demos por mapa',
				4 => 'Grabar demos por fecha y mapa',
				5 => 'Grabar demos por fecha, hora y mapa'
			);

			$form->add(new \IPS\Helpers\Form\Text('ap_sa_config_name', $row['name'], FALSE));
			$form->add(new \IPS\Helpers\Form\Password('ap_sa_config_password', NULL, FALSE));
			$form->add(new \IPS\Helpers\Form\Select('ap_sa_config_demo', $row['demo'], FALSE, array('options' => $demo)));

			if($values = $form->values()) {
				if(!empty($values['ap_sa_config_name']) &&
				!empty($values['ap_sa_config_password'])) {
					$this->db->update('gral_admins', array(
						'name' => $values['ap_sa_config_name'],
						'password' => md5($values['ap_sa_config_password']),
						'demo' => $values['ap_sa_config_demo']
					), array('id=? AND forum_id=?', $id, $member_id));
				} else if(!empty($values['ap_sa_config_name'])) {
					$this->db->update('gral_admins', array(
						'name' => $values['ap_sa_config_name'],
						'demo' => $values['ap_sa_config_demo']
					), array('id=? AND forum_id=?', $id, $member_id));
				} else if(!empty($values['ap_sa_config_password'])) {
					$this->db->update('gral_admins', array(
						'password' => md5($values['ap_sa_config_password']),
						'demo' => $values['ap_sa_config_demo']
					), array('id=? AND forum_id=?', $id, $member_id));
				} else {
					$this->db->update('gral_admins', array(
						'demo' => $values['ap_sa_config_demo']
					), array('id=? AND forum_id=?', $id, $member_id));
				}

				\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=adminpanel&module=main&controller=panel&area=statusAdmin', 'front', 'adminpanel_statusAdmin'), '__saved');
			}

			\IPS\Output::i()->title = 'Configuración';
			\IPS\Output::i()->output = $form->customTemplate(array(\call_user_func_array(array(\IPS\Theme::i(), 'getTemplate'), array('forms', 'core')), 'popupTemplate'));
		} catch(\UnderflowException $e) {
			\IPS\Output::i()->error('No se ha detectado ningún Admin CS. Inténtalo más tarde o consigue tu Admin CS en la <a href="/ventas/">sección de ventas</a> de la comunidad.', 'DG/AP/3', 403, '');
		}
	}

	protected function _vipList() {
		$table = new \IPS\adminpanel\Table\Db('focs', $this->connection, 'gral_admins', $this->urlVipList, array(array('access=?', 'b')));
		$table->rowsTemplate = array(\IPS\Theme::i()->getTemplate('main'), 'vipListRow');
		$table->orderBy = 'finish, name ASC';
		$table->sortBy = $table->sortBy ?: 'id';
		$table->sortDirection = $table->sortDirection ?: 'DESC';
		$table->advancedSearch = array(
			'name' => \IPS\adminpanel\Table\SEARCH_CONTAINS_TEXT
		);
		$table->parsers = array(
			'server_id' => function($val) {
				return \IPS\Application::load('adminpanel')->getServerName($val);
			}
		);

		return $table;
	}

	protected function _adminList() {
		$table = new \IPS\adminpanel\Table\Db('focs', $this->connection, 'gral_admins', $this->urlAdminList, array(array('access=?', 'abcdeijuvy')));
		$table->rowsTemplate = array(\IPS\Theme::i()->getTemplate('main'), 'adminListRow');
		$table->orderBy = 'finish, name ASC';
		$table->sortBy = $table->sortBy ?: 'id';
		$table->sortDirection = $table->sortDirection ?: 'DESC';
		$table->advancedSearch = array(
			'name' => \IPS\adminpanel\Table\SEARCH_CONTAINS_TEXT
		);
		$table->parsers = array(
			'server_id' => function($val) {
				return \IPS\Application::load('adminpanel')->getServerName($val);
			}
		);

		return $table;
	}

	protected function _miniStaffList() {
		$table = new \IPS\adminpanel\Table\Db('focs', $this->connection, 'gral_admins', $this->urlMiniStaffList, array(array('access=?', 'abcdefghijkpuvy')));
		$table->rowsTemplate = array(\IPS\Theme::i()->getTemplate('main'), 'miniStaffListRow');
		$table->orderBy = 'finish, name ASC';
		$table->sortBy = $table->sortBy ?: 'id';
		$table->sortDirection = $table->sortDirection ?: 'DESC';
		$table->advancedSearch = array(
			'name' => \IPS\adminpanel\Table\SEARCH_CONTAINS_TEXT
		);
		$table->parsers = array(
			'server_id' => function($val) {
				return \IPS\Application::load('adminpanel')->getServerName($val);
			}
		);

		return $table;
	}

	protected function _staffList() {
		$table = new \IPS\adminpanel\Table\Db('focs', $this->connection, 'gral_admins', $this->urlStaffList, array(array('access=? OR access=? OR access=? OR access=? OR access=?', 'abcdefghijkpquvy', 'abcdefghijkpqruvy', 'abcdefghijkpqrsuvy', 'abcdefghijkpqrstuvy', 'abcdefghijklpqrstuvy')));
		$table->rowsTemplate = array(\IPS\Theme::i()->getTemplate('main'), 'staffListRow');
		$table->orderBy = 'finish, name ASC';
		$table->sortBy = $table->sortBy ?: 'id';
		$table->sortDirection = $table->sortDirection ?: 'DESC';
		$table->advancedSearch = array(
			'name' => \IPS\adminpanel\Table\SEARCH_CONTAINS_TEXT
		);
		$table->parsers = array(
			'server_id' => function($val) {
				return \IPS\Application::load('adminpanel')->getServerName($val);
			}
		);

		return $table;
	}

	protected function _addAdmin() {
		$form = new \IPS\Helpers\Form('formAddAdmin', '__add_now');
		$form->class = 'ipsForm_fullWidth';

		$form->add(new \IPS\Helpers\Form\Text('ap_addadmin_tag', NULL, TRUE, array('maxLength' => 32)));
		$form->add(new \IPS\Helpers\Form\Password('ap_addadmin_password', NULL, TRUE, array('minimumStrength' => 3, 'maxLength' => 24)));
		$form->add(new \IPS\Helpers\Form\Select('ap_addadmin_range', NULL, TRUE, array('options' => $this->adminAccess)));
		$form->add(new \IPS\Helpers\Form\Number('ap_addadmin_days', 1, TRUE));
		$form->add(new \IPS\Helpers\Form\Member('ap_addadmin_member', NULL, TRUE));
		$form->add(new \IPS\Helpers\Form\YesNo('ap_addadmin_member_group', NULL, TRUE, array('togglesOn' => array('ap_addadmin_member_group_name'))));
		$form->add(new \IPS\Helpers\Form\Text('ap_addadmin_member_group_name', NULL, FALSE, array(), NULL, NULL, NULL, 'ap_addadmin_member_group_name'));
		$form->add(new \IPS\Helpers\Form\Select('ap_addadmin_server', NULL, TRUE, array('options' => \IPS\Application::load('adminpanel')->getServers())));

		if($values = $form->values()) {
			$name = $values['ap_addadmin_tag'];
			$server_id = $values['ap_addadmin_server'];
			$ok = 0;

			foreach($this->db->select('*', 'gral_admins') as $row) {
				if($row['name'] == $name && ((!$row['server_id'] && $row['access'] != 'b' && $row['access'] != 'abcdeijuvy') || (!$server_id && $row['server_id']) || $row['server_id'] == $server_id)) {
					$ok = 1;
					break;
				}
			}

			if($ok == 1) {
				\IPS\Output::i()->redirect($this->urlAdminList, 'ap_addadmin_existing');
			} else {
				$this->db->insert('gral_admins', array(
					'name' => $name,
					'password' => md5($values['ap_addadmin_password']),
					'access' => $values['ap_addadmin_range'],
					'finish' => (($values['ap_addadmin_days'] == 0) ? 2000000000 : (($values['ap_addadmin_days'] * 60 * 60 * 24) + time())),
					'staff_forum_id' => \IPS\Member::loggedIn()->member_id,
					'forum_id' => $values['ap_addadmin_member']->member_id,
					'server_id' => $server_id
				));

				if($values['ap_addadmin_member']->member_id) {
					if($values['ap_addadmin_member']->member_group_id != 4 &&
					$values['ap_addadmin_member']->member_group_id != 7 &&
					$values['ap_addadmin_member']->member_group_id != 6 &&
					$values['ap_addadmin_member']->member_group_id != 12 &&
					$values['ap_addadmin_member']->member_group_id != 11 &&
					$values['ap_addadmin_member']->member_group_id != 10) {
						if($values['ap_addadmin_member_group'] && $values['ap_addadmin_member_group_name'] !== NULL) {
							\IPS\Db::i()->update('core_members', array(
								'member_group_id' => \IPS\Application::load('adminpanel')->getAccessForumId($values['ap_addadmin_range']),
								'member_group_name' => $values['ap_addadmin_member_group_name']),
							array('member_id=?', $values['ap_addadmin_member']->member_id));
						} else {
							\IPS\Db::i()->update('core_members', array(
								'member_group_id' => \IPS\Application::load('adminpanel')->getAccessForumId($values['ap_addadmin_range'])),
							array('member_id=?', $values['ap_addadmin_member']->member_id));
						}
					} else {
						if($values['ap_addadmin_member_group'] && $values['ap_addadmin_member_group_name'] !== NULL) {
							\IPS\Db::i()->update('core_members', array(
								'member_group_name' => $values['ap_addadmin_member_group_name']),
							array('member_id=?', $values['ap_addadmin_member']->member_id));
						}
					}
				}

				\IPS\Output::i()->redirect($this->urlAdminList, '__saved');
			}
		}

		return (string) $form->customTemplate(array(\call_user_func_array(array(\IPS\Theme::i(), 'getTemplate'), array('forms', 'core')), 'popupTemplate'));
	}

	protected function _editAdmin() {
		$aid = \IPS\Request::i()->aid;

		if(!$aid) {
			\IPS\Output::i()->error('No se ha especificado el #Id', 'DG/PDA/8', 403, '');
		}

		try {
			$row = $this->db->select('*', 'gral_admins', array('id=?', $aid), NULL, 1)->first();
		} catch(\UnderflowException $e) {
			\IPS\Output::i()->error('No se ha podido obtener los datos del jugador. Inténtalo más tarde o contáctate con el desarrollador web.', 'DG/PDA/9', 403, '');
		}

		$form = new \IPS\Helpers\Form('ap_editadmin', '__edit_now');
		$form->class = 'ipsForm_fullWidth';

		$form->add(new \IPS\Helpers\Form\Text('__tag', $row['name'], TRUE, array('placeholder' => 'Ingrese el Tag CS que utilizará el jugador.', 'maxLength' => 32)));
		$form->add(new \IPS\Helpers\Form\Password('__password', NULL, FALSE, array('minimumStrength' => 3, 'maxLength' => 24)));
		$form->add(new \IPS\Helpers\Form\Select('__range', $row['access'], TRUE, array('options' => $this->adminAccess)));

		$date = new \IPS\DateTime;
		$date = $date->format('Y-m-d H:i');
		$form->add(new \IPS\Helpers\Form\Date('__finish', $row['finish'], TRUE, array('min' => new \IPS\DateTime($date), 'max' => new \IPS\DateTime('2033-05-18'), 'time' => TRUE, 'unlimited' => 2000000000, 'unlimitedLang' => '__permanently')));

		$form->add(new \IPS\Helpers\Form\Member('__member', \IPS\Member::load($row['forum_id'])->name, TRUE));
		$form->add(new \IPS\Helpers\Form\YesNo('__member_group', NULL, TRUE, array('togglesOn' => array('__member_group_name'))));
		$form->add(new \IPS\Helpers\Form\Text('__member_group_name', \IPS\Member::load($row['forum_id'])->member_group_name, FALSE, array(), NULL, NULL, NULL, '__member_group_name'));
		$form->add(new \IPS\Helpers\Form\Select('__server', $row['server_id'], TRUE, array('options' => \IPS\Application::load('adminpanel')->getServers())));

		if($values = $form->values()) {
			$password = ((!empty($values['__password'])) ? md5($values['__password']) : NULL);

			if($password == NULL) {
				$this->db->update('gral_admins', array(
					'name' => $values['__tag'],
					'access' => $values['__range'],
					'finish' => ((\is_numeric($values['__finish'])) ? $values['__finish'] : $values['__finish']->getTimestamp()),
					'staff_forum_id_edit' => \IPS\Member::loggedIn()->member_id,
					'staff_forum_id_edit_timestamp' => time(),
					'forum_id' => $values['__member']->member_id,
					'server_id' => $values['__server']
				), array('id=?', $aid));
			} else {
				$this->db->update('gral_admins', array(
					'name' => $values['__tag'],
					'password' => $password,
					'access' => $values['__range'],
					'finish' => ((\is_numeric($values['__finish'])) ? $values['__finish'] : $values['__finish']->getTimestamp()),
					'staff_forum_id_edit' => \IPS\Member::loggedIn()->member_id,
					'staff_forum_id_edit_timestamp' => time(),
					'forum_id' => $values['__member']->member_id,
					'server_id' => $values['__server']
				), array('id=?', $aid));
			}

			if($values['__member']->member_id) {
				if($values['__member']->member_group_id != 4 &&
				$values['__member']->member_group_id != 7 &&
				$values['__member']->member_group_id != 6 &&
				$values['__member']->member_group_id != 12 &&
				$values['__member']->member_group_id != 11 &&
				$values['__member']->member_group_id != 10) {
					if($values['__member_group'] && $values['__member_group_name'] !== NULL) {
						\IPS\Db::i()->update('core_members', array(
							'member_group_id' => \IPS\Application::load('adminpanel')->getAccessForumId($values['__range']),
							'member_group_name' => $values['__member_group_name']),
						array('member_id=?', $values['__member']->member_id));
					} else {
						\IPS\Db::i()->update('core_members', array(
							'member_group_id' => \IPS\Application::load('adminpanel')->getAccessForumId($values['__range'])),
						array('member_id=?', $values['__member']->member_id));
					}
				} else {
					if($values['__member_group'] && $values['__member_group_name'] !== NULL) {
						\IPS\Db::i()->update('core_members', array(
							'member_group_name' => $values['__member_group_name']),
						array('member_id=?', $values['__member']->member_id));
					}
				}
			}

			\IPS\Output::i()->redirect($this->urlAdminList, '__edited');
		}

		return (string) $form->customTemplate(array(\call_user_func_array(array(\IPS\Theme::i(), 'getTemplate'), array('forms', 'core')), 'popupTemplate'));
	}

	protected function _suspendAdmin() {
		$aid = \IPS\Request::i()->aid;

		if(!$aid) {
			\IPS\Output::i()->error('No se ha especificado el #Id', 'DG/PDA/10', 403, '');
		}

		try {
			$row = $this->db->select('*', 'gral_admins', array('id=?', $aid), NULL, 1)->first();
		} catch(\UnderflowException $e) {
			\IPS\Output::i()->error('No se ha podido obtener los datos del Admin CS. Inténtalo más tarde o contáctate con el desarrollador web.', 'DG/PDA/11', 403, '');
		}

		$form = new \IPS\Helpers\Form('ap_suspendadmin', '__suspend_now');
		$form->class = 'ipsForm_fullWidth';

		$date = new \IPS\DateTime;
		$date = $date->format('Y-m-d H:i');
		$form->add(new \IPS\Helpers\Form\Date('__finish', NULL, TRUE, array('min' => new \IPS\DateTime($date), 'max' => new \IPS\DateTime('2033-05-18'), 'time' => TRUE, 'unlimited' => 2000000000, 'unlimitedLang' => '__permanently')));

		$form->add(new \IPS\Helpers\Form\Text('__reason', NULL, TRUE, array('maxLength' => 64)));

		if($values = $form->values()) {
			if($row['suspend_id']) {
				\IPS\Output::i()->redirect($this->urlSuspendList, 'El Admin CS que intentas suspender ya está suspendido. Revisa la lista de suspensiones para más información.');
			} else {
				$lid = $this->db->insert('gral_suspends', array(
					'admin_id' => $row['id'],
					'admin_name' => $row['name'],
					'admin_forum_id' => $row['forum_id'],
					'staff_name' => \IPS\Member::loggedIn()->name,
					'staff_forum_id' => \IPS\Member::loggedIn()->member_id,
					'start' => time(),
					'finish' => ((\is_numeric($values['__finish'])) ? $values['__finish'] : $values['__finish']->getTimestamp()),
					'reason' => $values['__reason'],
					'server_id' => $row['server_id'],
					'active' => 1,
				));
				$this->db->update('gral_admins', array('suspend_id' => $lid), array('id=?', $row['id']));

				\IPS\Output::i()->redirect($this->urlSuspendList, '__suspended');
			}
		}

		return (string) $form->customTemplate(array(\call_user_func_array(array(\IPS\Theme::i(), 'getTemplate'), array('forms', 'core')), 'popupTemplate'));
	}

	protected function _removeAdmin() {
		$aid = \IPS\Request::i()->aid;

		if(!$aid) {
			\IPS\Output::i()->error('No se ha especificado el #Id', 'DG/PDA/24', 403, '');
		}

		try {
			$row = $this->db->select('*', 'gral_admins', array('id=?', $aid), NULL, 1)->first();
		} catch(\UnderflowException $e) {
			\IPS\Output::i()->error('No se ha podido obtener los datos del Admin CS. Inténtalo más tarde o contáctate con el desarrollador web.', 'DG/PDA/25', 403, '');
		}

		if($row['forum_id']) {
			$member = \IPS\Member::load($row['forum_id']);

			if($member->member_group_id != 4 &&
			$member->member_group_id != 7 &&
			$member->member_group_id != 6 &&
			$member->member_group_id != 12 &&
			$member->member_group_id != 11 &&
			$member->member_group_id != 10) {
				\IPS\Db::i()->update('core_members', array('member_group_id' => 3), array('member_id=?', $row['forum_id']));
			} else {
				\IPS\Db::i()->update('core_members', array('member_group_id' => 16), array('member_id=?', $row['forum_id']));
			}

			if(\IPS\Member::loggedIn()->member_group_name != NULL) {
				\IPS\Db::i()->update('core_members', array('member_group_name' => NULL), array('member_id=?', $row['forum_id']));
			}
		}

		$this->db->delete('gral_admins', array('id=?', $aid));

		\IPS\Output::i()->redirect($this->urlAdminList, '__removed');
	}

	protected function _myBans() {
		$table = new \IPS\adminpanel\Table\Db('focs', $this->connection, 'gral_bans', $this->urlMyBans, array(array('forum_id=?', \IPS\Member::loggedIn()->member_id)));
		$table->rowsTemplate = array(\IPS\Theme::i()->getTemplate('main'), 'banListRow');
		$table->orderBy = 'finish, user_name ASC';
		$table->sortBy = $table->sortBy ?: 'id';
		$table->sortDirection = $table->sortDirection ?: 'DESC';
		$table->advancedSearch = array(
			'user_name' => \IPS\adminpanel\Table\SEARCH_CONTAINS_TEXT,
			'user_authid' => \IPS\adminpanel\Table\SEARCH_CONTAINS_TEXT
		);
		$table->filters = array(
			'__active' => "active='1'",
			'__inactive' => "active='0'",
		);
		$table->parsers = array(
			'server_id' => function($val) {
				return \IPS\Application::load('adminpanel')->getServerName($val);
			}
		);

		return $table;
	}

	protected function _banList() {
		$table = new \IPS\adminpanel\Table\Db('focs', $this->connection, 'gral_bans', $this->urlBanList);
		$table->rowsTemplate = array(\IPS\Theme::i()->getTemplate('main'), 'banListRow');
		$table->orderBy = 'finish, user_name ASC';
		$table->sortBy = $table->sortBy ?: 'id';
		$table->sortDirection = $table->sortDirection ?: 'DESC';
		$table->advancedSearch = array(
			'user_name' => \IPS\adminpanel\Table\SEARCH_CONTAINS_TEXT,
			'user_authid' => \IPS\adminpanel\Table\SEARCH_CONTAINS_TEXT
		);
		$table->filters = array(
			'__active' => "active='1'",
			'__inactive' => "active='0'",
		);
		$table->parsers = array(
			'server_id' => function($val) {
				return \IPS\Application::load('adminpanel')->getServerName($val);
			}
		);

		return $table;
	}

	protected function _viewBan() {
		$bid = \IPS\Request::i()->bid;

		if(!$bid) {
			\IPS\Output::i()->error('No se ha especificado el #Id.', 'DG/PDA/2', 403, '');
		}

		$data = array();

		try {
			$row = $this->db->select('*', 'gral_bans', array('id=?', $bid), NULL, 1)->first();

			if(isset($row['admin_authid'])) {
				$row['admin_authid_m'] = NULL;

				if(preg_match('/^STEAM_/', $row['admin_authid'])) {
					$row['admin_authid_m'] = \IPS\Application::load('adminpanel')->convertSteamId($row['admin_authid']);
				}
			}

			if(isset($row['user_authid'])) {
				$row['user_authid_m'] = NULL;

				if(preg_match('/^STEAM_/', $row['user_authid'])) {
					$row['user_authid_m'] = \IPS\Application::load('adminpanel')->convertSteamId($row['user_authid']);
				}
			}

			$data['count_ban_totals'] = $this->db->select('COUNT(*)', 'gral_bans', array('user_authid=?', $row['user_authid']))->first();
			
			if($data['count_ban_totals']) {
				$data['count_ban_in_server_totals'] = $this->db->select('COUNT(*)', 'gral_bans', array('user_authid=? AND server_id=?', $row['user_authid'], $row['server_id']))->first();
			} else {
				$data['count_ban_in_server_totals'] = 0;
			}
		} catch(\UnderflowException $e) {
			\IPS\Output::i()->error('No se ha podido obtener los datos del ban. Inténtalo más tarde o contáctate con el desarrollador web.', 'DG/PDA/3', 403, '');
		}

		return \IPS\Theme::i()->getTemplate('main')->viewBan($row, $data);
	}

	protected function _blackListBan() {
		$bid = \IPS\Request::i()->bid;

		if(!$bid) {
			\IPS\Output::i()->error('No se ha especificado el #Id', 'DG/PDA/4', 403, '');
		}

		$this->db->update('gral_bans', array('server_id' => 0), array('id=?', $bid));

		\IPS\Output::i()->redirect($this->urlBanList, '__blackListAdd');
	}

	protected function _removeBan() {
		$bid = \IPS\Request::i()->bid;

		if(!$bid) {
			\IPS\Output::i()->error('No se ha especificado el #Id', 'DG/PDA/4', 403, '');
		}

		$this->db->update('gral_bans', array('active' => 0), array('id=?', $bid));

		\IPS\Output::i()->redirect($this->urlBanList, '__removed');
	}

	protected function _mySuspends() {
		$table = new \IPS\adminpanel\Table\Db('focs', $this->connection, 'gral_suspends', $this->urlSuspendList, array(array('staff_forum_id=?', \IPS\Member::loggedIn()->member_id)));
		$table->rowsTemplate = array(\IPS\Theme::i()->getTemplate('main'), 'suspendListRow');
		$table->orderBy = 'finish, staff_name ASC';
		$table->sortBy = $table->sortBy ?: 'id';
		$table->sortDirection = $table->sortDirection ?: 'DESC';
		$table->advancedSearch = array(
			'admin_forum_id' => \IPS\adminpanel\Table\SEARCH_MEMBER
		);
		$table->parsers = array(
			'server_id' => function($val) {
				return \IPS\Application::load('adminpanel')->getServerName($val);
			}
		);

		return (string) $table;
	}

	protected function _suspendList() {
		$table = new \IPS\adminpanel\Table\Db('focs', $this->connection, 'gral_suspends', $this->urlSuspendList);
		$table->rowsTemplate = array(\IPS\Theme::i()->getTemplate('main'), 'suspendListRow');
		$table->orderBy = 'finish, staff_name ASC';
		$table->sortBy = $table->sortBy ?: 'id';
		$table->sortDirection = $table->sortDirection ?: 'DESC';
		$table->advancedSearch = array(
			'admin_forum_id' => \IPS\adminpanel\Table\SEARCH_MEMBER
		);
		$table->parsers = array(
			'server_id' => function($val) {
				return \IPS\Application::load('adminpanel')->getServerName($val);
			}
		);

		return (string) $table;
	}

	protected function _viewSuspend() {
		$sid = \IPS\Request::i()->sid;

		if(!$sid) {
			\IPS\Output::i()->error('No se ha especificado el #Id.', 'DG/PDA/2', 403, '');
		}

		try {
			$row = $this->db->select('*', 'gral_suspends', array('id=?', $sid), NULL, 1)->first();

			if(isset($row['server_id'])) {
				$row['server_id'] = \IPS\Application::load('adminpanel')->getServerName($row['server_id']); 
			}
		} catch(\UnderflowException $e) {
			\IPS\Output::i()->error('No se ha podido obtener los datos del ban. Inténtalo más tarde o contáctate con el desarrollador web.', 'DG/PDA/3', 403, '');
		}

		return \IPS\Theme::i()->getTemplate('main')->viewSuspend($row);
	}

	protected function _editSuspend() {
		$sid = \IPS\Request::i()->sid;

		if(!$sid) {
			\IPS\Output::i()->error('No se ha especificado el #Id', 'DG/PDA/5', 403, '');
		}

		try {
			$row = $this->db->select('*', 'gral_suspends', array('id=? AND active=?', $sid, 1), NULL, 1)->first();
		} catch(\UnderflowException $e) {
			\IPS\Output::i()->error('No se ha podido obtener los datos de la Suspensión. Inténtalo más tarde o contáctate con el desarrollador web.', 'DG/PDA/6', 403, '');
		}

		$form = new \IPS\Helpers\Form('ap_editsuspend', '__edit_now');
		$form->class = 'ipsForm_fullWidth';

		$date = new \IPS\DateTime;
		$date = $date->format('Y-m-d H:i');
		$form->add(new \IPS\Helpers\Form\Date('ap_editsuspend_finish', $row['finish'], TRUE, array('min' => new \IPS\DateTime($date), 'max' => new \IPS\DateTime('2033-05-18'), 'time' => TRUE, 'unlimited' => 2000000000, 'unlimitedLang' => '__permanently')));

		$form->add(new \IPS\Helpers\Form\Text('ap_editsuspend_reason', $row['reason'], TRUE, array('maxLength' => 64)));

		if($values = $form->values()) {
			$this->db->update('gral_suspends', array(
				'finish' => ((\is_numeric($values['ap_editsuspend_finish'])) ? $values['ap_editsuspend_finish'] : $values['ap_editsuspend_finish']->getTimestamp()),
				'reason' => $values['ap_editsuspend_reason'],
			), array('id=? AND active=?', $sid, 1));

			\IPS\Output::i()->redirect($this->urlSuspendList, '__edited');
		}

		return (string) $form->customTemplate(array(\call_user_func_array(array(\IPS\Theme::i(), 'getTemplate'), array('forms', 'core')), 'popupTemplate'));
	}

	protected function _removeSuspend() {
		$sid = \IPS\Request::i()->sid;

		if(!$sid) {
			\IPS\Output::i()->error('No se ha especificado el #Id', 'DG/PDA/7', 403, '');
		}

		$this->db->update('gral_admins', array('suspend_id' => 0), array('suspend_id=?', $sid));
		$this->db->update('gral_suspends', array('active' => 0), array('id=?', $sid));

		\IPS\Output::i()->redirect($this->urlSuspendList, '__removed');
	}

	protected function _advList() {
		$table = new \IPS\adminpanel\Table\Db('focs', $this->connection, 'gral_advertisements', $this->urlAdvList);
		$table->rowsTemplate = array(\IPS\Theme::i()->getTemplate('main'), 'advListRow');
		$table->orderBy = 'server_id, message ASC';
		$table->sortBy = $table->sortBy ?: 'id';
		$table->sortDirection = $table->sortDirection ?: 'DESC';
		$table->parsers = array(
			'server_id' => function($val) {
				return \IPS\Application::load('adminpanel')->getServerName($val);
			}
		);

		return \IPS\Theme::i()->getTemplate('main')->advList($table);
	}

	protected function _addAdv() {
		$form = new \IPS\Helpers\Form('formAddAdv', '__add_now');
		$form->class = 'ipsForm_fullWidth';

		$form->add(new \IPS\Helpers\Form\Text('__message', NULL, TRUE, array('placeholder' => 'Ingrese el mensaje que contendrá el anuncio.')));
		$form->add(new \IPS\Helpers\Form\Number('__days', 1, TRUE));
		$form->add(new \IPS\Helpers\Form\Select('__server', NULL, TRUE, array('options' => \IPS\Application::load('adminpanel')->getServers())));

		if($values = $form->values()) {
			$this->db->insert('gral_advertisements', array(
				'message' => $values['__message'],
				'message_timestamp' => (($values['__days'] == 0) ? 2000000000 : (($values['__days'] * 60 * 60 * 24) + time())),
				'server_id' => $values['__server'],
			));

			\IPS\Output::i()->redirect($this->urlAdvList, '__saved');
		}

		return (string) $form->customTemplate(array(\call_user_func_array(array(\IPS\Theme::i(), 'getTemplate'), array('forms', 'core')), 'popupTemplate'));
	}

	protected function _editAdv() {
		$advid = \IPS\Request::i()->advid;

		if(!$advid) {
			\IPS\Output::i()->error('No se ha especificado el #Id.', 'DG/PDA/21', 403, '');
		}

		try {
			$row = $this->db->select('*', 'gral_advertisements', array('id=?', $advid), NULL, 1)->first();
		} catch(\UnderflowException $e) {
			\IPS\Output::i()->error('No se ha podido obtener los datos del anuncio. Inténtalo más tarde o contáctate con el desarrollador web.', 'DG/PDA/23', 403, '');
		}

		$form = new \IPS\Helpers\Form('ap_editadv', '__edit_now');
		$form->class = 'ipsForm_fullWidth';

		$form->add(new \IPS\Helpers\Form\Text('__message', $row['message'], TRUE, array('placeholder' => 'Ingrese el mensaje que contendrá el anuncio.')));
		
		$date = new \IPS\DateTime;
		$date = $date->format('Y-m-d H:i');
		$form->add(new \IPS\Helpers\Form\Date('__finish', $row['message_timestamp'], TRUE, array('min' => new \IPS\DateTime($date), 'max' => new \IPS\DateTime('2033-05-18'), 'time' => TRUE, 'unlimited' => 2000000000, 'unlimitedLang' => '__permanently')));

		$form->add(new \IPS\Helpers\Form\Select('__server', $row['server_id'], TRUE, array('options' => \IPS\Application::load('adminpanel')->getServers())));

		if($values = $form->values()) {
			$this->db->update('gral_advertisements', array(
				'message' => $values['__message'],
				'message_timestamp' => ((\is_numeric($values['__finish'])) ? $values['__finish'] : $values['__finish']->getTimestamp()),
				'server_id' => $values['__server']
			), array('id=?', $advid));

			\IPS\Output::i()->redirect($this->urlAdvList, '__edited');
		}

		return (string) $form->customTemplate(array(\call_user_func_array(array(\IPS\Theme::i(), 'getTemplate'), array('forms', 'core')), 'popupTemplate'));
	}

	protected function _removeAdv() {
		$advid = \IPS\Request::i()->advid;

		if(!$advid) {
			\IPS\Output::i()->error('No se ha especificado el #Id', 'DG/PDA/4', 403, '');
		}

		$this->db->delete('gral_advertisements', array('id=?', $advid));

		\IPS\Output::i()->redirect($this->urlAdvList, '__removed');
	}

	protected function tagExists() {
		$input = \IPS\Request::i()->input;

		try {
			$row = $this->db->select('*', 'gral_admins', array('name=?', $input))->first();
			\IPS\Output::i()->output = 'El nombre que elegiste o parte de este ya está tomado. Si estás realizando una renovación, puedes ignorar esto.';
		} catch(\UnderflowException $e) {
			\IPS\Output::i()->output = 'El nombre ('.$input.') se encuentra disponible.';
		}
	}
}