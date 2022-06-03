<?php
namespace IPS\ventas\modules\front\main;

if(!\defined('\IPS\SUITE_UNIQUE_KEY')) {
	header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
	exit;
}

const ZOMBIE_ESCAPE = 1;
const ZOMBIE_PLAGUE_UMBRELLA = 2;
const ZOMBIE_PLAGUE_ANNIHILATION = 8;

const MEMBER_ID_BOT = 9;

const CODE_FOR_VIP = 1;
const CODE_FOR_ADMIN = 2;
const CODE_FOR_ZE = 3;
const CODE_FOR_ZPU = 4;
const CODE_FOR_ZPA = 5;

class _main extends \IPS\Dispatcher\Controller {
	public $url;
	public $dbze;
	public $dbzpu;
	public $dbzpa;

	public function execute() {
		$this->url = \IPS\Http\Url::internal('app=ventas&module=main&controller=main', 'front', 'ventas');

		\IPS\Session::i()->setLocation($this->url, array(), 'v_viewing');

		\IPS\Output::i()->title	= \IPS\Member::loggedIn()->language()->addToStack('module__ventas_main');
		\IPS\Output::i()->breadcrumb['module'] = array($this->url, \IPS\Member::loggedIn()->language()->addToStack('module__ventas_main'));
		\IPS\Output::i()->sidebar['enabled'] = FALSE;

		\IPS\Output::i()->cssFiles = array_merge(\IPS\Output::i()->cssFiles, \IPS\Theme::i()->css('main.css', 'ventas', 'front'));

		$this->dbze = \IPS\Application::load('ventas')->getDbZE();
		$this->dbzpu = \IPS\Application::load('ventas')->getDbZPU();
		$this->dbzpa = \IPS\Application::load('ventas')->getDbZPA();

		parent::execute();
	}

	protected function manage() {
		if(!\IPS\Settings::i()->v_enable) {
			\IPS\Output::i()->error('La sección -Ventas- está deshabilitada temporalmente.', 'V/0', 403, '');
		}

		if(!\IPS\Member::loggedIn()->inGroup(array_filter(explode(',', \IPS\Settings::i()->v_access)))) {
			\IPS\Output::i()->error('No tienes permisos para acceder a la sección.', 'V/1', 403, '');
		}

		$data['member_id'] = \IPS\Member::loggedIn()->member_id;
		$data['name'] = \IPS\Member::loggedIn()->name;
		$data['email'] = \IPS\Member::loggedIn()->email;
		$data['promo_enable'] = \IPS\Settings::i()->v_b_promo_enable;
		$data['promo_extra_double'] = \IPS\Settings::i()->v_b_promo_extra_double;
		$data['promo_extra'] = \IPS\Settings::i()->v_b_promo_extra;

		\IPS\Output::i()->jsFiles = array_merge(\IPS\Output::i()->jsFiles, \IPS\Output::i()->js('ventas.js', 'ventas', 'interface'));
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('main')->main($this->url, $data);
	}

	protected function vipData() {
		if(!\IPS\Member::loggedIn()->member_id) {
			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=core&module=system&controller=login', 'front', 'login'));
		}

		$data['servers'] = \IPS\Application::load('ventas')->getServers();

		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('main')->vipDataHtml($data);
	}

	protected function adminData() {
		if(!\IPS\Member::loggedIn()->member_id) {
			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=core&module=system&controller=login', 'front', 'login'));
		}

		$data['servers'] = \IPS\Application::load('ventas')->getServers();

		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('main')->adminDataHtml($data);
	}

	// status (vip y admin)
	// 
	// -4 = Pago devuelto
	// -3 = Compra cancelada
	// -2 = Compra rechazada
	// -1 = Compra pendiente
	// 0 = Cuando el usuario registró la compra
	// 1 = Compra aprobada
	//  - Esto lo hace ya cuando las compras se hayan pagado (response mercado pago y cuenta digital)
	//  - También cuando lo hacen manualmente desde el Admin Panel (compras manuales)
	// 2 = Compra aprobada y acreditada
	//  - Mediante un task, se acreditan los beneficios

	// status (recursos)
	// 
	// -5 = Pago devuelto
	// -4 = Compra cancelada
	// -3 = Compra rechazada
	// -2 = Compra pendiente
	// -1 = Cuando el usuario registró la compra
	// 0 = Compra aprobada
	//  - Esto lo hace ya cuando las compras se hayan pagado (response mercado pago y cuenta digital)
	//  - También cuando lo hacen manualmente desde el Admin Panel (compras manuales)
	// 1 = Compra aprobada y acreditada
	//  - Mediante el plugin, se acreditan los recursos

	protected function sendData() {
		$member_id = \IPS\Member::loggedIn()->member_id;
		$name = \IPS\Member::loggedIn()->name;
		$email = \IPS\Member::loggedIn()->email;

		if(!$member_id || $name == NULL || $email == NULL) {
			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=core&module=system&controller=login', 'front', 'login'));
		}

		$benefit = \IPS\Request::i()->benefit;
		$region = \IPS\Request::i()->region;
		$days = \IPS\Request::i()->days;

		if(\IPS\Settings::i()->v_b_promo_enable) {
			if(\IPS\Settings::i()->v_b_promo_extra_double) {
				$days_extras = $days;
			} else {
				if(\IPS\Settings::i()->v_b_promo_extra) {
					$days_extras = (\IPS\Settings::i()->v_b_promo_extra * 30);
				} else {
					$days_extras = 0;
				}
			}
		} else {
			$days_extras = 0;
		}

		$money_real = \IPS\Request::i()->money_real;
		$time = time();

		if(\stripos($benefit, 'vip') === FALSE) {
			$benefit_int = CODE_FOR_ADMIN;
			$code = self::generateCode(CODE_FOR_ADMIN);
		} else {
			$benefit_int = CODE_FOR_VIP;
			$code = self::generateCode(CODE_FOR_VIP);
		}

		$buy_id = \IPS\Db::i()->insert('ventas_buys', array(
			'buy_member_id' => $member_id,
			'buy_name' => $name,
			'buy_email' => $email,
			'buy_tagcs' => \IPS\Request::i()->tagcs,
			'buy_setinfo' => \IPS\Request::i()->setinfo,
			'buy_benefit' => $benefit,
			'buy_region' => ($region + 1),
			'buy_days' => $days,
			'buy_days_extras' => $days_extras,
			'buy_server' => \IPS\Request::i()->server,
			'buy_money_real' => $money_real,
			'buy_image' => NULL,
			'buy_timestamp' => $time,
			'buy_status' => 0
		));

		$notification_id = \IPS\Db::i()->insert('ventas_notifications', array(
			'notification_buy_id' => $buy_id,
			'notification_external_reference' => $code
		));

		$pm_title = "Pedido de {$benefit} realizado correctamente";
		$pm_post = "¡Hola <strong>{$name}</strong>!";
		$pm_post .= "<br />";
		$pm_post .= "¡Tu pedido de {$benefit} fue enviado correctamente!";
		$pm_post .= "<br />";
		$pm_post .= "<br />";
		$pm_post .= "Te dejaremos el siguiente código para que, en caso de problemas relacionados a tu pago, activación o dudas, puedas hablar con cualquiera de los Directores de la comunidad: <strong>{$code}</strong>.";
		$pm_post .= "<br />";
		$pm_post .= "<br />";
		$pm_post .= "Abajo te dejaremos los pasos donde procederás a realizar el pago. Ten en cuenta que podrás abonar solamente una vez, es decir, no puedes abonar más de dos veces con el enlace, boleta o número de cuenta ya que el sistema solo lo detectará y acreditará una sola vez los recursos pedidos.";
		$pm_post .= "<br />";
		$pm_link = NULL;
		$output = NULL;

		if($region == 0) { // Perú
			$inter_num = "148-3082988700";
			$bcp_num = "193-36248654-0-00";

			$pm_post .= "<ul>";
			$pm_post .= "<li>Número de cuenta de Interbank: <strong>{$inter_num}</strong></li>";
			$pm_post .= "<li>Número de cuenta de BCP: <strong>{$bcp_num}</strong></li>";
			$pm_post .= "</ul>";
			$pm_post .= "Los números de cuenta mencionados arriba están a nombre de <strong>Jairo Ponte R</strong>. Ve al cajero más cercano y depositale el dinero correspondido. Recuerda que el precio de lo que estás comprando es de: S/.{$money_real}.";
			$pm_post .= "<br />";
			$pm_post .= "<br />";
			$pm_post .= "Para que te activemos el pedido, debes proceder el pago anteriormente mencionado; luego de haber hecho el pago, ve al <a href='/ventas/registrar/'>Registro de ventas</a> y registra que has pagado tu pedido. El dato más importante a tener es que tengas el ticket con el que has pagado. Es importante que lo tengas a mano para que podamos corroborar que el pago es verídico.";

			$output = $inter_num."|".$bcp_num;
		} else if($region == 1) { // Argentina
			$data_item['item_id'] = $buy_id;
			$data_item['item_title'] = "Compra de {$benefit} [{$name}] por {$days} días";
			$data_item['item_unit_price'] = $money_real;

			$data_payer['payer_name'] = $name;
			$data_payer['payer_email'] = $email;
			$data_payer['payer_date_created'] = date("F j, Y, g:i a", $time);

			$data_preference['preference_external_reference'] = $code;

			$data_cd = self::generateLinkFromCD($data_item['item_title'], $data_item['item_unit_price'], $data_payer['payer_email'], $data_preference['preference_external_reference']);
			$data_mp = self::generateLinkFromMP($data_item, $data_payer, $data_preference, $benefit_int);

			$cd_link = $data_cd;
			$mp_link = $data_mp;

			$pm_post .= "<ul>";
			$pm_post .= "<li>Boleta para abonar en RapiPago, PagoFácil o medios de pago similares: <a target='_blank' href='{$cd_link}'>Boleta de Cuenta Digital</a></li>";
			$pm_post .= "<li>Link para poder pagar con tarjeta de crédito o débito: <a target='_blank' href='{$mp_link}'>Enlace de Mercado Pago</a></li>";
			$pm_post .= "</ul>";
			$pm_post .= "La activación de tu {$benefit} es <strong>AUTOMÁTICA</strong>. Una vez hayas realizado el pago correctamente, se te enviará un mensaje privado avisando de tu activación. Generalmente, al abonar en sucursales como Rapipago, llega aproximadamente por la madrugada; mientras que por Pagofacil, abonos por tarjetas u otros, son al instante. Reiteramos, una vez que el pago llegue, <strong>será activado y avisado de forma automática</strong>.";

			$output = $cd_link."|".$mp_link;
		} else if($region == 2) { // Paypal
			$pp_link_jairo = 'https://www.paypal.com/paypalme/drunkgaming/'.$money_real;
			$pp_link_atsel = 'https://www.paypal.com/paypalme/atsel97/'.$money_real;

			$pm_post .= "<ul>";
			$pm_post .= "<li>Enlace para pagar a Jairito Mapper: <a target='_blank' href='{$pp_link_jairo}'>{$pp_link_jairo}</a></li>";
			$pm_post .= "<li>Enlace para pagar a Atsel.: <a target='_blank' href='{$pp_link_atsel}'>{$pp_link_atsel}</a></li>";
			$pm_post .= "</ul>";
			$pm_post .= "Recuerda que el precio de lo que estás comprando es de: {$money_real} USD";
			$pm_post .= "<br />";
			$pm_post .= "<br />";
			$pm_post .= "Para que te activemos el pedido, debes proceder el pago anteriormente mencionado; luego de haber hecho el pago, ve al <a href='/ventas/registrar/'>Registro de ventas</a> y registra que has pagado tu pedido. El dato más importante a tener es que tengas el ticket con el que has pagado. Es importante que lo tengas a mano para que podamos corroborar que el pago es verídico.";

			$output = $pp_link_jairo."|".$pp_link_atsel;
		} else if($region == 3) { // Chile
			$be_num = "20314971-9";

			$pm_post .= "<ul>";
			$pm_post .= "<li>Número de cuenta RUT de Bancoestado: <strong>{$be_num}</strong></li>";
			$pm_post .= "</ul>";
			$pm_post .= "Ve al cajero más cercano y depositale el dinero correspondido. Recuerda que el precio de lo que estás comprando es de: {$money_real} CLP.";
			$pm_post .= "<br />";
			$pm_post .= "<br />";
			$pm_post .= "Para que te activemos el pedido, debes proceder el pago anteriormente mencionado; luego de haber hecho el pago, ve al <a href='/ventas/registrar/'>Registro de ventas</a> y registra que has pagado tu pedido. El dato más importante a tener es que tengas el ticket con el que has pagado. Es importante que lo tengas a mano para que podamos corroborar que el pago es verídico.";

			$output = $be_num."|".$be_num;
		}

		if(\stripos($benefit, 'vip') === FALSE) {
			$pm_post .= "<br />";
			$pm_post .= "<br />";
			$pm_post .= "Recuerda que las reglas de administradores se encuentra en la Zona de Admin, ubicada en el foro una vez se te haya activado el mismo. Por favor, lee todo lo necesario acerca de comandos y las reglas impuestas por la comunidad para llevar un buen control en los servidores y manejo de tu {$benefit}.";
		}
		$pm_post .= "<br />";
		$pm_post .= "<br />";
		$pm_post .= "¡Saludos!";
		$pm_post .= "<br />";
		$pm_post .= "¡Staff: Drunk-Gaming!";
		$pm_post .= "<br />";
		$pm_post .= "<br />";
		$pm_post .= "<center><span style='color: #cbcbcb; font-size: 10px;'>Recuerda que este es un mensaje privado automático, si contestas esto, ningún miembro del staff verá el mensaje.</span></center>";
		$pm_post .= "<center><span style='color: #cbcbcb; font-size: 10px;'>Si tienes alguna duda, contáctate con cualquiera de los <a href='/staff/'>Directores</a> de la comunidad.</span></center>";

		self::sendPrivateMessage($member_id, $pm_title, $pm_post, $time);
		\IPS\Output::i()->sendOutput($output);
	}

