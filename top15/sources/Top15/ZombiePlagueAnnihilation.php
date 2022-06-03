<?php
namespace IPS\top15\Top15;

class _ZombiePlagueAnnihilation extends \IPS\Node\Model {
	public static function getTop15() {
		\IPS\Output::i()->jsFiles = array_merge(\IPS\Output::i()->jsFiles, \IPS\Output::i()->js('main.js', 'top15', 'interface'));
		
		$db = \IPS\Db::i('focs_zpa', array(
			'sql_host' => \IPS\Settings::i()->s_sql_host,
			'sql_port' => \IPS\Settings::i()->s_sql_port,
			'sql_user' => '',
			'sql_pass' => '',
			'sql_database' => '',
			'sql_tbl_prefix' => \IPS\Settings::i()->s_sql_prefix
		));
		$data = [];

		foreach($db->select('zp8_accounts.name, zp8_accounts.vinc, zp8_pjs.reset, zp8_pjs.level, zp8_pjs.xp', 'zp8_pjs', array('zp8_accounts.not_ranking=?', 0), 'zp8_pjs.reset DESC, zp8_pjs.level DESC, zp8_pjs.xp DESC', 15)->join('zp8_accounts', 'zp8_accounts.id=zp8_pjs.acc_id', 'LEFT') as $row){
			$data[1][] = $row;
		}

		foreach($db->select('zp8_accounts.name, zp8_accounts.vinc, zp8_pjs_stats.tp_d', 'zp8_pjs_stats', array('zp8_accounts.not_ranking=?', 0), 'zp8_pjs_stats.tp_d DESC', 15)->join('zp8_accounts', 'zp8_accounts.id=zp8_pjs_stats.acc_id', 'LEFT') as $row){
			$data[2][] = $row;
		}

		foreach($db->select('zp8_accounts.name, zp8_accounts.vinc, zp8_pjs_stats.combo_max', 'zp8_pjs_stats', array('zp8_accounts.not_ranking=?', 0), 'zp8_pjs_stats.combo_max DESC', 15)->join('zp8_accounts', 'zp8_accounts.id=zp8_pjs_stats.acc_id', 'LEFT') as $row){
			$data[3][] = $row;
		}

		return $data;
	}
}