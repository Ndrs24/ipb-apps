<?php
namespace IPS\ventas;

class _Application extends \IPS\Application {
	protected function get__icon() {
		return 'shopping-cart';
	}

	public function getConnection() {
		$connection = array(
			'sql_host' => \IPS\Settings::i()->v_sql_host,
			'sql_port' => \IPS\Settings::i()->v_sql_port,
			'sql_user' => \IPS\Settings::i()->v_sql_user,
			'sql_pass' => \IPS\Settings::i()->v_sql_password,
			'sql_database' => \IPS\Settings::i()->v_sql_database,
			'sql_tbl_prefix' => \IPS\Settings::i()->v_sql_prefix
		);

		if(\IPS\Settings::i()->v_sql_charset == 'utf8mb4') {
			$connection['sql_utf8mb4'] = true;
		}

		return $connection;
	}

	public function getDb() {
		return \IPS\Db::i('focs', \IPS\Application::load('ventas')->getConnection());
	}

	public function getConnectionZE() {
		$connection = array(
			'sql_host' => \IPS\Settings::i()->v_sql_host,
			'sql_port' => \IPS\Settings::i()->v_sql_port,
			'sql_user' => '',
			'sql_pass' => '',
			'sql_database' => '',
			'sql_tbl_prefix' => \IPS\Settings::i()->v_sql_prefix
		);

		if(\IPS\Settings::i()->v_sql_charset == 'utf8mb4') {
			$connection['sql_utf8mb4'] = true;
		}

		return $connection;
	}

	public function getDbZE() {
		return \IPS\Db::i('focs_ze', \IPS\Application::load('ventas')->getConnectionZE());
	}

	public function getConnectionZPU() {
		$connection = array(
			'sql_host' => \IPS\Settings::i()->v_sql_host,
			'sql_port' => \IPS\Settings::i()->v_sql_port,
			'sql_user' => '',
			'sql_pass' => '',
			'sql_database' => '',
			'sql_tbl_prefix' => \IPS\Settings::i()->v_sql_prefix
		);

		if(\IPS\Settings::i()->v_sql_charset == 'utf8mb4') {
			$connection['sql_utf8mb4'] = true;
		}

		return $connection;
	}

	public function getDbZPU() {
		return \IPS\Db::i('focs_zpu', \IPS\Application::load('ventas')->getConnectionZPU());
	}

	public function getConnectionZPA() {
		$connection = array(
			'sql_host' => \IPS\Settings::i()->v_sql_host,
			'sql_port' => \IPS\Settings::i()->v_sql_port,
			'sql_user' => '',
			'sql_pass' => '',
			'sql_database' => '',
			'sql_tbl_prefix' => \IPS\Settings::i()->v_sql_prefix
		);

		if(\IPS\Settings::i()->v_sql_charset == 'utf8mb4') {
			$connection['sql_utf8mb4'] = true;
		}

		return $connection;
	}

	public function getDbZPA() {
		return \IPS\Db::i('focs_zpa', \IPS\Application::load('ventas')->getConnectionZPA());
	}

	public function getVIPs() {
		$db = \IPS\Db::i('focs', array(
			'sql_host' => \IPS\Settings::i()->v_sql_host,
			'sql_port' => \IPS\Settings::i()->v_sql_port,
			'sql_user' => \IPS\Settings::i()->v_sql_user,
			'sql_pass' => \IPS\Settings::i()->v_sql_password,
			'sql_database' => \IPS\Settings::i()->v_sql_database,
			'sql_tbl_prefix' => \IPS\Settings::i()->v_sql_prefix
		));
		$c = 0;

		foreach($db->select('*', 'gral_admins', array('access=?', 'b'), 'id ASC') as $row) {
			++$c;
		}

		return $c;
	}

	public function getAdminCSs() {
		$db = \IPS\Db::i('focs', array(
			'sql_host' => \IPS\Settings::i()->v_sql_host,
			'sql_port' => \IPS\Settings::i()->v_sql_port,
			'sql_user' => \IPS\Settings::i()->v_sql_user,
			'sql_pass' => \IPS\Settings::i()->v_sql_password,
			'sql_database' => \IPS\Settings::i()->v_sql_database,
			'sql_tbl_prefix' => \IPS\Settings::i()->v_sql_prefix
		));
		$c = 0;

		foreach($db->select('*', 'gral_admins', array('access=?', 'abcdeijuvy'), 'id ASC') as $row) {
			++$c;
		}

		return $c;
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
			$name = 'Todos';
		} else {
			try {
				$name = \IPS\Db::i()->select('server_shortname', 'servidores_servers', array('server_position=?', $server_id), 'server_position ASC')->first();
			} catch(\UnderflowException $e) {
				$name = 'no-asignado';
			}
		}

		return $name;
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

	public function canAccessLista() {
		return (bool) \IPS\Member::loggedIn()->inGroup(explode(',', \IPS\Settings::i()->v_access_list));
	}
}