	protected function finishBuy() {

	}

	protected function approvedBuy() {
		$collection_id = \IPS\Request::i()->collection_id;
		$collection_status = \IPS\Request::i()->collection_status;
		$external_reference = \IPS\Request::i()->external_reference;
		$payment_type = \IPS\Request::i()->payment_type;
		$merchant_order_id = \IPS\Request::i()->merchant_order_id;
		$preference_id = \IPS\Request::i()->preference_id;
		$site_id = \IPS\Request::i()->site_id;
		$processing_mode = \IPS\Request::i()->processing_mode;
		$merchant_account_id = \IPS\Request::i()->merchant_account_id;
		$title = "Compra aprobada - Drunk-Gaming";
		$message = "Tu compra ya ha sido <strong>aprobada</strong>. Que lo disfrutes.";
		$type = 2;

		\IPS\Db::i()->update('ventas_notifications', array(
			'notification_collection_id' => (int) $collection_id,
			'notification_collection_status' => $collection_status,
			'notification_payment_type' => $payment_type,
			'notification_merchant_order_id' => (int) $merchant_order_id,
			'notification_preference_id' => $preference_id,
			'notification_site_id' => $site_id,
			'notification_processing_mode' => $processing_mode,
			'notification_merchant_account_id' => $merchant_account_id
		), array('notification_external_reference=?', $external_reference));

		$notification_buy_id = \IPS\Db::i()->select('notification_buy_id', 'ventas_notifications', array('notification_external_reference=?', $external_reference))->first();

		\IPS\Db::i()->update('ventas_buys', array('buy_status' => 1), array('buy_id=? AND buy_status=?', $notification_buy_id, 0));

		self::sendFinallyBuyMessage($title, $message, $type);
	}

	protected function failureBuy() {
		$collection_id = \IPS\Request::i()->collection_id;
		$collection_status = \IPS\Request::i()->collection_status;
		$external_reference = \IPS\Request::i()->external_reference;
		$payment_type = \IPS\Request::i()->payment_type;
		$merchant_order_id = \IPS\Request::i()->merchant_order_id;
		$preference_id = \IPS\Request::i()->preference_id;
		$site_id = \IPS\Request::i()->site_id;
		$processing_mode = \IPS\Request::i()->processing_mode;
		$merchant_account_id = \IPS\Request::i()->merchant_account_id;
		$title = "Compra rechazada - Drunk-Gaming";
		$message = "Ha ocurrido un error al procesar el pago.";
		$type = 0;

		\IPS\Db::i()->update('ventas_notifications', array(
			'notification_collection_id' => (int) $collection_id,
			'notification_collection_status' => $collection_status,
			'notification_payment_type' => $payment_type,
			'notification_merchant_order_id' => (int) $merchant_order_id,
			'notification_preference_id' => $preference_id,
			'notification_site_id' => $site_id,
			'notification_processing_mode' => $processing_mode,
			'notification_merchant_account_id' => $merchant_account_id
		), array('notification_external_reference=?', $external_reference));

		$notification_buy_id = \IPS\Db::i()->select('notification_buy_id', 'ventas_notifications', array('notification_external_reference=?', $external_reference))->first();

		\IPS\Db::i()->update('ventas_buys', array('buy_status' => -2), array('buy_id=? AND buy_status=?', $notification_buy_id, 0));

		self::sendFinallyBuyMessage($title, $message, $type);
	}

	protected function ze() {
		$member_id = \IPS\Member::loggedIn()->member_id;

		if(!$member_id) {
			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=core&module=system&controller=login', 'front', 'login'));
		}

		$data['ze_check_vinc'] = self::checkVinc(ZOMBIE_ESCAPE, $member_id);

		$form = new \IPS\Helpers\Form('formZEVentas', '__continue');
		$form->class = 'ipsForm_vertical';

		$form->add(new \IPS\Helpers\Form\Select('__account', NULL, TRUE, array('options' => self::getAccounts(ZOMBIE_ESCAPE, $member_id))));

		$form->add(new \IPS\Helpers\Form\YesNo('__ze_pl_y', NULL, TRUE, array('togglesOn' => array('__ze_pl'))));
		$form->add(new \IPS\Helpers\Form\Number('__ze_pl_cant', 1000, TRUE, array(), function($val) {
				if($val < 1000) {
					throw new \DomainException('__not_cant');
				}}, NULL, NULL, '__ze_pl'));

		$form->add(new \IPS\Helpers\Form\Select('__countrys', NULL, TRUE, array('options' => self::getCountrys(ZOMBIE_ESCAPE))));

		if($values = $form->values()) {
			if(!$values['__account'] || !$values['__ze_pl_y']) {
				\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=forums&module=forums&controller=index', 'front', 'forums'));
			}

			$url_ze_post = 'app=ventas&module=main&controller=main&do=zePost';
			$url_ze_post .= '&acc_id='.$values['__account'];
			$url_ze_post .= '&pl='.$values['__ze_pl_cant'];
			$url_ze_post .= '&region='.$values['__countrys'];
			$url_ze_post .= '&code='.self::generateCode(CODE_FOR_ZE);

			\IPS\Output::i()->redirect(\IPS\Http\Url::internal($url_ze_post, 'front', 'ventas_ze_post'));
		}

		$data['ze_form'] = $form;
		$data['percent_extra'] = \IPS\Settings::i()->v_ze_percent_extra;

		\IPS\Output::i()->jsFiles = array_merge(\IPS\Output::i()->jsFiles, \IPS\Output::i()->js('ze.js', 'ventas', 'interface'));
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('ze')->ze($data);
	}

