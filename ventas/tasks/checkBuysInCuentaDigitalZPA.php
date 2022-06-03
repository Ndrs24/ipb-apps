<?php
namespace IPS\ventas\tasks;

if(!\defined('\IPS\SUITE_UNIQUE_KEY')) {
	header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0').' 403 Forbidden');
	exit;
}

class _checkBuysInCuentaDigitalZPA extends \IPS\Task {
	public function execute() {
		$curl = curl_init();
		$fields = array('control' => ''); // Acá pongo la credencial que admite las operaciones desde la API de CuentaDigital
		$fields_str = \http_build_query($fields);

		curl_setopt($curl, CURLOPT_URL, "https://www.cuentadigital.com/exportacion.php");
		curl_setopt($curl, CURLOPT_POST, TRUE);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $fields_str);

		$reply = curl_exec($curl);

		curl_close($curl);

		$reply_data = \json_encode($reply);
		$reply_data = \str_replace("\"", "", $reply_data);
		$reply_data = \str_replace('\n', " ", $reply_data);

		$data_operations = \explode(" ", $reply_data);
		$data_operations_separated = array();
		$codes = array();

		for($i = 0; $i < (\count($data_operations) - 1); ++$i) {
			$data_operations_separated[] = \explode("|", $data_operations[$i]);
		}

		foreach($data_operations_separated as $data) {
			$codes[] = $data[4];
		}

		$db = \IPS\Application::load('ventas')->getDbZPA();

		foreach($db->select('zp8_payments.buy_id, zp8_buys.acc_id, zp8_payments.code', 'zp8_payments', array('zp8_payments.ok=?', -1))->join('zp8_buys', 'zp8_buys.id=zp8_payments.buy_id', 'LEFT') as $row) {
			foreach($codes as $code) {
				if($code == $row['code']) {
					$db->update('zp8_buys', array('bought_ok' => 0), array('id=?', $row['buy_id']));
					$db->update('zp8_payments', array('ok' => 0), array('buy_id=?', $row['buy_id']));
					$db->update('zp8_pjs', array('bought_ok' => 1), array('acc_id=? AND bought_ok=?', $row['acc_id'], 0));
				}
			}
		}

		return NULL;
	}
	
	public function cleanup() {
		
	}
}