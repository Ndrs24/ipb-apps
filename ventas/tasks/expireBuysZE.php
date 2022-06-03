<?php
namespace IPS\ventas\tasks;

if(!\defined('\IPS\SUITE_UNIQUE_KEY')) {
	header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0').' 403 Forbidden');
	exit;
}

class _expireBuysZE extends \IPS\Task {
	public function execute() {
		$db = \IPS\Application::load('ventas')->getDbZE();
		$query = $db->select("ze3_payments.buy_id, ze3_payments.timestamp", "ze3_payments", array("ok=?", -1))->join("ze3_buys", "ze3_buys.id=ze3_payments.buy_id", "LEFT");

		foreach($query as $row) {
			$finish = ($row['timestamp'] + 604800); // 7 días

			if(time() > $finish) {
				$db->update('ze3_buys', array('bought_ok' => -6), array('id=?', $row['buy_id']));
				$db->update('ze3_payments', array('ok' => -6), array('buy_id=?', $row['buy_id']));
			}
		}

		return NULL;
	}
	
	public function cleanup() {
		
	}
}