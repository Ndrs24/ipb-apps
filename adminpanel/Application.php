<?php
namespace IPS\adminpanel;

class _Application extends \IPS\Application {
	protected function get__icon() {
		return 'user-secret';
	}

	public function getConnection() {
		$connection = array(
			'sql_host' => \IPS\Settings::i()->ap_sql_host,
			'sql_port' => \IPS\Settings::i()->ap_sql_port,
			'sql_user' => \IPS\Settings::i()->ap_sql_user,
			'sql_pass' => \IPS\Settings::i()->ap_sql_password,
			'sql_database' => \IPS\Settings::i()->ap_sql_database,
			'sql_tbl_prefix' => \IPS\Settings::i()->ap_sql_prefix
		);

		if(\IPS\Settings::i()->ap_sql_charset == 'utf8mb4') {
			$connection['sql_utf8mb4'] = true;
		}

		return $connection;
	}

	public function getDb() {
		return \IPS\Db::i('focs', \IPS\Application::load('adminpanel')->getConnection());
	}

	public function getConnectionZE() {
		$connection = array(
			'sql_host' => \IPS\Settings::i()->ap_sql_host,
			'sql_port' => \IPS\Settings::i()->ap_sql_port,
			'sql_user' => '',
			'sql_pass' => '',
			'sql_database' => '',
			'sql_tbl_prefix' => \IPS\Settings::i()->ap_sql_prefix
		);

		if(\IPS\Settings::i()->ap_sql_charset == 'utf8mb4') {
			$connection['sql_utf8mb4'] = true;
		}

		return $connection;
	}

	public function getDbZE() {
		return \IPS\Db::i('focs_ze', \IPS\Application::load('adminpanel')->getConnectionZE());
	}

	public function getConnectionZPA() {
		$connection = array(
			'sql_host' => \IPS\Settings::i()->ap_sql_host,
			'sql_port' => \IPS\Settings::i()->ap_sql_port,
			'sql_user' => '',
			'sql_pass' => '',
			'sql_database' => '',
			'sql_tbl_prefix' => \IPS\Settings::i()->ap_sql_prefix
		);

		if(\IPS\Settings::i()->ap_sql_charset == 'utf8mb4') {
			$connection['sql_utf8mb4'] = true;
		}

		return $connection;
	}

	public function getDbZPA() {
		return \IPS\Db::i('focs_zpa', \IPS\Application::load('adminpanel')->getConnectionZPA());
	}

	public function getAccessForumId($access) {
		$forum_id = NULL;

		switch($access) {
			case 'abcdefghijklpqrstuvy': {
				$forum_id = 4;
				break;
			} case 'abcdefghijkpqrstuvy': {
				$forum_id = 7;
				break;
			} case 'abcdefghijkpqrsuvy': {
				$forum_id = 6;
				break;
			} case 'abcdefghijkpqruvy': {
				$forum_id = 12;
				break;
			} case 'abcdefghijkpquvy': {
				$forum_id = 11;
				break;
			} case 'abcdefghijkpuvy': {
				$forum_id = 10;
				break;
			} case 'abcdeijuvy': {
				$forum_id = 9;
				break;
			} case 'b': {
				$forum_id = 8;
				break;
			} default: {
				$forum_id = 3;
				break;
			}
		}

		return $forum_id;
	}

	public function getAccessName($access) {
		$admin_access = NULL;

		switch($access) {
			case 'abcdefghijklpqrstuvy': {
				$admin_access = 'Director';
				break;
			} case 'abcdefghijkpqrstuvy': {
				$admin_access = 'Super moderador';
				break;
			} case 'abcdefghijkpqrsuvy': {
				$admin_access = 'Moderador';
				break;
			} case 'abcdefghijkpqruvy': {
				$admin_access = 'Desarrollador';
				break;
			} case 'abcdefghijkpquvy': {
				$admin_access = 'Manager';
				break;
			} case 'abcdefghijkpuvy': {
				$admin_access = 'Capitán';
				break;
			} case 'abcdeijuvy': {
				$admin_access = 'Admin CS';
				break;
			} case 'b': {
				$admin_access = 'VIP';
				break;
			} default: {
				$admin_access = 'Usuario';
				break;
			}
		}

		return $admin_access;
	}

