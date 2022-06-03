<?php
namespace IPS\ventas\modules\front\lista;

if(!\defined('\IPS\SUITE_UNIQUE_KEY')) {
	header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0').' 403 Forbidden');
	exit;
}

class _main extends \IPS\Dispatcher\Controller {
	public $url;
	public $urlZE;
	public $urlZPU;
	public $urlZPA;

	public function execute() {
		$this->url = \IPS\Http\Url::internal('app=ventas&module=lista&controller=main', 'front', 'lista');
		$this->urlZE = \IPS\Http\Url::internal('app=ventas&module=lista&controller=main&area=buyListZE', 'front', 'lista_ze');
		$this->urlZPU = \IPS\Http\Url::internal('app=ventas&module=lista&controller=main&area=buyListZPU', 'front', 'lista_zpu');
		$this->urlZPA = \IPS\Http\Url::internal('app=ventas&module=lista&controller=main&area=buyListZPA', 'front', 'lista_zpa');

		\IPS\Session::i()->setLocation($this->url, array(), 'Viendo la lista de Ventas');

		\IPS\Output::i()->title	= 'Lista de Ventas';
		\IPS\Output::i()->breadcrumb['module'] = array($this->url, 'Lista de Ventas');
		\IPS\Output::i()->sidebar['enabled'] = FALSE;

		parent::execute();
	}

	protected function manage() {
		if(!\IPS\Member::loggedIn()->member_id) {
			\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=core&module=system&controller=login', 'front', 'login'));
		}

		if(!\IPS\Member::loggedIn()->inGroup(array_filter(array_filter(explode(',', \IPS\Settings::i()->v_access_list))))) {
			\IPS\Output::i()->error('No tienes permisos para acceder a la sección.', 'DG/VL/0', 403, '');
		}

		$area = \IPS\Request::i()->area ?: 'buyList';
		$method_name = "_{$area}";

		if(method_exists($this, $method_name)) {
			$output = $this->$method_name();
		}

		if(!\IPS\Request::i()->isAjax()) {
			if(\IPS\Request::i()->service) {
				$area = "{$area}_" . \IPS\Request::i()->service;
			}

			if($output) {
				\IPS\Output::i()->output .= \IPS\Theme::i()->getTemplate('lista')->main($area, $output);
			}
		} else if($output) {
			\IPS\Output::i()->output .= $output;
		}
	}

	protected function _buyList() {
		$table = new \IPS\Helpers\Table\Db('ventas_buys', $this->url);
		$table->rowsTemplate = array(\IPS\Theme::i()->getTemplate('lista'), 'rows');
		$table->orderBy = 'buy_timestamp, buy_name ASC';
		$table->sortBy = $table->sortBy ?: 'buy_id';
		$table->sortDirection = $table->sortDirection ?: 'DESC';
		$table->filters = array(
			'__statusr5' => "buy_status='-5'",
			'__statusr4' => "buy_status='-4'",
			'__statusr3' => "buy_status='-3'",
			'__statusr2' => "buy_status='-2'",
			'__statusr1' => "buy_status='-1'",
			'__status0' => "buy_status='0'",
			'__status1' => "buy_status='1'",
			'__status2' => "buy_status='2'"
		);
		$table->parsers = array(
			'buy_server' => function($val) {
				return \IPS\Application::load('ventas')->getServerName($val);
			}
		);

		return $table;
	}

	protected function _viewBuyList() {
		$id = \IPS\Request::i()->id;

		if(!$id) {
			\IPS\Output::i()->error('No se ha especificado el #Id.', 'DG/VL/1', 403, '');
		}

		try {
			$row = \IPS\Db::i()->select('ventas_buys.*, ventas_notifications.*', 'ventas_buys', array('buy_id=?', $id), NULL, 1)->join('ventas_notifications', 'ventas_notifications.notification_buy_id=ventas_buys.buy_id', 'LEFT')->first();

			if(isset($row['buy_server'])) {
				$row['buy_server'] = \IPS\Application::load('ventas')->getServerName($row['buy_server']); 
			}
		} catch(\UnderflowException $e) {
			
		}

		return \IPS\Theme::i()->getTemplate('lista')->viewBuyList($row);
	}

	protected function _cancelBuyList() {
		$id = \IPS\Request::i()->id;

		if(!$id) {
			\IPS\Output::i()->error('No se ha especificado el #Id.', 'DG/VL/3', 403, '');
		}

		\IPS\Db::i()->update('ventas_buys', array('buy_status' => -3), array('buy_id=?', $id));

		\IPS\Output::i()->redirect($this->url, 'Venta cancelada correctamente.');
	}