	protected function zePost() {
		$member_id = \IPS\Member::loggedIn()->member_id;
		$name = \IPS\Member::loggedIn()->name;

		if(!$member_id || $name == NULL) {
			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=core&module=system&controller=login', 'front', 'login'));
		}

		$data_forum['member_id'] = $member_id;
		$data_forum['name'] = $name;

		$data['acc_id'] = \IPS\Request::i()->acc_id;
		$data['pl'] = \IPS\Request::i()->pl;
		$data['region'] = \IPS\Request::i()->region;
		$data['code'] = \IPS\Request::i()->code;

		if(!$data['acc_id'] || !$data['pl'] || \strlen($data['code']) < 16) {
			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=forums&module=forums&controller=index', 'front', 'forums'));
		}

		try {
			$row = $this->dbze->select('*', 'ze3_accounts', array('id=?', $data['acc_id']))->first();

			$data['name'] = $row['name'];
			$data['member_id'] = $row['vinc'];
			$data['percent_extra'] = \IPS\Settings::i()->v_ze_percent_extra;

			if(\IPS\Settings::i()->v_ze_pl_reward_base) {
				if($data['pl'] >= \IPS\Settings::i()->v_ze_pl_reward_base) {
					$data['pl_extra'] = (int) (($data['pl'] / \IPS\Settings::i()->v_ze_pl_reward_base) * \IPS\Settings::i()->v_ze_pl_reward);
				} else {
					$data['pl_extra'] = 0;
				}
			} else {
				$data['pl_extra'] = 0;
			}
			
			\IPS\Output::i()->jsFiles = array_merge(\IPS\Output::i()->jsFiles, \IPS\Output::i()->js('ze.js', 'ventas', 'interface'));
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('ze')->zePost($data_forum, $data);
		} catch(\UnderflowException $e) {
			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=forums&module=forums&controller=index', 'front', 'forums'));
		}
	}

	protected function zeSendData() {
		$member_id = \IPS\Member::loggedIn()->member_id;
		$name = \IPS\Member::loggedIn()->name;
		$email = \IPS\Member::loggedIn()->email;

		if(!$member_id || $name == NULL || $email == NULL) {
			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=core&module=system&controller=login', 'front', 'login'));
		}

		$ze_id = \IPS\Request::i()->ze_id;
		$ze_pl = \IPS\Request::i()->ze_pl;
		$ze_region = \IPS\Request::i()->ze_region;
		$ze_code = \IPS\Request::i()->ze_code;
		$ze_total = \IPS\Request::i()->ze_total;
		$time = time();
		$benefit = "Recursos ZE";
		$benefit_int = CODE_FOR_ZE;

		if(!$ze_id || !$ze_pl || \strlen($ze_code) < 16 || $ze_total < 0) {
			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=forums&module=forums&controller=index', 'front', 'forums'));
		}

		$buy_id = $this->dbze->insert('ze3_buys', array(
			'acc_id' => $ze_id,
			'p_legacy' => $ze_pl,
			'bought_money' => ($ze_region + 1),
			'bought_money_real' => $ze_total,
			'bought_timestamp' => $time,
			'bought_ok' => -1
		));

		$payment_id = $this->dbze->insert('ze3_payments', array(
			'buy_id' => $buy_id,
			'member_id' => $member_id,
			'code' => $ze_code,
			'timestamp' => $time,
			'ok' => -1
		));

		$pm_title = "Pedido de {$benefit} realizado correctamente";
		$pm_post = "¡Hola <strong>{$name}</strong>!";
		$pm_post .= "<br />";
		$pm_post .= "¡Tu pedido de {$benefit} fue enviado correctamente!";
		$pm_post .= "<br />";
		$pm_post .= "<br />";
		$pm_post .= "Te dejaremos el siguiente código para que, en caso de problemas relacionados a tu pago, activación o dudas, puedas hablar con cualquiera de los Directores de la comunidad: <strong>{$ze_code}</strong>.";
		$pm_post .= "<br />";
		$pm_post .= "<br />";
		$pm_post .= "Abajo te dejaremos los pasos donde procederás a realizar el pago. Ten en cuenta que podrás abonar solamente una vez, es decir, no puedes abonar más de dos veces con el enlace, boleta o número de cuenta ya que el sistema solo lo detectará y acreditará una sola vez los recursos pedidos.";
		$pm_post .= "<br />";
		$pm_link = NULL;
		$output = NULL;

		if($ze_region == 0) { // Perú
			$inter_num = "148-3082988700";
			$bcp_num = "193-36248654-0-00";

			$pm_post .= "<ul>";
			$pm_post .= "<li>Número de cuenta de Interbank: <strong>{$inter_num}</strong></li>";
			$pm_post .= "<li>Número de cuenta de BCP: <strong>{$bcp_num}</strong></li>";
			$pm_post .= "</ul>";
			$pm_post .= "Los números de cuenta mencionados arriba están a nombre de <strong>Jairo Ponte R</strong>. Ve al cajero más cercano y depositale el dinero correspondido. Recuerda que la cantidad de recursos que estás comprando es de {$ze_pl} pL (por <strong>S/.{$ze_total}</strong>).";
			$pm_post .= "<br />";
			$pm_post .= "<br />";
			$pm_post .= "Para que te activemos los recursos que has pedido. Debes proceder el pago anteriormente mencionado; luego de haber hecho el pago, ve al <a href='/ventas/registrar-ze/'>Registro de ventas del ZE</a> y registra que has pagado tu pedido. El dato más importante a tener es que tengas el ticket con el que has pagado. Es importante que lo tengas a mano para que podamos corroborar que el pago es verídico.";

			$output = $inter_num.'|'.$bcp_num;
		} else if($ze_region == 1) { // Argentina
			$data_item['item_id'] = $buy_id;
			$data_item['item_title'] = "Compra de {$benefit} [{$name}]";
			$data_item['item_unit_price'] = $ze_total;

			$data_payer['payer_name'] = $name;
			$data_payer['payer_email'] = $email;
			$data_payer['payer_date_created'] = date("F j, Y, g:i a", $time);

			$data_preference['preference_external_reference'] = $ze_code;

			$data_cd = self::generateLinkFromCD($data_item['item_title'], $data_item['item_unit_price'], $data_payer['payer_email'], $data_preference['preference_external_reference']);
			$data_mp = self::generateLinkFromMP($data_item, $data_payer, $data_preference, $benefit_int);

			$cd_link = $data_cd;
			$mp_link = $data_mp;

			$pm_post .= "<ul>";
			$pm_post .= "<li>Boleta para abonar en RapiPago, PagoFácil o medios de pago similares: <a target='_blank' href='{$cd_link}'>Boleta de Cuenta Digital</a></li>";
			$pm_post .= "<li>Link para poder pagar con tarjeta de crédito o débito: <a target='_blank' href='{$mp_link}'>Enlace de Mercado Pago</a></li>";
			$pm_post .= "</ul>";
			$pm_post .= "La activación de los {$benefit} es <strong>AUTOMÁTICA</strong>. Una vez hayas realizado el pago correctamente, se te enviará un mensaje privado avisando de tu activación. Generalmente, al abonar en sucursales como Rapipago, llega aproximadamente por la madrugada; mientras que por Pagofacil, abonos por tarjetas u otros, son al instante. Reiteramos, una vez que el pago llegue, <strong>será activado y avisado de forma automática</strong>.";

			$output = $cd_link."|".$mp_link;
		} else if($ze_region == 2) { // Paypal
			$pp_link_jairo = 'https://www.paypal.com/paypalme/drunkgaming/'.$ze_total;
			$pp_link_atsel = 'https://www.paypal.com/paypalme/atsel97/'.$ze_total;

			$pm_post .= "<ul>";
			$pm_post .= "<li>Enlace para pagar a Jairito Mapper: <a target='_blank' href='{$pp_link_jairo}'>{$pp_link_jairo}</a></li>";
			$pm_post .= "<li>Enlace para pagar a Atsel.: <a target='_blank' href='{$pp_link_atsel}'>{$pp_link_atsel}</a></li>";
			$pm_post .= "</ul>";
			$pm_post .= "Recuerda que la cantidad de recursos que estás comprando es de {$ze_pl} pL (por <strong>{$ze_total} USD</strong>).";
			$pm_post .= "<br />";
			$pm_post .= "<br />";
			$pm_post .= "Para que te activemos los recursos que has pedido. Debes proceder el pago anteriormente mencionado; luego de haber hecho el pago, ve al <a href='/ventas/registrar-ze/'>Registro de ventas del ZE</a> y registra que has pagado tu pedido. El dato más importante a tener es que tengas el ticket con el que has pagado. Es importante que lo tengas a mano para que podamos corroborar que el pago es verídico.";

			$output = $pp_link_jairo."|".$pp_link_atsel;
		} else if($ze_region == 3) { // Chile
			$be_num = "20314971-9";

			$pm_post .= "<ul>";
			$pm_post .= "<li>Número de cuenta RUT de Bancoestado: <strong>{$be_num}</strong></li>";
			$pm_post .= "</ul>";
			$pm_post .= "Ve al cajero más cercano y depositale el dinero correspondido. Recuerda que la cantidad de recursos que estás comprando es de ".$ze_pl." pL (por <strong>{$ze_total} CLP</strong>).";
			$pm_post .= "<br />";
			$pm_post .= "<br />";
			$pm_post .= "Para que te activemos los recursos que has pedido. Debes proceder el pago anteriormente mencionado; luego de haber hecho el pago, ve al <a href='/ventas/registrar-ze/'>Registro de ventas del ZE</a> y registra que has pagado tu pedido. El dato más importante a tener es que tengas el ticket con el que has pagado. Es importante que lo tengas a mano para que podamos corroborar que el pago es verídico.";

			$output = $be_num."|".$be_num;
		}

		$pm_post .= "<br />";
		$pm_post .= "<br />";
		$pm_post .= "¡Saludos!";
		$pm_post .= "<br />";
		$pm_post .= "¡Staff: Drunk-Gaming!";
		$pm_post .= "<br />";
		$pm_post .= "<br />";
		$pm_post .= "<center><span style='color: #cbcbcb; font-size: 10px;'>Recuerda que este es un mensaje privado automático, si contestas esto, ningún miembro del staff verá el mensaje.</span></center>";
		$pm_post .= "<center><span style='color: #cbcbcb; font-size: 10px;'>Si tienes alguna duda, contáctate con cualquiera de los <a href='/staff/'>Directores</a> de la comunidad.</span></center>";

		self::sendPrivateMessage($member_id, $pm_title, $pm_post, $time);
		\IPS\Output::i()->sendOutput($output);
	}

	protected function zeFinishBuy() {
		
	}

