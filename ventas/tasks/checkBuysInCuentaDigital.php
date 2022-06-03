<?php
namespace IPS\ventas\tasks;

if(!\defined('\IPS\SUITE_UNIQUE_KEY')) {
	header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0').' 403 Forbidden');
	exit;
}

class _checkBuysInCuentaDigital extends \IPS\Task {
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

		foreach(\IPS\Db::i()->select("notification_buy_id, notification_external_reference", "ventas_notifications", array("ventas_buys.buy_status=?", 0))->join("ventas_buys", "ventas_buys.buy_id=ventas_notifications.notification_buy_id", "LEFT") as $row) {
			foreach($codes as $code) {
				if($code == $row["notification_external_reference"]) {
					\IPS\Db::i()->update("ventas_buys", array("buy_status" => 1), array("buy_id=?", $row["notification_buy_id"]));
				}
			}
		}

		return NULL;
	}

	public function cleanup() {
		
	}
}