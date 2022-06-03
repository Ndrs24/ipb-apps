<?php
namespace IPS\ventas\tasks;

if(!\defined('\IPS\SUITE_UNIQUE_KEY')) {
	header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0').' 403 Forbidden');
	exit;
}

const MEMBER_ID_BOT = 9;

class _checkBuys extends \IPS\Task {
	public function execute() {
		$query = \IPS\Db::i()->select('*', 'ventas_buys', array('buy_status=?', 1));
		$pm_title = NULL;
		$pm_post = NULL;
		$db = \IPS\Application::load('ventas')->getDb();

		foreach($query as $row) {
			\IPS\Db::i()->update('ventas_buys', array('buy_status' => 2), array('buy_id=? AND buy_status=?', $row['buy_id'], 1));

			$pm_title = "Pedido de {$row['buy_benefit']} activado correctamente";

			$pm_post = "¡Hola <strong>{$row['buy_name']}</strong>!";
			$pm_post .= "<br />";
			$pm_post .= "¡Su {$row['buy_benefit']} se ha activado exitósamente!";
			$pm_post .= "<br />";
			$pm_post .= "<br />";
			$pm_post .= "Abajo te daremos los datos para que actives tu {$row['buy_benefit']} dentro del cliente de tu Counter-Strike:";
			$pm_post .= "<ul>";
			$pm_post .= "<li>Tag CS: <strong>{$row['buy_tagcs']}</strong></li>";
			$pm_post .= "<li>Contraseña: <strong>{$row['buy_setinfo']}</strong></li>";
			$pm_post .= "</ul>";
			$pm_post .= "Para activar tu {$row['buy_benefit']} dentro de los servidores de la comunidad, debes visitar el siguiente enlace y seguir los pasos para poder activarlo.";
			$pm_post .= "<ul>";
			if(\stripos($row['buy_benefit'], 'vip') === FALSE) {
				$pm_post .= "<li><a target='_blank' href='https://www.drunkgaming.net/admin-panel/'>Estado de tu Admin CS</a></li>";
			} else {
				$pm_post .= "<li><a target='_blank' href='https://www.drunkgaming.net/estado-vip/'>Estado de tu VIP</a></li>";
			}
			$pm_post .= "</ul>";
			$pm_post .= "En dicho enlace verás los datos de tu {$row['buy_benefit']}, los pasos para poder activarlo y también puedes configurarlo a tu gusto. Todo está explicado en el panel que te acabamos de dejar.";
			$pm_post .= "<br />";
			$pm_post .= "<br />";
			$pm_post .= "Por favor, antes y siempre ante la duda, lee el <a hreff='/reglamento/'>reglamento</a> impuesto por la comunidad para no quedarte con ninguna duda. En caso de tenerla, consulta con algún <a href='/staff/'>miembro del Staff</a>, o puedes hacer una publicación en la zona correspondientes para tus dudas.";
			$pm_post .= "<br />";
			$pm_post .= "<br />";
			$pm_post .= "Esperemos que disfrute de los beneficios que le ofrecemos.";
			$pm_post .= "<br />";
			$pm_post .= "<br />";
			$pm_post .= "¡Saludos!";
			$pm_post .= "<br />";
			$pm_post .= "¡Staff: Drunk-Gaming!";
			$pm_post .= "<br />";
			$pm_post .= "<br />";
			$pm_post .= "<center><span style='color: #cbcbcb; font-size: 10px;'>Recuerda que este es un mensaje privado automático, si contestas esto, ningún miembro del staff verá el mensaje.</span></center>";
			$pm_post .= "<center><span style='color: #cbcbcb; font-size: 10px;'>Si tienes alguna duda, contáctate con cualquiera de los <a href='/staff/'>Directores</a> de la comunidad.</span></center>";

			self::sendPrivateMessage($row['buy_member_id'], $pm_title, $pm_post, time());

			try {
				$row_admin = $db->select("*", "gral_admins", array("name=? AND access=?", $row['buy_tagcs'], ((\stripos($row['buy_benefit'], 'vip') === FALSE) ? "abcdeijuvy" : "b")))->first();

				$db->update('gral_admins', array(
					'password' => md5($row['buy_setinfo']),
					'finish' => ((($row['buy_days'] + $row['buy_days_extras']) * 60 * 60 * 24) + $row_admin['finish']),
					'server_id' => $row['buy_server']
				), array("name=? AND access=?", $row['buy_tagcs'], ((\stripos($row['buy_benefit'], 'vip') === FALSE) ? "abcdeijuvy" : "b")));
			} catch(\UnderflowException $e) {
				$db->insert('gral_admins', array(
					'name' => $row['buy_tagcs'],
					'password' => md5($row['buy_setinfo']),
					'access' => ((\stripos($row['buy_benefit'], 'vip') === FALSE) ? "abcdeijuvy" : "b"),
					'finish' => ((($row['buy_days'] + $row['buy_days_extras']) * 60 * 60 * 24) + time()),
					'staff_forum_id' => MEMBER_ID_BOT,
					'forum_id' => $row['buy_member_id'],
					'server_id' => $row['buy_server']
				));
			}

			$member_group_id = \IPS\Member::load($row['buy_member_id'])->member_group_id;

			if($member_group_id == 3 || /* Usuario */
			$member_group_id == 15 || /* Girl */
			$member_group_id == 16 || /* Veterano */
			$member_group_id == 8) { /* VIP (En caso de tener VIP y ahora tiene Admin, se le cambia el Rango) */
				if(\stripos($row['buy_benefit'], 'vip') === FALSE) {
					\IPS\Db::i()->update('core_members', array('member_group_id' => 9), array('member_id=?', $member_id));
				} else {
					\IPS\Db::i()->update('core_members', array('member_group_id' => 8), array('member_id=?', $member_id));
				}
			}
		}

		return NULL;
	}

	public function cleanup() {
		
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
}