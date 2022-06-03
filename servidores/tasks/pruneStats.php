<?php
namespace IPS\servidores\tasks;

if(!\defined('\IPS\SUITE_UNIQUE_KEY')) {
	header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0').' 403 Forbidden');
	exit;
}

class _pruneStats extends \IPS\Task {
	public function execute() {
		$time = (time() - 3024000);
		$result = \IPS\Db::i()->select("*", "servidores_stats", array("stat_players_time<?", $time));

		foreach($result as $row) {
			$stats = \IPS\servidores\Stats::load($row['stat_id']);
			$stats->delete();
		}

		return NULL;
	}
	
	public function cleanup() {
		
	}
}