	public function getServers() {
		$sv_name = array(0 => 'Todos');

		if(\IPS\Application::load('servidores')) {
			$c = 1;

			foreach(\IPS\Db::i()->select('server_shortname', 'servidores_servers', NULL, 'server_position ASC') as $name) {
				if($c < 10) {
					$sv_name[$c] = "#0".$c." ".$name;
				} else {
					$sv_name[$c] = "#".$c." ".$name;
				}

				++$c;
			}
		} else {
			$sv_name[1] = " - - - ";
		}

		return $sv_name;
	}

	public function getServerName($server_id) {
		$name = NULL;

		if($server_id == 0) {
			$name = 'Todos los Servidores';
		} else {
			try {
				$name = \IPS\Db::i()->select('server_shortname', 'servidores_servers', array('server_position=?', $server_id), 'server_position ASC')->first();
			} catch(\UnderflowException $e) {
				$name = 'no-asignado';
			}
		}

		return $name;
	}

	public function getUserVIP($member_id) {
		$db = \IPS\Db::i('focs', array(
			'sql_host' => \IPS\Settings::i()->ap_sql_host,
			'sql_user' => \IPS\Settings::i()->ap_sql_user,
			'sql_pass' => \IPS\Settings::i()->ap_sql_password,
			'sql_database' => \IPS\Settings::i()->ap_sql_database
		));
		$c = 0;

		foreach($db->select('*', 'gral_admins', array('access=? AND forum_id=?', 'b', $member_id), 'id ASC') as $row) {
			++$c;
		}

		return $c;
	}

	public function getUserAdminCS($member_id) {
		$db = \IPS\Db::i('focs', array(
			'sql_host' => \IPS\Settings::i()->ap_sql_host,
			'sql_user' => \IPS\Settings::i()->ap_sql_user,
			'sql_pass' => \IPS\Settings::i()->ap_sql_password,
			'sql_database' => \IPS\Settings::i()->ap_sql_database
		));
		$c = 0;

		foreach($db->select('*', 'gral_admins', array('access<>? AND forum_id=?', 'b', $member_id), 'id ASC') as $row) {
			++$c;
		}

		return $c;
	}

	public function convertSteamId($steamid) {
		$server = '0';
		$authid = '0';
		$temp = \strtok($steamid, ':');

		while(($temp = \strtok(':')) !== false) { 
			$temp2 = \strtok(':');

			if($temp2 !== false) {
				$server = $temp;
				$authid = $temp2;
			}
		} 

		if($authid == '0') {
			return '0';
		}

		$steamid_64 = bcmul($authid, '2');
		$steamid_64 = bcadd($steamid_64, bcadd('76561197960265728', $server));

		return $steamid_64;
	}

	public function canVipList() {
		return (bool) \IPS\Member::loggedIn()->inGroup(array_filter(explode(',', \IPS\Settings::i()->ap_viplist_access)));
	}

	public function canAdminList() {
		return (bool) \IPS\Member::loggedIn()->inGroup(array_filter(explode(',', \IPS\Settings::i()->ap_adminlist_access)));
	}

	public function canMiniStaffList() {
		return (bool) \IPS\Member::loggedIn()->inGroup(array_filter(explode(',', \IPS\Settings::i()->ap_ministafflist_access)));
	}

	public function canStaffList() {
		return (bool) \IPS\Member::loggedIn()->inGroup(array_filter(explode(',', \IPS\Settings::i()->ap_stafflist_access)));
	}
	
	public function canAddAdmin() {
		return (bool) \IPS\Member::loggedIn()->inGroup(array_filter(explode(',', \IPS\Settings::i()->ap_addadmin_access)));
	}

	public function canBanList() {
		return (bool) \IPS\Member::loggedIn()->inGroup(array_filter(explode(',', \IPS\Settings::i()->ap_banlist_access)));
	}

	public function canEditBan() {
		return (bool) \IPS\Member::loggedIn()->inGroup(array_filter(explode(',', \IPS\Settings::i()->ap_editban_access)));
	}

	public function canSuspendList() {
		return (bool) \IPS\Member::loggedIn()->inGroup(array_filter(explode(',', \IPS\Settings::i()->ap_suspendlist_access)));
	}

	public function canEditSuspend() {
		return (bool) \IPS\Member::loggedIn()->inGroup(array_filter(explode(',', \IPS\Settings::i()->ap_editsuspend_access)));
	}

	public function canAdvList() {
		return (bool) \IPS\Member::loggedIn()->inGroup(array_filter(explode(',', \IPS\Settings::i()->ap_advlist_access)));
	}

	public function canAddAdv() {
		return (bool) \IPS\Member::loggedIn()->inGroup(array_filter(explode(',', \IPS\Settings::i()->ap_addadv_access)));
	}
}