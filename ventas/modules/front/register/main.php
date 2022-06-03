<?php
namespace IPS\ventas\modules\front\register;

if(!\defined('\IPS\SUITE_UNIQUE_KEY')) {
	header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0').' 403 Forbidden');
	exit;
}

class _main extends \IPS\Dispatcher\Controller {
	public function execute() {
		\IPS\Session::i()->setLocation(\IPS\Http\Url::internal('app=ventas&module=register&controller=main', 'front', 'register'), array(), 'v_register_viewing');

		\IPS\Output::i()->title	= \IPS\Member::loggedIn()->language()->addToStack('module__ventas_register');
		\IPS\Output::i()->breadcrumb['module'] = array(NULL, \IPS\Member::loggedIn()->language()->addToStack('module__ventas_register'));
		\IPS\Output::i()->sidebar['enabled'] = FALSE;

		parent::execute();
	}

	protected function manage() {
		$member_id = \IPS\Member::loggedIn()->member_id;

		if(!$member_id) {
			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=core&module=system&controller=login', 'front', 'login'));
		}

		$data = array();
		$c = 0;

		foreach(\IPS\Db::i()->select('*', 'ventas_buys', array('buy_member_id=? AND buy_status=?', $member_id, 0)) as $row) {
			if($row['buy_id'] && $row['buy_image'] === NULL) {
				if($row['buy_region'] == 1) {
					$data[$row['buy_id']] = '(Perú) '.$row['buy_benefit'].' por S/.'.$row['buy_money_real'].'';
				} else if($row['buy_region'] == 2) {
					$data[$row['buy_id']] = '(Argentina) '.$row['buy_benefit'].' por $ '.$row['buy_money_real'].'';
				} else if($row['buy_region'] == 3) {
					$data[$row['buy_id']] = '(Paypal) '.$row['buy_benefit'].' por '.$row['buy_money_real'].' USD';
				} else if($row['buy_region'] == 4) {
					$data[$row['buy_id']] = '(Chile) '.$row['buy_benefit'].' por '.$row['buy_money_real'].' CLP';
				}

				++$c;
			}
		}

		$form = new \IPS\Helpers\Form('formRegister', '__register');
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

			\IPS\Db::i()->update('ventas_buys', array(
				'buy_image' => $reply->data->link,
			), array('buy_id=? AND buy_status=?', $values['vr_data'], 0));

			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=forums&module=forums&controller=index', 'front', 'forums'), 'vr_success');
		}

		$form = $form->customTemplate(array(\call_user_func_array(array(\IPS\Theme::i(), 'getTemplate'), array('forms', 'core')), 'popupTemplate'));
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('register')->register($c, $form);
	}
}