	protected function zeApprovedBuy() {
		$collection_id = \IPS\Request::i()->collection_id;
		$collection_status = \IPS\Request::i()->collection_status;
		$external_reference = \IPS\Request::i()->external_reference;
		$payment_type = \IPS\Request::i()->payment_type;
		$merchant_order_id = \IPS\Request::i()->merchant_order_id;
		$preference_id = \IPS\Request::i()->preference_id;
		$site_id = \IPS\Request::i()->site_id;
		$processing_mode = \IPS\Request::i()->processing_mode;
		$merchant_account_id = \IPS\Request::i()->merchant_account_id;
		$title = "Compra aprobada - Drunk-Gaming";
		$message = "Tu compra ya ha sido <strong>aprobada</strong>. Que lo disfrutes.";
		$type = 2;

		$this->dbze->update('ze3_payments', array(
			'collection_id' => (int) $collection_id,
			'collection_status' => $collection_status,
			'payment_type' => $payment_type,
			'merchant_order_id' => (int) $merchant_order_id,
			'preference_id' => $preference_id,
			'site_id' => $site_id,
			'processing_mode' => $processing_mode,
			'merchant_account_id' => $merchant_account_id,
			'ok' => 0
		), array('code=?', $external_reference));

		$buy_id = $this->dbze->select('buy_id', 'ze3_payments', array('code=?', $external_reference))->first();
		$this->dbze->update('ze3_buys', array('bought_ok' => 0), array('id=? AND bought_ok=?', $buy_id, -1));

		$acc_id = $this->dbze->select('acc_id', 'ze3_buys', array('id=?', $buy_id))->first();
		$this->dbze->update('ze3_pjs', array('bought_ok' => 1), array('acc_id=? AND bought_ok=?', $acc_id, 0));

		self::sendFinallyBuyMessage($title, $message, $type);
	}

	protected function zeFailureBuy() {
		$collection_id = \IPS\Request::i()->collection_id;
		$collection_status = \IPS\Request::i()->collection_status;
		$external_reference = \IPS\Request::i()->external_reference;
		$payment_type = \IPS\Request::i()->payment_type;
		$merchant_order_id = \IPS\Request::i()->merchant_order_id;
		$preference_id = \IPS\Request::i()->preference_id;
		$site_id = \IPS\Request::i()->site_id;
		$processing_mode = \IPS\Request::i()->processing_mode;
		$merchant_account_id = \IPS\Request::i()->merchant_account_id;
		$title = "Compra rechazada - Drunk-Gaming";
		$message = "Ha ocurrido un error al procesar el pago.";
		$type = 0;

		$this->dbze->update('ze3_payments', array(
			'collection_id' => (int) $collection_id,
			'collection_status' => $collection_status,
			'payment_type' => $payment_type,
			'merchant_order_id' => (int) $merchant_order_id,
			'preference_id' => $preference_id,
			'site_id' => $site_id,
			'processing_mode' => $processing_mode,
			'merchant_account_id' => $merchant_account_id,
			'ok' => -3
		), array('code=?', $external_reference));

		$buy_id = $this->dbze->select('buy_id', 'ze3_payments', array('code=?', $external_reference))->first();
		$this->dbze->update('ze3_buys', array('bought_ok' => -3), array('id=? AND bought_ok=?', $buy_id, -1));

		self::sendFinallyBuyMessage($title, $message, $type);
	}

	protected function zpu() {
		$member_id = \IPS\Member::loggedIn()->member_id;

		if(!$member_id) {
			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=core&module=system&controller=login', 'front', 'login'));
		}

		$data['zpu_check_vinc'] = self::checkVinc(ZOMBIE_PLAGUE_UMBRELLA, $member_id);

		$form = new \IPS\Helpers\Form('formZPUVentas', '__continue');
		$form->class = 'ipsForm_vertical';

		$form->add(new \IPS\Helpers\Form\Select('__account', NULL, TRUE, array('options' => self::getAccounts(ZOMBIE_PLAGUE_UMBRELLA, $member_id))));

		$form->add(new \IPS\Helpers\Form\YesNo('__zpu_ps_y', NULL, TRUE, array('togglesOn' => array('__zpu_ps'))));
		$form->add(new \IPS\Helpers\Form\Number('__zpu_ps_cant', 2000, TRUE, array(), function($val) {
				if($val < 2000) {
					throw new \DomainException('__not_cant');
				}}, NULL, NULL, '__zpu_ps'));

		$form->add(new \IPS\Helpers\Form\Select('__countrys', NULL, TRUE, array('options' => self::getCountrys(ZOMBIE_PLAGUE_UMBRELLA))));

		if($values = $form->values()) {
			if(!$values['__account'] || !$values['__zpu_ps_y']) {
				\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=forums&module=forums&controller=index', 'front', 'forums'));
			}

			$url_zpu_post = 'app=ventas&module=main&controller=main&do=zpuPost';
			$url_zpu_post .= '&acc_id='.$values['__account'];
			$url_zpu_post .= '&ps='.$values['__zpu_ps_cant'];
			$url_zpu_post .= '&region='.$values['__countrys'];
			$url_zpu_post .= '&code='.self::generateCode(CODE_FOR_ZPU);

			\IPS\Output::i()->redirect(\IPS\Http\Url::internal($url_zpu_post, 'front', 'ventas_zpu_post'));
		}

		$data['zpu_form'] = $form;
		$data['percent_extra'] = \IPS\Settings::i()->v_zpu_percent_extra;

		\IPS\Output::i()->jsFiles = array_merge(\IPS\Output::i()->jsFiles, \IPS\Output::i()->js('zpu.js', 'ventas', 'interface'));
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('zpu')->zpu($data);
	}

	protected function zpuPost() {
		$member_id = \IPS\Member::loggedIn()->member_id;
		$name = \IPS\Member::loggedIn()->name;
		$email = \IPS\Member::loggedIn()->email;

		if(!$member_id || $name == NULL || $email == NULL) {
			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=core&module=system&controller=login', 'front', 'login'));
		}

		$data_forum['member_id'] = $member_id;
		$data_forum['name'] = $name;

		$data['acc_id'] = \IPS\Request::i()->acc_id;
		$data['ps'] = \IPS\Request::i()->ps;
		$data['region'] = \IPS\Request::i()->region;
		$data['code'] = \IPS\Request::i()->code;

		if(!$data['acc_id'] || !$data['ps'] || \strlen($data['code']) < 16) {
			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=forums&module=forums&controller=index', 'front', 'forums'));
		}

		try {
			$row = $this->dbzpu->select('*', 'pdata', array('ID=?', $data['acc_id']))->first();

			$data['name'] = $row['Name'];
			$data['member_id'] = $row['Forum_ID'];
			$data['percent_extra'] = \IPS\Settings::i()->v_zpu_percent_extra;

			if(\IPS\Settings::i()->v_zpu_ps_reward_base) {
				if($data['ps'] >= \IPS\Settings::i()->v_zpu_ps_reward_base) {
					$data['ps_extra'] = (int) (($data['ps'] / \IPS\Settings::i()->v_zpu_ps_reward_base) * \IPS\Settings::i()->v_zpu_ps_reward);
				} else {
					$data['ps_extra'] = 0;
				}
			} else {
				$data['ps_extra'] = 0;
			}
			
			\IPS\Output::i()->jsFiles = array_merge(\IPS\Output::i()->jsFiles, \IPS\Output::i()->js('zpu.js', 'ventas', 'interface'));
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('zpu')->zpuPost($data_forum, $data);
		} catch(\UnderflowException $e) {
			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=forums&module=forums&controller=index', 'front', 'forums'));
		}
	}

