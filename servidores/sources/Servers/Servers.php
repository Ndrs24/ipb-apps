<?php
namespace IPS\servidores;

class _Servers extends \IPS\Node\Model {
	protected static $multitons;
	protected static $defaultValues = NULL;

	public static $databaseTable = 'servidores_servers';
	public static $databaseColumnOrder = 'position';
	public static $databaseColumnId = 'id';
	public static $databasePrefix = 'server_';
    public static $nodeTitle = 'module__servidores_servers';
	public static $titleLangPrefix = 's_server_';
	public static $modalForms = true;

	public function form(&$form) {
		if($this->position) {
			$form->add(new \IPS\Helpers\Form\Number("server_position", $this->position, true, array("min" => 1)));
		} else {
			$form->add(new \IPS\Helpers\Form\Number("server_position", 1, true, array("min" => 1)));
		}

		$games = \IPS\servidores\Games::allGames();
		$form->add(new \IPS\Helpers\Form\Select("server_game_id", array_filter(explode(",", $this->game_id)), true, array("options" => $games, "multiple" => false, "parse" => "normal")));

		$form->add(new \IPS\Helpers\Form\Text("server_shortname", $this->shortname, true));
		$form->add(new \IPS\Helpers\Form\Text("server_ip", $this->ip, true));

		if($this->logo) {
			$form->add(new \IPS\Helpers\Form\Upload("server_logo", \IPS\File::get("servidores_fileServer", $this->logo), false, array("storageExtension" => "servidores_fileServer")));
		} else {
			$form->add(new \IPS\Helpers\Form\Upload("server_logo", NULL, false, array("storageExtension" => "servidores_fileServer")));
		}

		$form->add(new \IPS\Helpers\Form\Textarea("server_infomod", $this->infomod, false));
		$form->add(new \IPS\Helpers\Form\Select("server_access_guides", array_filter(explode(",", $this->access_guides)), false, array("options" => \IPS\Member\Group::groups(), "multiple" => true, "parse" => "normal")));
		$form->add(new \IPS\Helpers\Form\Select("server_access_rules", array_filter(explode(",", $this->access_rules)), false, array("options" => \IPS\Member\Group::groups(), "multiple" => true, "parse" => "normal")));
		
		if($this->enabled) {
			$form->add(new \IPS\Helpers\Form\YesNo("server_enabled", $this->enabled, true));
		} else {
			$form->add(new \IPS\Helpers\Form\YesNo("server_enabled", 1, true));
		}
	}

	public function formatFormValues($values) {
		if(!$this->id) {
			$this->save();
		}

		foreach(array("server_shortname" => "s_server_{$this->id}") as $k => $v) {
			if(isset($values[$k])) {
				\IPS\Lang::saveCustom("servidores", $v, $values[$k]);
			}
		}

		$values["server_longname"] = $values["server_shortname"];

		\IPS\File::claimAttachments("servidores-editpage", $this->id);
		return $values;
	}
	
	public function postSaveForm($values) {
		try {
			$db = \IPS\Db::i()->select("*", "core_tasks", array("`app`=? AND `key`=?", "servidores", "queryServers"))->first();
			$task = \IPS\Task::constructFromData($db);
			
			if($task->running) {
				$task->unlock();
			}
			
			$output = $task->run();
			
			if($output !== NULL) {
				\IPS\Output::i()->error("s_task_unsucess", "S/TASK/0", 403, "");
			}
		} catch(\Exception $e) {
			\IPS\Log::log($e, "uncaught_exception");
		}
	}

	public static function countServers() {
		return \IPS\Db::i()->select("COUNT(*)", "servidores_servers")->first();
	}

	public function getButtons($url, $subnode=false) {
		$buttons = array();
		$buttons["reset_stats"] = array(
			"icon"	=> "minus",
			"title"	=> "s_reset_stats",
			"link"	=> $url->setQueryString(array("do" => "resetStat", "id" => $this->id)),
			"data"	=> array("confirm" => "TRUE")
		);
		
		$buttons = array_merge(parent::getButtons($url, $subnode), $buttons);
		return $buttons;
	}

