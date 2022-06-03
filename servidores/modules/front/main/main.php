<?php
namespace IPS\servidores\modules\front\main;

if(!\defined('\IPS\SUITE_UNIQUE_KEY')) {
	header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0').' 403 Forbidden');
	exit;
}

class _main extends \IPS\Dispatcher\Controller {
	public function execute() {
		\IPS\Session::i()->setLocation(\IPS\Http\Url::internal('app=servidores&module=main&controller=main', 'front', 'servidores'), array(), 's_viewing');

		\IPS\Output::i()->sidebar['enabled'] = FALSE;

		\IPS\Output::i()->cssFiles = array_merge(\IPS\Output::i()->cssFiles, \IPS\Theme::i()->css('main.css', 'servidores', 'front'));
		\IPS\Output::i()->jsFiles = array_merge(\IPS\Output::i()->jsFiles, \IPS\Output::i()->js('main.js', 'servidores', 'interface'));
		
		parent::execute();
	}

	protected function manage() {
		$url = \IPS\Http\Url::internal('app=servidores&module=main&controller=main', 'front', 'servidores');

		\IPS\Output::i()->title	= \IPS\Member::loggedIn()->language()->addToStack('module__servidores_main');
		\IPS\Output::i()->breadcrumb['module'] = array($url, \IPS\Member::loggedIn()->language()->addToStack('module__servidores_servers'));

		$query = \IPS\Db::i()->select('servidores_servers.*, servidores_games.*', 'servidores_servers', array('servidores_servers.server_enabled=?', 1))->join('servidores_games', 'servidores_games.game_id=servidores_servers.server_game_id');
		$servers = [];

		$stats['servers_online'] = 0;
		$stats['players_num_t'] = 0;
		$stats['players_max_t'] = 0;

		$stats['record_players_num'] = \IPS\Settings::i()->s_players_num_record;
		$stats['record_players_max'] = \IPS\Settings::i()->s_players_max_record;
		$stats['record_time'] = \IPS\Settings::i()->s_time_record;

		$stats['percent'] = 0;
		$stats['record_percent'] = 0;

		foreach($query as $row) {
			$servers[] = $row;

			if($row['server_online']) {
				++$stats['servers_online'];
			}

			$stats['players_num_t'] += $row['server_players_num'];
			$stats['players_max_t'] += $row['server_players_max'];
		}

		if($stats['players_max_t'] != 0) {
			$stats['percent'] = (($stats['players_num_t'] / $stats['players_max_t']) * 100);
			$stats['record_percent'] = (($stats['record_players_num'] / $stats['players_max_t']) * 100);
			
			$stats['percent'] = (int) $stats['percent'];		
			$stats['record_percent'] = (int) $stats['record_percent'];		
		}

		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('main')->main($url, $servers, $stats);
	}

	protected function toServer() {
		$id = \IPS\Request::i()->id;

		if(!isset($id)) {
			\IPS\Output::i()->error('node_error', 'S/0', 404, '');
		}

		$server = \IPS\servidores\Servers::load($id);
		$seo = \IPS\Http\Url::seoTitle($server->shortname);

		try {
			$server_next = self::getNextServer($server->position);
		} catch(\Exception $e) {
			$server_next = NULL;
		}

		$game = \IPS\servidores\Games::load($server->game_id);

		$s_url_internal = \IPS\Http\Url::internal('app=servidores&module=main&controller=main', 'front', 'servidores');

		$sid_url = "app=servidores&module=main&controller=main&do=toServer&id={$id}&seo={$seo}";
		$sid_url_seo = 'servidores_id';
		$sid_url_internal = \IPS\Http\Url::internal($sid_url, 'front', $sid_url_seo);

		\IPS\Output::i()->title	= $server->shortname;
		\IPS\Output::i()->breadcrumb['module'] = array($s_url_internal, \IPS\Member::loggedIn()->language()->addToStack('module__servidores_servers'));
		\IPS\Output::i()->breadcrumb[] = array($sid_url_internal, $server->shortname);

		$players_info = json_decode($server->players_info, TRUE);
		$chart_maps = $server->getChartMaps();
		$players_chart = $server->getPlayersChart();

		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('main')->displayServer($server, $seo, $server_next, $game, $sid_url_internal, $players_info, $chart_maps, $players_chart);
	}

