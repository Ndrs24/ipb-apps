<?php
namespace IPS\servidores;

class _Application extends \IPS\Application {
	protected function get__icon() {
		return 'gamepad';
	}

	public function installOther() {
		$cs16_file = \IPS\ROOT_PATH."/applications/servidores/interface/Images/cs16.png";
		$file = \IPS\File::create("servidores_fileGame2", "cs16.png", file_get_contents($cs16_file), NULL, false, NULL, false);

		\IPS\Lang::saveCustom("servidores", "s_games_1", "Counter-Strike: 1.6");
		\IPS\Db::i()->insert("servidores_games", array("game_position" => 1, "game_type" => "cs16", "game_icon" => (string) $file));
	}

	public function getConnection() {
		$connection = array(
			'sql_host' => \IPS\Settings::i()->s_sql_host,
			'sql_port' => \IPS\Settings::i()->s_sql_port,
			'sql_user' => \IPS\Settings::i()->s_sql_user,
			'sql_pass' => \IPS\Settings::i()->s_sql_password,
			'sql_database' => \IPS\Settings::i()->s_sql_database,
			'sql_tbl_prefix' => \IPS\Settings::i()->s_sql_prefix
		);

		if(\IPS\Settings::i()->s_sql_charset == 'utf8mb4') {
			$connection['sql_utf8mb4'] = true;
		}

		return $connection;
	}

	public function getDb() {
		return \IPS\Db::i('focs', \IPS\Application::load('servidores')->getConnection());
	}
}