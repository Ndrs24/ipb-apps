<?php
namespace IPS\ventas\tasks;

if(!\defined('\IPS\SUITE_UNIQUE_KEY')) {
	header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0').' 403 Forbidden');
	exit;
}

class _expireBuys extends \IPS\Task {
	public function execute() {
		$query = \IPS\Db::i()->select('buy_id, buy_timestamp', 'ventas_buys', array('buy_status=?', 0), 'buy_id ASC');

		foreach($query as $row) {
			$finish = ($row['buy_timestamp'] + 604800); // 7 días

			if(time() > $finish) {
				\IPS\Db::i()->update('ventas_buys', array('buy_status' => -5), array('buy_id=?', $row['buy_id']));
			}
		}

		return NULL;
	}
	
	public function cleanup() {
		
	}
}