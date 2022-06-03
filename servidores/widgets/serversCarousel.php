<?php
namespace IPS\servidores\widgets;

if(!\defined('\IPS\SUITE_UNIQUE_KEY'))  {
	header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0').' 403 Forbidden');
	exit;
}

class _serversCarousel extends \IPS\Widget {
	public $key = 'serversCarousel';
	public $app = 'servidores';
	public $plugin = '';

	public function init() {
		\IPS\Output::i()->cssFiles = array_merge(\IPS\Output::i()->cssFiles, \IPS\Theme::i()->css('main.css', 'servidores', 'front'));

		$this->template(array(\IPS\Theme::i()->getTemplate('widgets', $this->app, 'front'), $this->key));
		parent::init();
	}

	public function render() {
		$query = \IPS\Db::i()->select('servidores_servers.*, servidores_games.*', 'servidores_servers', array('servidores_servers.server_enabled=?', 1))->join('servidores_games', 'servidores_games.game_id=servidores_servers.server_game_id');
		$servers = [];
		$players_total = 0;
		$url = \IPS\Http\Url::internal('app=servidores&module=main&controller=main', 'front', 'servidores');

		foreach($query as $row) {
			$servers[] = $row;
			$players_total += $row['server_players_num'];
		}

		return $this->output($servers, $players_total, $url);
	}
}