	protected function _processBuyList() {
		$id = \IPS\Request::i()->id;

		if(!$id) {
			\IPS\Output::i()->error('No se ha especificado el #Id.', 'DG/VL/4', 403, '');
		}

		try {
			$row = \IPS\Db::i()->select('*', 'ventas_buys', array('buy_id=?', $id), NULL, 1)->first();
			
			if($row['buy_member_id'] && $row['buy_name'] != NULL && $row['buy_email'] != NULL) {
				$bot = \IPS\Member::load(9);

				$member_id = $row['buy_member_id'];
				$member = \IPS\Member::load($member_id);

				$title = "[{$row['buy_benefit']}] Activación";
				$post = "¡Hola <strong>{$row['buy_name']}</strong>!<br>";

				$post .= "¡Su {$row['buy_benefit']} se ha activado exitósamente!<br><br>";
				$post .= "Abajo te daremos los datos para que actives tu {$row['buy_benefit']} dentro del cliente de tu Counter-Strike:";
				$post .= "<ul>";
				$post .= "<li>Tag CS: {$row['buy_tagcs']}</li>";
				$post .= "<li>Contraseña: {$row['buy_setinfo']}</li>";
				$post .= "</ul>";
				$post .= "Para activar tu {$row['buy_benefit']} dentro de los servidores de la comunidad, debes visitar el siguiente enlace y seguir los pasos para poder activarlo.";
				$post .= "<ul>";
				if(\stripos($row['buy_benefit'], 'vip') === FALSE) {
					$post .= "<li><a href='https://www.DrunkGaming.net/admin-panel/'>Estado Admin CS</a></li>";
				} else {
					$post .= "<li><a href='https://www.DrunkGaming.net/estado-vip/'>Estado VIP</a></li>";
				}
				$post .= "</ul>";
				$post .= "En dicho enlace verás los datos de tu {$row['buy_benefit']}, los pasos para poder activarlo y también puedes configurarlo a tu gusto. Todo está explicado en el panel que te acabamos de dejar.<br><br>";
				$post .= "Esperemos que disfrute de los beneficios que le ofrecemos.<br><br>";
				$post .= "¡Saludos!<br>";
				$post .= "Staff Drunk-Gaming<br><br>";
				$post .= "<center><span style='color: #cbcbcb; font-size: 10px;'>Recuerda que este es un mensaje privado automático, si contestas esto, ningún miembro del staff verá el mensaje.</span></center>";
				$post .= "<center><span style='color: #cbcbcb; font-size: 10px;'>Si tienes alguna duda, contáctate con cualquiera de los directores (marcados en rojo)..</span></center>";

				$conversation = \IPS\core\Messenger\Conversation::createItem($bot, $bot->ip_address, \IPS\DateTime::ts(time()));
				$conversation->title = $title;
				$conversation->to_member_id = $member->member_id;
				$conversation->save();

				$message = \IPS\core\Messenger\Message::create($conversation, $post, TRUE, NULL, NULL, $bot);
				$conversation->first_msg_id = $message->id;
				$conversation->save();

				$conversation->authorize($member);
				$conversation->authorize($bot);

				$notification = new \IPS\Notification(\IPS\Application::load('core'), 'private_message_added', $conversation, array($conversation, $bot));
				$notification->send();

				$member->msg_count_reset = (time() - 1);
				\IPS\core\Messenger\Conversation::rebuildMessageCounts($member);

		        \IPS\Db::i()->update('ventas_buys', array('buy_status' => 2), array('buy_id=?', $id));

		        $access = NULL;

				if(\stripos($row['buy_benefit'], 'vip') === FALSE) {
					$access = 'abcdeijuvy';
				} else {
					$access = 'b';
				}

				$db = \IPS\Application::load('ventas')->getDb();

				$db->insert('gral_admins', array(
					'name' => $row['buy_tagcs'],
					'password' => md5($row['buy_setinfo']),
					'access' => $access,
					'finish' => ((($row['buy_days'] + $row['buy_days_extras']) * 60 * 60 * 24) + time()),
					'staff_forum_id' => \IPS\Member::loggedIn()->member_id,
					'forum_id' => $member_id,
					'server_id' => $row['buy_server']
				));

				$member_gid = \IPS\Member::load($member_id)->member_group_id;

				if((\stripos($row['buy_benefit'], 'vip') === FALSE && $member_gid == 8) || $member_gid == 15 || $member_gid == 3) {
					\IPS\Db::i()->update('core_members', array('member_group_id' => \IPS\Application::load('ventas')->getAccessForumId($access)), array('member_id=?', $member_id));
				}

				\IPS\Output::i()->redirect($this->url, 'Venta procesada correctamente.');
			}
		} catch(\UnderflowException $e) {
			
		}
	}

