<?php
namespace IPS\top15\Top15;

class _ZombieEscape extends \IPS\Node\Model {
	public static function getTop15() {
		\IPS\Output::i()->jsFiles = array_merge(\IPS\Output::i()->jsFiles, \IPS\Output::i()->js('main.js', 'top15', 'interface'));

		$db = \IPS\Db::i('focs_ze', array(
			'sql_host' => \IPS\Settings::i()->s_sql_host,
			'sql_port' => \IPS\Settings::i()->s_sql_port,
			'sql_user' => '',
			'sql_pass' => '',
			'sql_database' => '',
			'sql_tbl_prefix' => \IPS\Settings::i()->s_sql_prefix
		));
		$data = [];

		foreach($db->select('ze3_accounts.name, ze3_accounts.vinc, ze3_pjs.level, ze3_pjs.exp', 'ze3_pjs', array('ze3_accounts.not_top15=?', 0), 'ze3_pjs.level DESC, ze3_pjs.exp DESC', 15)->join('ze3_accounts', 'ze3_accounts.id=ze3_pjs.acc_id', 'LEFT') as $row) {
			$data[1][] = $row;
		}

		foreach($db->select('ze3_accounts.name, ze3_accounts.vinc, ze3_pjs_stats.time_played', 'ze3_pjs_stats', array('ze3_accounts.not_top15=?', 0), 'ze3_pjs_stats.time_played DESC', 15)->join('ze3_accounts', 'ze3_accounts.id=ze3_pjs_stats.acc_id', 'LEFT') as $row) {
			$data[2][] = $row;
		}

		return $data;
	}
}