	protected function zpuSendData() {
		$member_id = \IPS\Member::loggedIn()->member_id;
		$name = \IPS\Member::loggedIn()->name;
		$email = \IPS\Member::loggedIn()->email;

		if(!$member_id || $name == NULL || $email == NULL) {
			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=core&module=system&controller=login', 'front', 'login'));
		}

		$zpu_id = \IPS\Request::i()->zpu_id;
		$zpu_ps = \IPS\Request::i()->zpu_ps;
		$zpu_region = \IPS\Request::i()->zpu_region;
		$zpu_code = \IPS\Request::i()->zpu_code;
		$zpu_total = \IPS\Request::i()->zpu_total;
		$time = time();
		$benefit = "Recursos ZPU";
		$benefit_int = CODE_FOR_ZPU;

		if(!$zpu_id || !$zpu_ps || \strlen($zpu_code) < 16 || $zpu_total < 0) {
			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=forums&module=forums&controller=index', 'front', 'forums'));
		}

		$buy_id = $this->dbzpu->insert('forum_saldo', array(
			'Player ID' => $zpu_id,
			'Saldo' => $zpu_ps,
			'Money' => ($zpu_region + 1),
			'MoneyReal' => $zpu_total,
			'Date' => $time,
			'Deprecated' => -1
		));

		$payment_id = $this->dbzpu->insert('forum_saldo_payments', array(
			'buy_id' => $buy_id,
			'member_id' => $member_id,
			'code' => $zpu_code,
			'timestamp' => $time,
			'ok' => -1
		));

		$pm_title = "Pedido de {$benefit} realizado correctamente";
		$pm_post = "¡Hola <strong>{$name}</strong>!";
		$pm_post .= "<br />";
		$pm_post .= "¡Tu pedido de {$benefit} fue enviado correctamente!";
		$pm_post .= "<br />";
		$pm_post .= "<br />";
		$pm_post .= "Te dejaremos el siguiente código para que, en caso de problemas relacionados a tu pago, activación o dudas, puedas hablar con cualquiera de los Directores de la comunidad: <strong>{$zpu_code}</strong>.";
		$pm_post .= "<br />";
		$pm_post .= "<br />";
		$pm_post .= "Abajo te dejaremos los pasos donde procederás a realizar el pago. Ten en cuenta que podrás abonar solamente una vez, es decir, no puedes abonar más de dos veces con el enlace, boleta o número de cuenta ya que el sistema solo lo detectará y acreditará una sola vez los recursos pedidos.";
		$pm_post .= "<br />";
		$pm_link = NULL;
		$output = NULL;

		if($zpu_region == 0) { // Perú
			$inter_num = "148-3082988700";
			$bcp_num = "193-36248654-0-00";

			$pm_post .= "<ul>";
			$pm_post .= "<li>Número de cuenta de Interbank: <strong>{$inter_num}</strong></li>";
			$pm_post .= "<li>Número de cuenta de BCP: <strong>{$bcp_num}</strong></li>";
			$pm_post .= "</ul>";
			$pm_post .= "Los números de cuenta mencionados arriba están a nombre de <strong>Jairo Ponte R</strong>. Ve al cajero más cercano y depositale el dinero correspondido. Recuerda que la cantidad de recursos que estás comprando es de {$zpu_ps} pL (por <strong>S/.{$zpu_total}</strong>).";
			$pm_post .= "<br />";
			$pm_post .= "<br />";
			$pm_post .= "Para que te activemos los recursos que has pedido. Debes proceder el pago anteriormente mencionado; luego de haber hecho el pago, ve al <a href='/ventas/registrar-zpu/'>Registro de ventas del ZPU</a> y registra que has pagado tu pedido. El dato más importante a tener es que tengas el ticket con el que has pagado. Es importante que lo tengas a mano para que podamos corroborar que el pago es verídico.";

			$output = $inter_num.'|'.$bcp_num;
		} else if($zpu_region == 1) { // Argentina
			$data_item['item_id'] = $buy_id;
			$data_item['item_title'] = "Compra de {$benefit} [{$name}]";
			$data_item['item_unit_price'] = $zpu_total;

			$data_payer['payer_name'] = $name;
			$data_payer['payer_email'] = $email;
			$data_payer['payer_date_created'] = date("F j, Y, g:i a", $time);

			$data_preference['preference_external_reference'] = $zpu_code;

			$data_cd = self::generateLinkFromCD($data_item['item_title'], $data_item['item_unit_price'], $data_payer['payer_email'], $data_preference['preference_external_reference']);
			$data_mp = self::generateLinkFromMP($data_item, $data_payer, $data_preference, $benefit_int);

			$cd_link = $data_cd;
			$mp_link = $data_mp;

			$pm_post .= "<ul>";
			$pm_post .= "<li>Boleta para abonar en RapiPago, PagoFácil o medios de pago similares: <a target='_blank' href='{$cd_link}'>Boleta de Cuenta Digital</a></li>";
			$pm_post .= "<li>Link para poder pagar con tarjeta de crédito o débito: <a target='_blank' href='{$mp_link}'>Enlace de Mercado Pago</a></li>";
			$pm_post .= "</ul>";
			$pm_post .= "La activación de los {$benefit} es <strong>AUTOMÁTICA</strong>. Una vez hayas realizado el pago correctamente, se te enviará un mensaje privado avisando de tu activación. Generalmente, al abonar en sucursales como Rapipago, llega aproximadamente por la madrugada; mientras que por Pagofacil, abonos por tarjetas u otros, son al instante. Reiteramos, una vez que el pago llegue, <strong>será activado y avisado de forma automática</strong>.";

			$output = $cd_link."|".$mp_link;
		} else if($zpu_region == 2) { // Paypal
			$pp_link_jairo = 'https://www.paypal.com/paypalme/drunkgaming/'.$zpu_total;
			$pp_link_atsel = 'https://www.paypal.com/paypalme/atsel97/'.$zpu_total;

			$pm_post .= "<ul>";
			$pm_post .= "<li>Enlace para pagar a Jairito Mapper: <a target='_blank' href='{$pp_link_jairo}'>{$pp_link_jairo}</a></li>";
			$pm_post .= "<li>Enlace para pagar a Atsel.: <a target='_blank' href='{$pp_link_atsel}'>{$pp_link_atsel}</a></li>";
			$pm_post .= "</ul>";
			$pm_post .= "Recuerda que la cantidad de recursos que estás comprando es de {$zpu_ps} pL (por <strong>{$zpu_total} USD</strong>).";
			$pm_post .= "<br />";
			$pm_post .= "<br />";
			$pm_post .= "Para que te activemos los recursos que has pedido. Debes proceder el pago anteriormente mencionado; luego de haber hecho el pago, ve al <a href='/ventas/registrar-zpu/'>Registro de ventas del ZPU</a> y registra que has pagado tu pedido. El dato más importante a tener es que tengas el ticket con el que has pagado. Es importante que lo tengas a mano para que podamos corroborar que el pago es verídico.";

			$output = $pp_link_jairo."|".$pp_link_atsel;
		} else if($zpu_region == 3) { // Chile
			$be_num = "20314971-9";

			$pm_post .= "<ul>";
			$pm_post .= "<li>Número de cuenta RUT de Bancoestado: <strong>{$be_num}</strong></li>";
			$pm_post .= "</ul>";
			$pm_post .= "Ve al cajero más cercano y depositale el dinero correspondido. Recuerda que la cantidad de recursos que estás comprando es de ".$zpu_ps." SALDO (por <strong>{$zpu_total} CLP</strong>).";
			$pm_post .= "<br />";
			$pm_post .= "<br />";
			$pm_post .= "Para que te activemos los recursos que has pedido. Debes proceder el pago anteriormente mencionado; luego de haber hecho el pago, ve al <a href='/ventas/registrar-zpu/'>Registro de ventas del ZPU</a> y registra que has pagado tu pedido. El dato más importante a tener es que tengas el ticket con el que has pagado. Es importante que lo tengas a mano para que podamos corroborar que el pago es verídico.";

			$output = $be_num."|".$be_num;
		}

		$pm_post .= "<br />";
		$pm_post .= "<br />";
		$pm_post .= "¡Saludos!";
		$pm_post .= "<br />";
		$pm_post .= "¡Staff: Drunk-Gaming!";
		$pm_post .= "<br />";
		$pm_post .= "<br />";
		$pm_post .= "<center><span style='color: #cbcbcb; font-size: 10px;'>Recuerda que este es un mensaje privado automático, si contestas esto, ningún miembro del staff verá el mensaje.</span></center>";
		$pm_post .= "<center><span style='color: #cbcbcb; font-size: 10px;'>Si tienes alguna duda, contáctate con cualquiera de los <a href='/staff/'>Directores</a> de la comunidad.</span></center>";

		self::sendPrivateMessage($member_id, $pm_title, $pm_post, $time);
		\IPS\Output::i()->sendOutput($output);
	}

	protected function zpuFinishBuy() {
		
	}

	protected function zpuApprovedBuy() {
		$collection_id = \IPS\Request::i()->collection_id;
		$collection_status = \IPS\Request::i()->collection_status;
		$external_reference = \IPS\Request::i()->external_reference;
		$payment_type = \IPS\Request::i()->payment_type;
		$merchant_order_id = \IPS\Request::i()->merchant_order_id;
		$preference_id = \IPS\Request::i()->preference_id;
		$site_id = \IPS\Request::i()->site_id;
		$processing_mode = \IPS\Request::i()->processing_mode;
		$merchant_account_id = \IPS\Request::i()->merchant_account_id;
		$title = "Compra aprobada - Drunk-Gaming";
		$message = "Tu compra ya ha sido <strong>aprobada</strong>. Que lo disfrutes.";
		$type = 2;

		$this->dbzpu->update('forum_saldo_payments', array(
			'collection_id' => (int) $collection_id,
			'collection_status' => $collection_status,
			'payment_type' => $payment_type,
			'merchant_order_id' => (int) $merchant_order_id,
			'preference_id' => $preference_id,
			'site_id' => $site_id,
			'processing_mode' => $processing_mode,
			'merchant_account_id' => $merchant_account_id,
			'ok' => 0
		), array('code=?', $external_reference));

		$buy_id = $this->dbzpu->select('buy_id', 'forum_saldo_payments', array('code=?', $external_reference))->first();
		$this->dbzpu->update('forum_saldo', array('Deprecated' => 0), array('ID=? AND Deprecated=?', $buy_id, -1));
		
		self::sendFinallyBuyMessage($title, $message, $type);
	}

	protected function zpuFailureBuy() {
		$collection_id = \IPS\Request::i()->collection_id;
		$collection_status = \IPS\Request::i()->collection_status;
		$external_reference = \IPS\Request::i()->external_reference;
		$payment_type = \IPS\Request::i()->payment_type;
		$merchant_order_id = \IPS\Request::i()->merchant_order_id;
		$preference_id = \IPS\Request::i()->preference_id;
		$site_id = \IPS\Request::i()->site_id;
		$processing_mode = \IPS\Request::i()->processing_mode;
		$merchant_account_id = \IPS\Request::i()->merchant_account_id;
		$title = "Compra rechazada - Drunk-Gaming";
		$message = "Ha ocurrido un error al procesar el pago.";
		$type = 0;

		$this->dbzpu->update('forum_saldo_payments', array(
			'collection_id' => (int) $collection_id,
			'collection_status' => $collection_status,
			'payment_type' => $payment_type,
			'merchant_order_id' => (int) $merchant_order_id,
			'preference_id' => $preference_id,
			'site_id' => $site_id,
			'processing_mode' => $processing_mode,
			'merchant_account_id' => $merchant_account_id,
			'ok' => -3
		), array('code=?', $external_reference));

		$buy_id = $this->dbzpu->select('buy_id', 'forum_saldo_payments', array('code=?', $external_reference))->first();
		$this->dbzpu->update('forum_saldo', array('Deprecated' => -3), array('ID=? AND Deprecated=?', $buy_id, -1));

		self::sendFinallyBuyMessage($title, $message, $type);
	}