	protected function _buyListZE() {
		$table = new \IPS\adminpanel\Table\Db('focs_ze', \IPS\Application::load('ventas')->getConnectionZE(), 'ze3_payments', $this->urlZE);
		$table->rowsTemplate = array(\IPS\Theme::i()->getTemplate('lista'), 'zeRows');
		$table->orderBy = 'timestamp, member_id ASC';
		$table->sortBy = $table->sortBy ?: 'id';
		$table->sortDirection = $table->sortDirection ?: 'DESC';
		$table->filters = array(
			'__boughtokr6' => "ok='-6'",
			'__boughtokr5' => "ok='-5'",
			'__boughtokr4' => "ok='-4'",
			'__boughtokr3' => "ok='-3'",
			'__boughtokr2' => "ok='-2'",
			'__boughtokr1' => "ok='-1'",
			'__boughtok0' => "ok='0'",
			'__boughtok1' => "ok='1'"
		);

		return $table;
	}

	protected function _viewBuyListZE() {
		$id = \IPS\Request::i()->id;

		if(!$id) {
			\IPS\Output::i()->error('No se ha especificado el #Id.', 'DG/VL/3', 403, '');
		}

		$db_ze = \IPS\Application::load('ventas')->getDbZE();

		try {
			$row = $db_ze->select('*', 'ze3_buys', array('id=?', $id), NULL, 1)->first();
		} catch(\UnderflowException $e) {
			
		}

		return \IPS\Theme::i()->getTemplate('lista')->viewBuyListZE($row);
	}

	protected function _cancelBuyListZE() {
		$id = \IPS\Request::i()->id;

		if(!$id) {
			\IPS\Output::i()->error('No se ha especificado el #Id.', 'DG/VL/3', 403, '');
		}

		$db_ze = \IPS\Application::load('ventas')->getDbZE();

		$db_ze->update('ze3_buys', array('bought_ok' => -4), array('id=?', $id));
		$db_ze->update('ze3_payments', array('ok' => -4), array('buy_id=?', $id));

		\IPS\Output::i()->redirect($this->urlZE, 'Venta cancelada correctamente.');
	}

	protected function _processBuyListZE() {
		$id = \IPS\Request::i()->id;

		if(!$id) {
			\IPS\Output::i()->error('No se ha especificado el #Id.', 'DG/VL/3', 403, '');
		}

		$db_ze = \IPS\Application::load('ventas')->getDbZE();

		try {
			$row = $db_ze->select('*', 'ze3_buys', array('id=?', $id), NULL, 1)->first();

			if($row['bought_ok'] == -1) {
				$db_ze->update('ze3_pjs', array('bought_ok' => 1), array('acc_id=?', $row['acc_id']));
				$db_ze->update('ze3_buys', array('bought_ok' => 0), array('id=?', $id));
				$db_ze->update('ze3_payments', array('ok' => 0), array('buy_id=?', $id));

				\IPS\Output::i()->redirect($this->urlZE, 'Venta procesada correctamente.');
			}
		} catch(\UnderflowException $e) {
			
		}
	}

	protected function _buyListZPU() {
		$table = new \IPS\adminpanel\Table\Db('focs_zpu', \IPS\Application::load('ventas')->getConnectionZPU(), 'forum_saldo_payments', $this->urlZPU);
		$table->rowsTemplate = array(\IPS\Theme::i()->getTemplate('lista'), 'zpuRows');
		$table->orderBy = 'timestamp, member_id ASC';
		$table->sortBy = $table->sortBy ?: 'id';
		$table->sortDirection = $table->sortDirection ?: 'DESC';
		$table->filters = array(
			'__boughtokr6' => "ok='-6'",
			'__boughtokr5' => "ok='-5'",
			'__boughtokr4' => "ok='-4'",
			'__boughtokr3' => "ok='-3'",
			'__boughtokr2' => "ok='-2'",
			'__boughtokr1' => "ok='-1'",
			'__boughtok0' => "ok='0'",
			'__boughtok1' => "ok='1'"
		);

		return $table;
	}

	protected function _viewBuyListZPU() {
		$id = \IPS\Request::i()->id;

		if(!$id) {
			\IPS\Output::i()->error('No se ha especificado el #Id.', 'DG/VL/3', 403, '');
		}

		$db_zpu = \IPS\Application::load('ventas')->getDbZPU();

		try {
			$row = $db_zpu->select('*', 'forum_saldo', array('ID=?', $id), NULL, 1)->first();
		} catch(\UnderflowException $e) {
			
		}

		return \IPS\Theme::i()->getTemplate('lista')->viewBuyListZPU($row);
	}

