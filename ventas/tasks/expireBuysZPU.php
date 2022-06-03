<?php
namespace IPS\ventas\tasks;

if(!\defined('\IPS\SUITE_UNIQUE_KEY')) {
	header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0').' 403 Forbidden');
	exit;
}

class _expireBuysZPU extends \IPS\Task {
	public function execute() {
		$db_zpu = \IPS\Application::load('ventas')->getDbZPU();
		$query_zpu = $db_zpu->select("forum_saldo_payments.buy_id, forum_saldo_payments.timestamp", "forum_saldo_payments", array("ok=?", -1))->join("forum_saldo", "forum_saldo.ID=forum_saldo_payments.buy_id", "LEFT");

		foreach($query_zpu as $row_zpu) {
			$finish = ($row_zpu['timestamp'] + 604800); // 7 días

			if(time() > $finish) {
				$db_zpu->update('forum_saldo', array('Deprecated' => -6), array('ID=?', $row_zpu['buy_id']));
				$db_zpu->update('forum_saldo_payments', array('ok' => -6), array('buy_id=?', $row_zpu['buy_id']));
			}
		}

		return NULL;
	}
	
	public function cleanup() {
		
	}
}