	protected function zpa() {
		$member_id = \IPS\Member::loggedIn()->member_id;

		if(!$member_id) {
			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=core&module=system&controller=login', 'front', 'login'));
		}

		$data['zpa_check_vinc'] = self::checkVinc(ZOMBIE_PLAGUE_ANNIHILATION, $member_id);

		$form = new \IPS\Helpers\Form('formZPAVentas', '__continue');
		$form->class = 'ipsForm_vertical';

		$form->add(new \IPS\Helpers\Form\Select('__account', NULL, TRUE, array('options' => self::getAccounts(ZOMBIE_PLAGUE_ANNIHILATION, $member_id))));

		$form->add(new \IPS\Helpers\Form\YesNo('__zpa_ph_y', NULL, TRUE, array('togglesOn' => array('__zpa_ph'))));
		$form->add(new \IPS\Helpers\Form\Number('__zpa_ph_cant', 25, TRUE, array(), function($val) {
				if($val < 25) {
					throw new \DomainException('__not_cant');
				}}, NULL, NULL, '__zpa_ph'));

		$form->add(new \IPS\Helpers\Form\YesNo('__zpa_pz_y', NULL, TRUE, array('togglesOn' => array('__zpa_pz'))));
		$form->add(new \IPS\Helpers\Form\Number('__zpa_pz_cant', 25, TRUE, array(), function($val) {
				if($val < 25) {
					throw new \DomainException('__not_cant');
				}}, NULL, NULL, '__zpa_pz'));

		$form->add(new \IPS\Helpers\Form\YesNo('__zpa_pl_y', NULL, TRUE, array('togglesOn' => array('__zpa_pl'))));
		$form->add(new \IPS\Helpers\Form\Number('__zpa_pl_cant', 50, TRUE, array(), function($val) {
				if($val < 50) {
					throw new \DomainException('__not_cant');
				}}, NULL, NULL, '__zpa_pl'));

		$form->add(new \IPS\Helpers\Form\YesNo('__zpa_ps_y', NULL, TRUE, array('togglesOn' => array('__zpa_ps'))));
		$form->add(new \IPS\Helpers\Form\Number('__zpa_ps_cant', 100, TRUE, array(), function($val) {
				if($val < 100) {
					throw new \DomainException('__not_cant');
				}}, NULL, NULL, '__zpa_ps'));

		$form->add(new \IPS\Helpers\Form\YesNo('__zpa_pd_y', NULL, TRUE, array('togglesOn' => array('__zpa_pd'))));
		$form->add(new \IPS\Helpers\Form\Number('__zpa_pd_cant', 5, TRUE, array(), function($val) {
				if($val < 5) {
					throw new \DomainException('__not_cant');
				}}, NULL, NULL, '__zpa_pd'));

		$form->add(new \IPS\Helpers\Form\Select('__countrys', NULL, TRUE, array('options' => self::getCountrys(ZOMBIE_PLAGUE_ANNIHILATION))));

		if($values = $form->values()) {
			if(!$values['__account'] ||
			(!$values['__zpa_ph_y'] && !$values['__zpa_pz_y'] && !$values['__zpa_pl_y'] && !$values['__zpa_ps_y'] && !$values['__zpa_pd_y'])) {
				\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=forums&module=forums&controller=index', 'front', 'forums'));
			}

			$url_zpa_post = 'app=ventas&module=main&controller=main&do=zpaPost';
			$url_zpa_post .= '&acc_id='.$values['__account'];

			if($values['__zpa_ph_y']) {
				$url_zpa_post .= '&ph='.$values['__zpa_ph_cant'];
			}

			if($values['__zpa_pz_y']) {
				$url_zpa_post .= '&pz='.$values['__zpa_pz_cant'];
			}

			if($values['__zpa_pl_y']) {
				$url_zpa_post .= '&pl='.$values['__zpa_pl_cant'];
			}

			if($values['__zpa_ps_y']) {
				$url_zpa_post .= '&ps='.$values['__zpa_ps_cant'];
			}

			if($values['__zpa_pd_y']) {
				$url_zpa_post .= '&pd='.$values['__zpa_pd_cant'];
			}

			$url_zpa_post .= '&region='.$values['__countrys'];
			$url_zpa_post .= '&code='.self::generateCode(CODE_FOR_ZPA);

			\IPS\Output::i()->redirect(\IPS\Http\Url::internal($url_zpa_post, 'front', 'ventas_zpa_post'));
		}

		$data['zpa_form'] = $form;
		$data['percent_extra'] = \IPS\Settings::i()->v_zpa_percent_extra;

		\IPS\Output::i()->jsFiles = array_merge(\IPS\Output::i()->jsFiles, \IPS\Output::i()->js('zpa.js', 'ventas', 'interface'));
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('zpa')->zpa($data);
	}

	protected function zpaPost() {
		$member_id = \IPS\Member::loggedIn()->member_id;
		$name = \IPS\Member::loggedIn()->name;
		$email = \IPS\Member::loggedIn()->email;

		if(!$member_id || $name == NULL || $email == NULL) {
			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=core&module=system&controller=login', 'front', 'login'));
		}

		$data_forum['member_id'] = $member_id;
		$data_forum['name'] = $name;

		$data['acc_id'] = \IPS\Request::i()->acc_id;
		$data['ph'] = \IPS\Request::i()->ph;
		$data['pz'] = \IPS\Request::i()->pz;
		$data['pl'] = \IPS\Request::i()->pl;
		$data['ps'] = \IPS\Request::i()->ps;
		$data['pd'] = \IPS\Request::i()->pd;
		$data['region'] = \IPS\Request::i()->region;
		$data['code'] = \IPS\Request::i()->code;

		if(!$data['acc_id'] ||
		(!$data['ph'] && !$data['pz'] && !$data['pl'] && !$data['ps'] && !$data['pd']) ||
		\strlen($data['code']) < 16) {
			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=forums&module=forums&controller=index', 'front', 'forums'));
		}

		try {
			$row = $this->dbzpa->select('*', 'zp8_accounts', array('id=?', $data['acc_id']))->first();

			$data['name'] = $row['name'];
			$data['member_id'] = $row['vinc'];
			$data['percent_extra'] = \IPS\Settings::i()->v_zpa_percent_extra;

			if(\IPS\Settings::i()->v_zpa_ph_reward_base) {
				if($data['ph'] >= \IPS\Settings::i()->v_zpa_ph_reward_base) {
					$data['ph_extra'] = (int) (($data['ph'] / \IPS\Settings::i()->v_zpa_ph_reward_base) * \IPS\Settings::i()->v_zpa_ph_reward);
				} else {
					$data['ph_extra'] = 0;
				}
			} else {
				$data['ph_extra'] = 0;
			}

			if(\IPS\Settings::i()->v_zpa_pz_reward_base) {
				if($data['pz'] >= \IPS\Settings::i()->v_zpa_pz_reward_base) {
					$data['pz_extra'] = (int) (($data['pz'] / \IPS\Settings::i()->v_zpa_pz_reward_base) * \IPS\Settings::i()->v_zpa_pz_reward);
				} else {
					$data['pz_extra'] = 0;
				}
			} else {
				$data['pz_extra'] = 0;
			}

			if(\IPS\Settings::i()->v_zpa_pl_reward_base) {
				if($data['pl'] >= \IPS\Settings::i()->v_zpa_pl_reward_base) {
					$data['pl_extra'] = (int) (($data['pl'] / \IPS\Settings::i()->v_zpa_pl_reward_base) * \IPS\Settings::i()->v_zpa_pl_reward);
				} else {
					$data['pl_extra'] = 0;
				}
			} else {
				$data['pl_extra'] = 0;
			}

			if(\IPS\Settings::i()->v_zpa_ps_reward_base) {
				if($data['ps'] >= \IPS\Settings::i()->v_zpa_ps_reward_base) {
					$data['ps_extra'] = (int) (($data['ps'] / \IPS\Settings::i()->v_zpa_ps_reward_base) * \IPS\Settings::i()->v_zpa_ps_reward);
				} else {
					$data['ps_extra'] = 0;
				}
			} else {
				$data['ps_extra'] = 0;
			}

			if(\IPS\Settings::i()->v_zpa_pd_reward_base) {
				if($data['pd'] >= \IPS\Settings::i()->v_zpa_pd_reward_base) {
					$data['pd_extra'] = (int) (($data['pd'] / \IPS\Settings::i()->v_zpa_pd_reward_base) * \IPS\Settings::i()->v_zpa_pd_reward);
				} else {
					$data['pd_extra'] = 0;
				}
			} else {
				$data['pd_extra'] = 0;
			}

			\IPS\Output::i()->jsFiles = array_merge(\IPS\Output::i()->jsFiles, \IPS\Output::i()->js('zpa.js', 'ventas', 'interface'));
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('zpa')->zpaPost($data_forum, $data);
		} catch(\UnderflowException $e) {
			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=forums&module=forums&controller=index', 'front', 'forums'));
		}
	}