	public function getNextServer($server_position) {
		$return = array();

		$count_servers = \IPS\servidores\Servers::countServers();
		$server_next_id = ($server_position + 1);
		$server_next = \IPS\servidores\Servers::load(\IPS\Db::i()->select("server_id", "servidores_servers", array("server_position=?", $server_next_id))->first());
		$seo_next = \IPS\Http\Url::seoTitle($server_next->shortname);

		if($server_next_id > $count_servers) {
			return NULL;
		}

		$return[0] = $server_next_id;
		$return[1] = $seo_next;
		$return[2] = $server_next->shortname;

		return $return;
	}

	protected function editGuides() {
		$id = \IPS\Request::i()->id;

		if(!isset($id)) {
			\IPS\Output::i()->error('node_error', 'S/1', 404, '');
		}

		$server = \IPS\servidores\Servers::load($id);
		$seo = \IPS\Http\Url::seoTitle($server->shortname);

		$s_url_internal = \IPS\Http\Url::internal('app=servidores&module=main&controller=main', 'front', 'servidores');

		$sid_url = "app=servidores&module=main&controller=main&do=toServer&id={$id}&seo={$seo}";
		$sid_url_seo = 'servidores_id';
		$sid_url_internal = \IPS\Http\Url::internal($sid_url, 'front', $sid_url_seo);

		$side_url = "app=servidores&module=main&controller=main&do=editGuides&id={$id}&seo={$seo}";
		$side_url_seo = 'servidores_id_editGuides';
		$side_url_internal = \IPS\Http\Url::internal($side_url, 'front', $side_url_seo);

		$title = "Editando gúa del servidor: {$server->shortname}";

		\IPS\Output::i()->title	= $title;
		\IPS\Output::i()->breadcrumb['module'] = array($s_url_internal, \IPS\Member::loggedIn()->language()->addToStack('module__servidores_servers'));
		\IPS\Output::i()->breadcrumb[] = array($sid_url_internal, $server->shortname);
		\IPS\Output::i()->breadcrumb[] = array($side_url_internal, $title);

		$form = new \IPS\Helpers\Form('formEditGuides', '__save_now');
		$form->class = 'ipsForm_vertical';

		$form->add(new \IPS\Helpers\Form\Editor('s_editguides_description', $server->guides, TRUE, array('app' => 'servidores', 'key' => 'editorServers', 'autoSaveKey' => 'servidores-editpage', 'attachIds' => array($server->id))));

		if(\IPS\Member::loggedIn()->member_id == 1) {
			$form->add(new \IPS\Helpers\Form\Text('s_editguides_top15', $server->top15, FALSE));
			$form->add(new \IPS\Helpers\Form\Text('s_editguides_vinc', $server->vinc, FALSE));
		}

		if($values = $form->values()) {
			$server->guides = $values['s_editguides_description'];
			$server->top15 = (($values['s_editguides_top15'] == '') ? NULL : $values['s_editguides_top15']);
			$server->vinc = (($values['s_editguides_vinc'] == '') ? NULL : $values['s_editguides_vinc']);
			$server->save();

			\IPS\Output::i()->redirect($sid_url_internal, '__saved');
		}

		$form = $form->customTemplate(array(\call_user_func_array(array(\IPS\Theme::i(), 'getTemplate'), array('forms', 'core')), 'popupTemplate'));
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('main')->editGuides($server, $form);
	}