    public function __clone() {
        if($this->skipCloneDuplication === true) {
            return;
        }

        parent::__clone();

		$this->longname = NULL;
		$this->players_num = 0;
		$this->players_max = 1;
		$this->players_most = 0;
		$this->players_info = NULL;
		$this->online = 0;
		$this->save();
    }

	public function resetServerStat() {
		$db = \IPS\Db::i()->select("*", "servidores_stats", array("stat_server_id=?", $this->id));
		$node = "IPS\servidores\Stats";

		foreach(new \IPS\Patterns\ActiveRecordIterator($db, $node) as $stat) {
			$stat->delete();
		}

		$this->players_most = 0;
		$this->save();
	}
	
	public function url() {
		return \IPS\Http\Url::internal("app=servidores&module=main&controller=main&id={$this->_id}", "front", "servidores_id");
	}
	
	public function canEditGuides() {
		if(\IPS\Member::loggedIn()->inGroup(array_filter(explode(",", $this->access_guides)))) {
			return true;
		}

		return false;
	}
	
	public function canEditRules() {
		if(\IPS\Member::loggedIn()->inGroup(array_filter(explode(",", $this->access_rules)))) {
			return true;
		}

		return false;
	}

	public function getChartMaps() {
		$chart = new \IPS\Helpers\Chart;

		$chart->addHeader(\IPS\Member::loggedIn()->language()->get('s_chart_stats_map'), "string");
		$chart->addHeader(\IPS\Member::loggedIn()->language()->get('s_chart_stats_percent'), "number");

		$db = \IPS\Application::load('servidores')->getDb();
		$result = $db->select('*', 'gral_maps', array('server_id=?', $this->position), 'count ASC');

		foreach($result as $row) {
     		$chart->addRow(array($row['mapname'], $row['count']));
  		}

  		$maps = $chart->render('PieChart', array(
			'backgroundColor' => 'transparent',
			'is3D' => true,
			'height' => '300',
			'chartArea' => array('width' => '100%', 'height' => '80%'),
			'sliceVisibilityThreshold' => '0.02',
			'tooltipText' => 'percentage',
			'legend' => array('textStyle' => array('color' => '#cccccc'), 'position' => 'bottom')
		));

		return \IPS\Theme::i()->getTemplate('charts')->maps($maps);
	}

	public function getPlayersChart() {
		$chart = new \IPS\Helpers\Chart;
		
		$chart->addHeader(\IPS\Member::loggedIn()->language()->get('s_chart_players_time'), "string");
		$chart->addHeader(\IPS\Member::loggedIn()->language()->get('s_chart_players_amount'), "number");
		
		$result = \IPS\Db::i()->select('stat_players, stat_players_time', 'servidores_stats', array('stat_server_id=?', $this->id), 'stat_players_time ASC');
		
		foreach($result as $row) {
     		$time = new \IPS\DateTime("@{$row['stat_players_time']}");
			$time = $time->format('d M Y H:i:s');
     		$chart->addRow(array($time, $row['stat_players']));
  		}

  		$players = $chart->render('AreaChart', array(
  			'isStacked' => false,
			'backgroundColor' => 'transparent',
			'chartArea' => array('width' => '80%', 'height' => '80%'),
			'legend' => array('position' => 'none'),
			'height' => 300,
			'lineWidth' => 1,
			'areaOpacity' => 0.4,
			'colors' => array('#10967e'),
			'hAxis' => array(
				'title' => \IPS\Member::loggedIn()->language()->get('s_chart_players_sub_title'),
				'textPosition' => 'none',
				'textStyle' => array('color' => '#ffffff'),
				'gridlines' => array('color' => '#f5f5f5', 'count' => 3600)
			),
			'vAxis' => array(
				'minValue' => 0,
				'maxValue' => 31,
				'viewWindow' => array('min' => 0)
			)
		));

		return \IPS\Theme::i()->getTemplate('charts')->players($players);
	}
}