<?php
require_once str_replace('applications/servidores/interface/gameqtest.php', '',str_replace('\\', '/', __FILE__)).'init.php';
require_once \IPS\ROOT_PATH.'/applications/servidores/interface/GameQ/Autoloader.php';

$ip = "45.58.126.18:27050";
$ip2 = explode(":", $ip);

echo($ip2[0]." ".$ip2[1]);

$servers = array(['type' => 'cs16', 'host' => $ip]);
$gq = new \GameQ\GameQ();

foreach($servers as $server) {
	try {
		$gq->clearServers();
		$gq->addServer($server);

		$gq->setOption('timeout', 3);

		$results = $gq->process();
		var_dump($results);
	} catch(\Exception $e) {
		echo($e."\n");
		continue;
	}
}