	protected function editRules() {
		$id = \IPS\Request::i()->id;

		if(!isset($id)) {
			\IPS\Output::i()->error('node_error', 'S/2', 404, '');
		}

		$server = \IPS\servidores\Servers::load($id);
		$seo = \IPS\Http\Url::seoTitle($server->shortname);

		$s_url_internal = \IPS\Http\Url::internal('app=servidores&module=main&controller=main', 'front', 'servidores');

		$sid_url = "app=servidores&module=main&controller=main&do=toServer&id={$id}&seo={$seo}";
		$sid_url_seo = 'servidores_id';
		$sid_url_internal = \IPS\Http\Url::internal($sid_url, 'front', $sid_url_seo);

		$side_url = "app=servidores&module=main&controller=main&do=editRules&id={$id}&seo={$seo}";
		$side_url_seo = 'servidores_id_editRules';
		$side_url_internal = \IPS\Http\Url::internal($side_url, 'front', $side_url_seo);

		$title = "Editando reglamento del servidor: {$server->shortname}";

		\IPS\Output::i()->title	= $title;
		\IPS\Output::i()->breadcrumb['module'] = array($s_url_internal, \IPS\Member::loggedIn()->language()->addToStack('module__servidores_servers'));
		\IPS\Output::i()->breadcrumb[] = array($sid_url_internal, $server->shortname);
		\IPS\Output::i()->breadcrumb[] = array($side_url_internal, $title);

		$form = new \IPS\Helpers\Form('formEditRules', '__save_now');
		$form->class = 'ipsForm_vertical';

		$form->add(new \IPS\Helpers\Form\Editor('s_editrules_description', $server->rules, TRUE, array('app' => 'servidores', 'key' => 'editorServers', 'autoSaveKey' => 'servidores-editpage', 'attachIds' => array($server->id))));

		if($values = $form->values()) {
			$server->rules = $values['s_editrules_description'];
			$server->save();

			\IPS\Output::i()->redirect($sid_url_internal, '__saved');
		}

		$form = $form->customTemplate(array(\call_user_func_array(array(\IPS\Theme::i(), 'getTemplate'), array('forms', 'core')), 'popupTemplate'));
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('main')->editRules($server, $form);
	}

	protected function banList() {
		$id = \IPS\Request::i()->id;

		if(!isset($id)) {
			\IPS\Output::i()->error('node_error', 'S/3', 404, '');
		}

		$server = \IPS\servidores\Servers::load($id);
		$seo = \IPS\Http\Url::seoTitle($server->shortname);

		$s_url_internal = \IPS\Http\Url::internal('app=servidores&module=main&controller=main', 'front', 'servidores');

		$sid_url = "app=servidores&module=main&controller=main&do=toServer&id={$id}&seo={$seo}";
		$sid_url_seo = 'servidores_id';
		$sid_url_internal = \IPS\Http\Url::internal($sid_url, 'front', $sid_url_seo);

		$bl_url = "app=servidores&module=main&controller=main&do=banList&id={$id}&seo={$seo}";
		$bl_url_seo = 'servidores_id_banList';
		$bl_url_internal = \IPS\Http\Url::internal($bl_url, 'front', $bl_url_seo);

		\IPS\Output::i()->title	= "Baneos del {$server->shortname}";
		\IPS\Output::i()->breadcrumb['module'] = array($s_url_internal, \IPS\Member::loggedIn()->language()->addToStack('module__servidores_servers'));
		\IPS\Output::i()->breadcrumb[] = array($sid_url_internal, $server->shortname);
		\IPS\Output::i()->breadcrumb[] = array($bl_url_internal, 'Lista de Baneos');

		$table = new \IPS\servidores\Table\Db('focs', \IPS\Application::load('servidores')->getConnection(), 'gral_bans', $bl_url_internal, array(array('server_id=?', $id)));
		$table->rowsTemplate = array(\IPS\Theme::i()->getTemplate('main'), 'banListRow');
		$table->orderBy = 'finish, user_name ASC';
		$table->sortBy = $table->sortBy ?: 'id';
		$table->sortDirection = $table->sortDirection ?: 'DESC';
		$table->parsers = array(
			'reason' => function($val) {
				return \utf8_decode($val);
			}
		);

		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('main')->banList($server, $sid_url, $sid_url_seo, (string) $table);
	}
}