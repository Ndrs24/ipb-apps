<?php
namespace IPS\ventas\modules\front\register;

if(!\defined('\IPS\SUITE_UNIQUE_KEY')) {
	header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0').' 403 Forbidden');
	exit;
}

class _zpu extends \IPS\Dispatcher\Controller {
	public $dbzpu;
	
	public function execute() {
		\IPS\Session::i()->setLocation(\IPS\Http\Url::internal('app=ventas&module=register&controller=zpu', 'front', 'register_zpu'), array(), 'v_register_viewing');

		\IPS\Output::i()->title	= \IPS\Member::loggedIn()->language()->addToStack('module__ventas_register');
		\IPS\Output::i()->breadcrumb['module'] = array(NULL, \IPS\Member::loggedIn()->language()->addToStack('module__ventas_register'));
		\IPS\Output::i()->sidebar['enabled'] = FALSE;

		$this->dbzpu = \IPS\Application::load('ventas')->getDbZPU();
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

		foreach($this->dbzpu->select('forum_saldo.`ID` as buy_id, forum_saldo.`Saldo`, forum_saldo.`Money`, forum_saldo.`URL Image`', 'pdata', array('`Forum_ID`=? AND forum_saldo.Deprecated=?', $member_id, -1))->join('forum_saldo', 'forum_saldo.`Player ID`=pdata.`ID`', 'LEFT') as $row) {
			if($row['buy_id'] && $row['URL Image'] === NULL) {
				$data[$row['buy_id']] = "({$region[($row['Money'] - 1)]}) {$row['Saldo']} SALDO";
				++$c;
			}
		}

		$form = new \IPS\Helpers\Form('formZPURegister', '__register');
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

			$this->dbzpu->update('forum_saldo', array(
				'URL Image' => $reply->data->link,
			), array('ID=? AND Deprecated=?', $values['vr_data'], -1));

			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=forums&module=forums&controller=index', 'front', 'forums'), 'vr_success');
		}

		$form = $form->customTemplate(array(\call_user_func_array(array(\IPS\Theme::i(), 'getTemplate'), array('forms', 'core')), 'popupTemplate'));
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('register')->registerZPU($c, $form);
	}
}