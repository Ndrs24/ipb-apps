<?php
namespace IPS\ventas\modules\front\register;

if(!\defined('\IPS\SUITE_UNIQUE_KEY')) {
	header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0').' 403 Forbidden');
	exit;
}

class _zpa extends \IPS\Dispatcher\Controller {
	public $dbzpa;

	public function execute() {
		\IPS\Session::i()->setLocation(\IPS\Http\Url::internal('app=ventas&module=register&controller=zpa', 'front', 'register_zpa'), array(), 'v_register_viewing');

		\IPS\Output::i()->title	= \IPS\Member::loggedIn()->language()->addToStack('module__ventas_register');
		\IPS\Output::i()->breadcrumb['module'] = array(NULL, \IPS\Member::loggedIn()->language()->addToStack('module__ventas_register'));
		\IPS\Output::i()->sidebar['enabled'] = FALSE;

		$this->dbzpa = \IPS\Application::load('ventas')->getDbZPA();
		parent::execute();
	}

	protected function manage() {
		$member_id = \IPS\Member::loggedIn()->member_id;

		if(!$member_id) {
			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=core&module=system&controller=login', 'front', 'login'));
		}

		$data = array();
		$c = 0;

		foreach($this->dbzpa->select('zp8_buys.id as buy_id, zp8_buys.p_humans, zp8_buys.p_zombies, zp8_buys.p_legacy, zp8_buys.money, zp8_buys.diamonds, zp8_buys.bought_money, zp8_buys.bought_image', 'zp8_accounts', array('vinc=? AND bought_ok=?', $member_id, -1))->join('zp8_buys', 'zp8_buys.acc_id=zp8_accounts.id', 'LEFT') as $row) {
			if($row['buy_id'] && $row['bought_image'] === NULL) {
				if($row['bought_money'] == 1) {
					$data[$row['buy_id']] = "(Perú) {$row['p_humans']} pH ~ {$row['p_zombies']} pZ ~ {$row['p_legacy']} pL ~ {$row['money']} SALDO ~ {$row['diamonds']} DIAMANTES";
				} else if($row['bought_money'] == 2) {
					$data[$row['buy_id']] = "(Argentina) {$row['p_humans']} pH ~ {$row['p_zombies']} pZ ~ {$row['p_legacy']} pL ~ {$row['money']} SALDO ~ {$row['diamonds']} DIAMANTES";
				} else if($row['bought_money'] == 3) {
					$data[$row['buy_id']] = "(Paypal) {$row['p_humans']} pH ~ {$row['p_zombies']} pZ ~ {$row['p_legacy']} pL ~ {$row['money']} SALDO ~ {$row['diamonds']} DIAMANTES";
				} else if($row['bought_money'] == 4) {
					$data[$row['buy_id']] = "(Chile) {$row['p_humans']} pH ~ {$row['p_zombies']} pZ ~ {$row['p_legacy']} pL ~ {$row['money']} SALDO ~ {$row['diamonds']} DIAMANTES";
				}

				++$c;
			}
		}

		$form = new \IPS\Helpers\Form('formZPARegister', '__register');
		$form->class = 'ipsForm_vertical';

		$form->add(new \IPS\Helpers\Form\Select('vr_data', NULL, TRUE, array('options' => $data)));
		$form->add(new \IPS\Helpers\Form\Upload('vr_payment_image', NULL, TRUE, array('postKey' => NULL, 'temporary' => TRUE, 'allowedFileTypes' => array('bmp', 'jpg', 'png'))));

		if($values = $form->values()) {
			$client_id = "3cbda33525c2be1";
			$image = file_get_contents($values['vr_payment_image']);

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, 'https://api.imgur.com/3/image.json');
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Client-ID '.$client_id));
			curl_setopt($ch, CURLOPT_POSTFIELDS, array('image' => base64_encode($image)));

			$reply = curl_exec($ch);
			curl_close($ch);
			$reply = json_decode($reply);

			$this->dbzpa->update('zp8_buys', array(
				'bought_image' => $reply->data->link,
			), array('id=? AND bought_ok=?', $values['vr_data'], -1));

			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=forums&module=forums&controller=index', 'front', 'forums'), 'vr_success');
		}

		$form = $form->customTemplate(array(\call_user_func_array(array(\IPS\Theme::i(), 'getTemplate'), array('forms', 'core')), 'popupTemplate'));
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('register')->registerZPA($c, $form);
	}
}