	protected function zpaSendData() {
		$member_id = \IPS\Member::loggedIn()->member_id;
		$name = \IPS\Member::loggedIn()->name;
		$email = \IPS\Member::loggedIn()->email;

		if(!$member_id || $name == NULL || $email == NULL) {
			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=core&module=system&controller=login', 'front', 'login'));
		}

		$zpa_id = \IPS\Request::i()->zpa_id;
		$zpa_ph = \IPS\Request::i()->zpa_ph;
		$zpa_pz = \IPS\Request::i()->zpa_pz;
		$zpa_pl = \IPS\Request::i()->zpa_pl;
		$zpa_ps = \IPS\Request::i()->zpa_ps;
		$zpa_pd = \IPS\Request::i()->zpa_pd;
		$zpa_region = \IPS\Request::i()->zpa_region;
		$zpa_code = \IPS\Request::i()->zpa_code;
		$zpa_total = \IPS\Request::i()->zpa_total;
		$time = time();
		$benefit = "Recursos ZPA";
		$benefit_int = CODE_FOR_ZPA;

		if(!$zpa_id ||
		(!$zpa_ph && !$zpa_pz && !$zpa_pl && !$zpa_ps && !$zpa_pd) ||
		\strlen($zpa_code) < 16 || $zpa_total < 0) {
			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=forums&module=forums&controller=index', 'front', 'forums'));
		}

		$buy_id = $this->dbzpa->insert('zp8_buys', array(
			'acc_id' => $zpa_id,
			'p_humans' => $zpa_ph,
			'p_zombies' => $zpa_pz,
			'p_legacy' => $zpa_pl,
			'money' => $zpa_ps,
			'diamonds' => $zpa_pd,
			'bought_money' => ($zpa_region + 1),
			'bought_money_real' => $zpa_total,
			'bought_image' => NULL,
			'bought_timestamp' => $time,
			'bought_ok' => -1
		));

		$payment_id = $this->dbzpa->insert('zp8_payments', array(
			'buy_id' => $buy_id,
			'member_id' => $member_id,
			'code' => $zpa_code,
			'timestamp' => $time,
			'ok' => -1
		));

		$pm_title = "Pedido de {$benefit} realizado correctamente";
		$pm_post = "¡Hola <strong>{$name}</strong>!";
		$pm_post .= "<br />";
		$pm_post .= "¡Tu pedido de {$benefit} fue enviado correctamente!";
		$pm_post .= "<br />";
		$pm_post .= "<br />";
		$pm_post .= "Te dejaremos el siguiente código para que, en caso de problemas relacionados a tu pago, activación o dudas, puedas hablar con cualquiera de los Directores de la comunidad: <strong>{$zpa_code}</strong>.";
		$pm_post .= "<br />";
		$pm_post .= "<br />";
		$pm_post .= "Abajo te dejaremos los pasos donde procederás a realizar el pago. Ten en cuenta que podrás abonar solamente una vez, es decir, no puedes abonar más de dos veces con el enlace, boleta o número de cuenta ya que el sistema solo lo detectará y acreditará una sola vez los recursos pedidos.";
		$pm_post .= "<br />";
		$pm_link = NULL;
		$output = NULL;

		if($zpa_region == 0) { // Perú
			$inter_num = "148-3082988700";
			$bcp_num = "193-36248654-0-00";

			$pm_post .= "<ul>";
			$pm_post .= "<li>Número de cuenta de Interbank: <strong>{$inter_num}</strong></li>";
			$pm_post .= "<li>Número de cuenta de BCP: <strong>{$bcp_num}</strong></li>";
			$pm_post .= "</ul>";
			$pm_post .= "Los números de cuenta mencionados arriba están a nombre de <strong>Jairo Ponte R</strong>. Ve al cajero más cercano y depositale el dinero correspondido.";
			$pm_post .= "<br />";
			$pm_post .= "<br />";
			$pm_post .= "Para que te activemos los recursos que has pedido. Debes proceder el pago anteriormente mencionado; luego de haber hecho el pago, ve al <a href='/ventas/registrar-zpa/'>Registro de ventas del ZPA</a> y registra que has pagado tu pedido. El dato más importante a tener es que tengas el ticket con el que has pagado. Es importante que lo tengas a mano para que podamos corroborar que el pago es verídico.";

			$output = $inter_num.'|'.$bcp_num;
		} else if($zpa_region == 1) { // Argentina
			$data_item['item_id'] = $buy_id;
			$data_item['item_title'] = "Compra de {$benefit} [{$name}]";
			$data_item['item_unit_price'] = $zpa_total;

			$data_payer['payer_name'] = $name;
			$data_payer['payer_email'] = $email;
			$data_payer['payer_date_created'] = date("F j, Y, g:i a", $time);

			$data_preference['preference_external_reference'] = $zpa_code;

			$data_cd = self::generateLinkFromCD($data_item['item_title'], $data_item['item_unit_price'], $data_payer['payer_email'], $data_preference['preference_external_reference']);
			$data_mp = self::generateLinkFromMP($data_item, $data_payer, $data_preference, $benefit_int);

			$cd_link = $data_cd;
			$mp_link = $data_mp;

			$pm_post .= "<ul>";
			$pm_post .= "<li>Boleta para abonar en RapiPago, PagoFácil o medios de pago similares: <a target='_blank' href='{$cd_link}'>Boleta de Cuenta Digital</a></li>";
			$pm_post .= "<li>Link para poder pagar con tarjeta de crédito o débito: <a target='_blank' href='{$mp_link}'>Enlace de Mercado Pago</a></li>";
			$pm_post .= "</ul>";
			$pm_post .= "La activación de los {$benefit} es <strong>AUTOMÁTICA</strong>. Una vez hayas realizado el pago correctamente, se te enviará un mensaje privado avisando de tu activación. Generalmente, al abonar en sucursales como Rapipago, llega aproximadamente por la madrugada; mientras que por Pagofacil, abonos por tarjetas u otros, son al instante. Reiteramos, una vez que el pago llegue, <strong>será activado y avisado de forma automática</strong>.";

			$output = $cd_link."|".$mp_link;
		} else if($zpa_region == 2) { // Paypal
			$pp_link_jairo = 'https://www.paypal.com/paypalme/drunkgaming/'.$zpa_total;
			$pp_link_atsel = 'https://www.paypal.com/paypalme/atsel97/'.$zpa_total;

			$pm_post .= "<ul>";
			$pm_post .= "<li>Enlace para pagar a Jairito Mapper: <a target='_blank' href='{$pp_link_jairo}'>{$pp_link_jairo}</a></li>";
			$pm_post .= "<li>Enlace para pagar a Atsel.: <a target='_blank' href='{$pp_link_atsel}'>{$pp_link_atsel}</a></li>";
			$pm_post .= "</ul>";
			$pm_post .= "";
			$pm_post .= "<br />";
			$pm_post .= "<br />";
			$pm_post .= "Para que te activemos los recursos que has pedido. Debes proceder el pago anteriormente mencionado; luego de haber hecho el pago, ve al <a href='/ventas/registrar-zpa/'>Registro de ventas del ZPA</a> y registra que has pagado tu pedido. El dato más importante a tener es que tengas el ticket con el que has pagado. Es importante que lo tengas a mano para que podamos corroborar que el pago es verídico.";

			$output = $pp_link_jairo."|".$pp_link_atsel;
		} else if($zpa_region == 3) { // Chile
			$be_num = "20314971-9";

			$pm_post .= "<ul>";
			$pm_post .= "<li>Número de cuenta RUT de Bancoestado: <strong>{$be_num}</strong></li>";
			$pm_post .= "</ul>";
			$pm_post .= "Ve al cajero más cercano y depositale el dinero correspondido.";
			$pm_post .= "<br />";
			$pm_post .= "<br />";
			$pm_post .= "Para que te activemos los recursos que has pedido. Debes proceder el pago anteriormente mencionado; luego de haber hecho el pago, ve al <a href='/ventas/registrar-ze/'>Registro de ventas del ZE</a> y registra que has pagado tu pedido. El dato más importante a tener es que tengas el ticket con el que has pagado. Es importante que lo tengas a mano para que podamos corroborar que el pago es verídico.";

			$output = $be_num."|".$be_num;
		}

		$pm_post .= "<br />";
		$pm_post .= "<br />";
		$pm_post .= "¡Saludos!";
		$pm_post .= "<br />";
		$pm_post .= "¡Staff: Drunk-Gaming!";
		$pm_post .= "<br />";
		$pm_post .= "<br />";
		$pm_post .= "<center><span style='color: #cbcbcb; font-size: 10px;'>Recuerda que este es un mensaje privado automático, si contestas esto, ningún miembro del staff verá el mensaje.</span></center>";
		$pm_post .= "<center><span style='color: #cbcbcb; font-size: 10px;'>Si tienes alguna duda, contáctate con cualquiera de los <a href='/staff/'>Directores</a> de la comunidad.</span></center>";

		self::sendPrivateMessage($member_id, $pm_title, $pm_post, $time);
		\IPS\Output::i()->sendOutput($output);
	}

	protected function zpaFinishBuy() {

	}

	protected function zpaApprovedBuy() {
		$collection_id = \IPS\Request::i()->collection_id;
		$collection_status = \IPS\Request::i()->collection_status;
		$external_reference = \IPS\Request::i()->external_reference;
		$payment_type = \IPS\Request::i()->payment_type;
		$merchant_order_id = \IPS\Request::i()->merchant_order_id;
		$preference_id = \IPS\Request::i()->preference_id;
		$site_id = \IPS\Request::i()->site_id;
		$processing_mode = \IPS\Request::i()->processing_mode;
		$merchant_account_id = \IPS\Request::i()->merchant_account_id;
		$title = "Compra aprobada - Drunk-Gaming";
		$message = "Tu compra ya ha sido <strong>aprobada</strong>. Que lo disfrutes.";
		$type = 2;

		$this->dbzpa->update('zp8_payments', array(
			'collection_id' => (int) $collection_id,
			'collection_status' => $collection_status,
			'payment_type' => $payment_type,
			'merchant_order_id' => (int) $merchant_order_id,
			'preference_id' => $preference_id,
			'site_id' => $site_id,
			'processing_mode' => $processing_mode,
			'merchant_account_id' => $merchant_account_id,
			'ok' => 0
		), array('code=?', $external_reference));

		$buy_id = $this->dbzpa->select('buy_id', 'zp8_payments', array('code=?', $external_reference))->first();
		$this->dbzpa->update('zp8_buys', array('bought_ok' => 0), array('id=? AND bought_ok=?', $buy_id, -1));

		$acc_id = $this->dbzpa->select('acc_id', 'zp8_buys', array('id=?', $buy_id))->first();
		$this->dbzpa->update('zp8_pjs', array('bought_ok' => 1), array('acc_id=? AND bought_ok=?', $acc_id, 0));

		self::sendFinallyBuyMessage($title, $message, $type);
	}

	protected function zpaFailureBuy() {
		$collection_id = \IPS\Request::i()->collection_id;
		$collection_status = \IPS\Request::i()->collection_status;
		$external_reference = \IPS\Request::i()->external_reference;
		$payment_type = \IPS\Request::i()->payment_type;
		$merchant_order_id = \IPS\Request::i()->merchant_order_id;
		$preference_id = \IPS\Request::i()->preference_id;
		$site_id = \IPS\Request::i()->site_id;
		$processing_mode = \IPS\Request::i()->processing_mode;
		$merchant_account_id = \IPS\Request::i()->merchant_account_id;
		$title = "Compra rechazada - Drunk-Gaming";
		$message = "Ha ocurrido un error al procesar el pago.";
		$type = 0;

		$this->dbzpa->update('zp8_payments', array(
			'collection_id' => (int) $collection_id,
			'collection_status' => $collection_status,
			'payment_type' => $payment_type,
			'merchant_order_id' => (int) $merchant_order_id,
			'preference_id' => $preference_id,
			'site_id' => $site_id,
			'processing_mode' => $processing_mode,
			'merchant_account_id' => $merchant_account_id,
			'ok' => -3
		), array('code=?', $external_reference));

		$buy_id = $this->dbzpa->select('buy_id', 'zp8_payments', array('code=?', $external_reference))->first();
		$this->dbzpa->update('zp8_buys', array('bought_ok' => -3), array('id=? AND bought_ok=?', $buy_id, -1));

		self::sendFinallyBuyMessage($title, $message, $type);
	}

