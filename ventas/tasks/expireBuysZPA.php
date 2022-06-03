<?php
namespace IPS\ventas\tasks;

if(!\defined('\IPS\SUITE_UNIQUE_KEY')) {
	header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0').' 403 Forbidden');
	exit;
}

class _expireBuysZPA extends \IPS\Task {
	public function execute() {
		$db_zpa = \IPS\Application::load('ventas')->getDbZPA();
		$query_zpa = $db_zpa->select("zp8_payments.buy_id, zp8_payments.timestamp", "zp8_payments", array("ok=?", -1))->join("zp8_buys", "zp8_buys.id=zp8_payments.buy_id", "LEFT");

		foreach($query_zpa as $row_zpa) {
			$finish = ($row_zpa['timestamp'] + 604800); // 7 días

			if(time() > $finish) {
				$db_zpa->update('zp8_buys', array('bought_ok' => -6), array('id=?', $row_zpa['buy_id']));
				$db_zpa->update('zp8_payments', array('ok' => -6), array('buy_id=?', $row_zpa['buy_id']));
			}
		}

		return NULL;
	}
	
	public function cleanup() {
		
	}
}