	protected function _cancelBuyListZPU() {
		$id = \IPS\Request::i()->id;

		if(!$id) {
			\IPS\Output::i()->error('No se ha especificado el #Id.', 'DG/VL/3', 403, '');
		}

		$db_zpu = \IPS\Application::load('ventas')->getDbZPU();

		$db_zpu->update('forum_saldo', array('Deprecated' => -4), array('ID=?', $id));
		$db_zpu->update('forum_saldo_payments', array('ok' => -4), array('buy_id=?', $id));

		\IPS\Output::i()->redirect($this->urlZPU, 'Venta cancelada correctamente.');
	}

	protected function _processBuyListZPU() {
		$id = \IPS\Request::i()->id;

		if(!$id) {
			\IPS\Output::i()->error('No se ha especificado el #Id.', 'DG/VL/3', 403, '');
		}

		$db_zpu = \IPS\Application::load('ventas')->getDbZPU();

		try {
			$row = $db_zpu->select('*', 'forum_saldo', array('ID=?', $id), NULL, 1)->first();

			if($row['Deprecated'] == -1) {
				$db_zpu->update('forum_saldo', array('Deprecated' => 0), array('ID=?', $id));
				$db_zpu->update('forum_saldo_payments', array('ok' => 0), array('buy_id=?', $id));

				\IPS\Output::i()->redirect($this->urlZPU, 'Venta procesada correctamente.');
			}
		} catch(\UnderflowException $e) {
			
		}
	}

	protected function _buyListZPA() {
		$table = new \IPS\adminpanel\Table\Db('focs_zpa', \IPS\Application::load('ventas')->getConnectionZPA(), 'zp8_payments', $this->urlZPA);
		$table->rowsTemplate = array(\IPS\Theme::i()->getTemplate('lista'), 'zpaRows');
		$table->orderBy = 'timestamp, member_id ASC';
		$table->sortBy = $table->sortBy ?: 'id';
		$table->sortDirection = $table->sortDirection ?: 'DESC';
		$table->filters = array(
			'__boughtokr6' => "ok='-6'",
			'__boughtokr5' => "ok='-5'",
			'__boughtokr4' => "ok='-4'",
			'__boughtokr3' => "ok='-3'",
			'__boughtokr2' => "ok='-2'",
			'__boughtokr1' => "ok='-1'",
			'__boughtok0' => "ok='0'",
			'__boughtok1' => "ok='1'"
		);

		return $table;
	}

	protected function _viewBuyListZPA() {
		$id = \IPS\Request::i()->id;

		if(!$id) {
			\IPS\Output::i()->error('No se ha especificado el #Id.', 'DG/VL/3', 403, '');
		}

		$db_zpa = \IPS\Application::load('ventas')->getDbZPA();

		try {
			$row = $db_zpa->select('*', 'zp8_buys', array('id=?', $id), NULL, 1)->first();
		} catch(\UnderflowException $e) {
			
		}

		return \IPS\Theme::i()->getTemplate('lista')->viewBuyListZPA($row);
	}

	protected function _cancelBuyListZPA() {
		$id = \IPS\Request::i()->id;

		if(!$id) {
			\IPS\Output::i()->error('No se ha especificado el #Id.', 'DG/VL/3', 403, '');
		}

		$db_zpa = \IPS\Application::load('ventas')->getDbZPA();

		$db_zpa->update('zp8_buys', array('bought_ok' => -4), array('id=?', $id));
		$db_zpa->update('zp8_payments', array('ok' => -4), array('buy_id=?', $id));

		\IPS\Output::i()->redirect($this->urlZPA, 'Venta cancelada correctamente.');
	}

	protected function _processBuyListZPA() {
		$id = \IPS\Request::i()->id;

		if(!$id) {
			\IPS\Output::i()->error('No se ha especificado el #Id.', 'DG/VL/3', 403, '');
		}

		$db_zpa = \IPS\Application::load('ventas')->getDbZPA();

		try {
			$row = $db_zpa->select('*', 'zp8_buys', array('id=?', $id), NULL, 1)->first();

			if($row['bought_ok'] == -1) {
				$db_zpa->update('zp8_pjs', array('bought_ok' => 1), array('acc_id=?', $row['acc_id']));
				$db_zpa->update('zp8_buys', array('bought_ok' => 0), array('id=?', $id));
				$db_zpa->update('zp8_payments', array('ok' => 0), array('buy_id=?', $id));

				\IPS\Output::i()->redirect($this->urlZPA, 'Venta procesada correctamente.');
			}
		} catch(\UnderflowException $e) {
			
		}
	}
}