	protected function pendingBuy() {

	}

	public function generateCode($type) {
		$code = NULL;

		switch($type) {
			case CODE_FOR_VIP: { // VIP
				$code = 'DG';
				$code .= self::generateCodeRandomize();
				$code .= "BVIP";
				$code .= self::generateCodeRandomize();
				$code .= (\IPS\Db::i()->select('COUNT(buy_id)', 'ventas_buys')->first() + 1);

				break;
			} case CODE_FOR_ADMIN: { // Admin CS
				$code = 'DG';
				$code .= self::generateCodeRandomize();
				$code .= "BADM";
				$code .= self::generateCodeRandomize();
				$code .= (\IPS\Db::i()->select('COUNT(buy_id)', 'ventas_buys')->first() + 1);

				break;
			} case CODE_FOR_ZE: { // ZE
				$code = 'DG';
				$code .= self::generateCodeRandomize();
				$code .= "RZEL";
				$code .= self::generateCodeRandomize();
				$code .= ($this->dbze->select('COUNT(id)', 'ze3_buys')->first() + 1);

				break;
			} case CODE_FOR_ZPU: { // ZPU
				$code = 'DG';
				$code .= self::generateCodeRandomize();
				$code .= "RZPU";
				$code .= self::generateCodeRandomize();
				$code .= ($this->dbzpu->select('COUNT(ID)', 'forum_saldo')->first() + 1);

				break;
			} case CODE_FOR_ZPA: { // ZPA
				$code = 'DG';
				$code .= self::generateCodeRandomize();
				$code .= "RZPA";
				$code .= self::generateCodeRandomize();
				$code .= ($this->dbzpa->select('COUNT(id)', 'zp8_buys')->first() + 1);

				break;
			}
		}

		return $code;
	}

	public function generateCodeRandomize() {
		$key = '';
		$pattern = '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$max = (\strlen($pattern) - 1);
		
		for($i = 0; $i < 5; ++$i) {
			$key .= $pattern{\mt_rand(0, $max)};
		}

		return $key;
	}

	public function generateLinkFromCD($concept, $price, $email, $code) {
		$link = "https://www.CuentaDigital.com/api.php?id=581108";
		$link .= "&precio={$price},00";
		$link .= "&venc=30";
		$link .= "&codigo={$code}";
		$link .= "&hacia={$email}";
		$link .= "&concepto={$concept}";
		$hash = "{$code}{$concept}{$price},00dg96541compras";
		$hash_md5 = md5($hash);
		$link .= "&hash={$hash_md5}";

		return $link;
	}

	public function generateLinkFromMP($item=array(), $payer=array(), $preference=array(), $benefit_int) {
		require_once \IPS\ROOT_PATH.'/applications/ventas/interface/MercadoPago/vendor/autoload.php';

		\MercadoPago\SDK::setAccessToken(""); // Credencial (Token de producción) de Mercado Pago

		$mp_preference = new \MercadoPago\Preference();

		$mp_item = new \MercadoPago\Item();
		$mp_item->id = $item['item_id'];
		$mp_item->title = $item['item_title'];
		$mp_item->quantity = 1;
		$mp_item->currency_id = "ARS";
		$mp_item->unit_price = $item['item_unit_price'];

		$mp_preference->items = array($mp_item);
		$mp_preference->external_reference = $preference['preference_external_reference'];

		if($benefit_int == CODE_FOR_ZE) {
			$func_success = "zeApprovedBuy";
			$func_failure = "zeFailureBuy";
		} else if($benefit_int == CODE_FOR_ZPU) {
			$func_success = "zpuApprovedBuy";
			$func_failure = "zpuFailureBuy";
		} else if($benefit_int == CODE_FOR_ZPA) {
			$func_success = "zpaApprovedBuy";
			$func_failure = "zpaFailureBuy";
		} else {
			$func_success = "approvedBuy";
			$func_failure = "failureBuy";
		}

		$mp_preference->back_urls = array(
			"success" => "https://www.drunkgaming.net/ventas/?do={$func_success}",
			"failure" => "https://www.drunkgaming.net/ventas/?do={$func_failure}",
			"pending" => "https://www.drunkgaming.net/ventas/?do=pendingBuy"
		);
		$mp_preference->auto_return = "all";

		$mp_preference->payment_methods = array(
			"excluded_payment_types" => array(array("id" => "ticket"), array("id" => "atm")),
			"installments" => 1
		);

		$mp_preference->binary_mode = true;

		$mp_payer = new \MercadoPago\Payer();
		$mp_payer->email = $payer['payer_email'];

		$mp_preference->payer = $mp_payer;
		$mp_preference->save();

		return $mp_preference->init_point;
	}

	public function sendPrivateMessage($member_id, $pm_title, $pm_post, $time) {
		$pm_bot = \IPS\Member::load(MEMBER_ID_BOT);
		$pm_member = \IPS\Member::load($member_id);

		$conver = \IPS\core\Messenger\Conversation::createItem($pm_bot, $pm_bot->ip_address, \IPS\DateTime::ts($time));
		$conver->title = $pm_title;
		$conver->to_member_id = $pm_member->member_id;
		$conver->save();

		$message = \IPS\core\Messenger\Message::create($conver, $pm_post, TRUE, NULL, NULL, $pm_bot);
		$conver->first_msg_id = $message->id;
		$conver->save();

		$conver->authorize($pm_member);
		$conver->authorize($pm_bot);

		$notification = new \IPS\Notification(\IPS\Application::load('core'), 'private_message_added', $conver, array($conver, $pm_bot));
		$notification->send();

		$pm_member->msg_count_reset = (time() - 1);

		\IPS\core\Messenger\Conversation::rebuildMessageCounts($pm_member);
	}

	public function sendFinallyBuyMessage($title, $message, $type) {
		\IPS\Output::i()->sendOutput(\IPS\Theme::i()->getTemplate('global', 'core')->globalTemplate($title, \IPS\Theme::i()->getTemplate('main')->finallyBuyMessage($type, $message), 200, 'text/html', array(), FALSE));
	}

	public function checkVinc($server, $member_id) {
		$ok = 0;

		switch($server) {
			case ZOMBIE_ESCAPE: {
				try {
					$row = $this->dbze->select('*', 'ze3_accounts', array('vinc=?', $member_id))->first();
					$ok = 1;
				} catch(\UnderflowException $e) {
					$ok = 0;
				}

				break;
			} case ZOMBIE_PLAGUE_UMBRELLA: {
				try {
					$row = $this->dbzpu->select('*', 'pdata', array('Forum_ID=?', $member_id))->first();
					$ok = 1;
				} catch(\UnderflowException $e) {
					$ok = 0;
				}

				break;
			} case ZOMBIE_PLAGUE_ANNIHILATION: {
				try {
					$row = $this->dbzpa->select('*', 'zp8_accounts', array('vinc=?', $member_id))->first();
					$ok = 1;
				} catch(\UnderflowException $e) {
					$ok = 0;
				}

				break;
			}
		}

		return $ok;
	}

	public function getAccounts($server, $member_id) {
		$a = array();

		switch($server) {
			case ZOMBIE_ESCAPE: {
				foreach($this->dbze->select('id, name', 'ze3_accounts', array('vinc=?', $member_id), 'id ASC') as $row) {
					$a[$row['id']] = $row['name'];
				}

				break;
			} case ZOMBIE_PLAGUE_UMBRELLA: {
				foreach($this->dbzpu->select('ID, Name', 'pdata', array('Forum_ID=?', $member_id), 'ID ASC') as $row) {
					$a[$row['ID']] = $row['Name'];
				}

				break;
			} case ZOMBIE_PLAGUE_ANNIHILATION: {
				foreach($this->dbzpa->select('id, name', 'zp8_accounts', array('vinc=?', $member_id), 'id ASC') as $row) {
					$a[$row['id']] = $row['name'];
				}

				break;
			}
		}

		return $a;
	}

	public function getCountrys($server) {
		$per = ($server == ZOMBIE_ESCAPE) ? \IPS\Settings::i()->v_region_per_ze : ($server == ZOMBIE_PLAGUE_UMBRELLA) ? \IPS\Settings::i()->v_region_per_zpu : \IPS\Settings::i()->v_region_per_zpa;
		$arg = ($server == ZOMBIE_ESCAPE) ? \IPS\Settings::i()->v_region_arg_ze : ($server == ZOMBIE_PLAGUE_UMBRELLA) ? \IPS\Settings::i()->v_region_arg_zpu : \IPS\Settings::i()->v_region_arg_zpa;
		$pp = ($server == ZOMBIE_ESCAPE) ? \IPS\Settings::i()->v_region_pp_ze : ($server == ZOMBIE_PLAGUE_UMBRELLA) ? \IPS\Settings::i()->v_region_pp_zpu : \IPS\Settings::i()->v_region_pp_zpa;
		$cl = ($server == ZOMBIE_ESCAPE) ? \IPS\Settings::i()->v_region_cl_ze : ($server == ZOMBIE_PLAGUE_UMBRELLA) ? \IPS\Settings::i()->v_region_cl_zpu : \IPS\Settings::i()->v_region_cl_zpa;

		$data = array();

		if($per) {
			$data[0] = "Perú (Sol peruano)";
		}

		if($arg) {
			$data[1] = "Argentina (Peso argentino)";
		}

		if($pp) {
			$data[2] = "Paypal (Dolar)";
		}

		if($cl) {
			$data[3] = "Chile (Peso chileno)";
		}

		return $data;
	}
}