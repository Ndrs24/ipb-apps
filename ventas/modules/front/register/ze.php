<?php
namespace IPS\ventas\modules\front\register;

if(!\defined('\IPS\SUITE_UNIQUE_KEY')) {
	header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0').' 403 Forbidden' );
	exit;
}

class _ze extends \IPS\Dispatcher\Controller {
	public $dbze;

	public function execute() {
		\IPS\Session::i()->setLocation(\IPS\Http\Url::internal('app=ventas&module=register&controller=ze', 'front', 'register_ze'), array(), 'v_register_viewing');

		\IPS\Output::i()->title	= \IPS\Member::loggedIn()->language()->addToStack('module__ventas_register');
		\IPS\Output::i()->breadcrumb['module'] = array(NULL, \IPS\Member::loggedIn()->language()->addToStack('module__ventas_register'));
		\IPS\Output::i()->sidebar['enabled'] = FALSE;

		$this->dbze = \IPS\Application::load('ventas')->getDbZE();
		parent::execute();
	}

	protected function manage() {
		$member_id = \IPS\Member::loggedIn()->member_id;

		if(!$member_id) {
			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=core&module=system&controller=login', 'front', 'login'));
		}

		$data = array();
		$c = 0;
		$region = array('Perú', 'Argentina', 'Paypal', 'Chile');

		foreach($this->dbze->select('ze3_buys.id as buy_id, ze3_buys.p_legacy, ze3_buys.bought_money, ze3_buys.bought_image', 'ze3_accounts', array('vinc=? AND bought_ok=?', $member_id, -1))->join('ze3_buys', 'ze3_buys.acc_id=ze3_accounts.id', 'LEFT') as $row) {
			if($row['buy_id'] && $row['bought_image'] === NULL) {
				$data[$row['buy_id']] = '('.$region[($row['bought_money'] - 1)].') '.$row['p_legacy'].' pL';
				++$c;
			}
		}

		$form = new \IPS\Helpers\Form('formZERegister', '__register');
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

			$this->dbze->update('ze3_buys', array(
				'bought_image' => $reply->data->link,
			), array('id=? AND bought_ok=?', $values['vr_data'], -1));

			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=forums&module=forums&controller=index', 'front', 'forums'), 'vr_success');
		}

		$form = $form->customTemplate(array(\call_user_func_array(array(\IPS\Theme::i(), 'getTemplate'), array('forms', 'core')), 'popupTemplate'));
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('register')->registerZE($c, $form);
	}
}