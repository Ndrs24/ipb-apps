<?php
namespace IPS\servidores\tasks;

if(!\defined('\IPS\SUITE_UNIQUE_KEY')) {
	header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0').' 403 Forbidden');
	exit;
}

class _queryServers extends \IPS\Task {
	public function execute() {
		$file_gameq = \IPS\ROOT_PATH."/applications/servidores/interface/GameQ/Autoloader.php";
		require_once $file_gameq;

		$counts['players_num'] = 0;
		$counts['players_max'] = 0;

		$query = \IPS\Db::i()->select("*", "servidores_servers", array("game_protocol=?", "gameq"))->join("servidores_games", "game_id=server_game_id");
		$gq = new \GameQ\GameQ();

		foreach($query as $row) {
     		$server = array('id' => $row['server_id'], 'type' => $row['game_type'], 'host' => $row['server_ip']);
			
			try {
				$gq->clearServers();
				$gq->addServer($server);

				$gq->setOption('write_wait', 10);
				$gq->setOption('timeout', 3);

				$results = $gq->process();

				foreach($results as $id => $data) {
					$server = \IPS\servidores\Servers::load($id);

					if($data['gq_online'] == false) {
						if($server->longname == NULL || $server->longname == '') {
							$server->longname = $server->shortname;
						}

						$server->players_num = 0;
						$server->players_info = '{}';
						$server->online = 0;
						$server->save();

						continue;
					}

					if($data['gq_hostname']) {
						$server->longname = $data['gq_hostname'];
					} else {
						$server->longname = $server->shortname;
					}

					$server->connect = $data['gq_joinlink'];

					$players_num = (($data['gq_numplayers']) ? ($data['gq_numplayers'] - ($data['num_bots'] ? $data['num_bots'] : 0)) : 0);
					$server->players_num = $players_num;
					$counts['players_num'] += $players_num;

					$players_max = (($data['gq_maxplayers']) ? $data['gq_maxplayers'] : 0);
					$server->players_max = $players_max;
					$counts['players_max'] += $players_max;

					$players_most = (int) $server->players_most;

					if($players_most < $data['gq_numplayers']) {
						$server->players_most = $data['gq_numplayers'];
						$server->players_most_time = time();
					}

					$server->players_info = json_encode($data['players']);

					$server->map = $data['gq_mapname'];
					$server->nextmap = $data['amx_nextmap'];

					$server->online = 1;

					$server->save();

					$stat = new \IPS\servidores\Stats;

					$stat->server_id = $server->id;
					$stat->players = $server->players_num;
					$stat->players_time = time();

					$stat->save();
				}
			} catch(\Exception $e) {
				\IPS\Log::log($e, "QueryServerERR: SID: {$row['server_id']}");
				continue;
			}
  		}

		if($counts['players_num'] > \IPS\Settings::i()->s_players_num_record) {
			$settings['s_players_num_record'] = $counts['players_num'];
			$settings['s_players_max_record'] = $counts['players_max'];
			$settings['s_time_record'] = time();
		}

		$settings['s_time_refreshed'] = time();

		\IPS\Settings::i()->changeValues($settings);
		\IPS\Data\Cache::i()->clearAll();
		
		return NULL;
	}
	
	public function cleanup() {
		
	}
}