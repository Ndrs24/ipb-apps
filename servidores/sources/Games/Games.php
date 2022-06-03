<?php
namespace IPS\servidores;

class _Games extends \IPS\Node\Model {
	protected static $multitons;
	protected static $defaultValues = NULL;

	public static $databaseTable = 'servidores_games';
	public static $databaseColumnOrder = 'position';
	public static $databaseColumnId = 'id';
	public static $databasePrefix = 'game_';
	public static $nodeTitle = 'module__servidores_games';
	public static $titleLangPrefix = 's_games_';
	public static $modalForms = true;

	public function form(&$form) {
		if($this->position) {
			$form->add(new \IPS\Helpers\Form\Number("game_position", $this->position, true, array("min" => 1)));
		} else {
			$form->add(new \IPS\Helpers\Form\Number("game_position", 1, true, array("min" => 1)));
		}

		if($this->id) {
			$form->add(new \IPS\Helpers\Form\Translatable("game_shortname", \IPS\Member::loggedIn()->language()->get("s_games_{$this->id}"), true, array("app" => "servidores", "key" => "s_games_{$this->id}")));
		} else {
			$form->add(new \IPS\Helpers\Form\Translatable("game_shortname", NULL, true, array("app" => "servidores", "key" => NULL)));
		}

		$game_types_file = \IPS\ROOT_PATH."/applications/servidores/data/games.json";
		$game_types = $this->getJson($game_types_file);

		if($this->type) {
			$form->add(new \IPS\Helpers\Form\Select("game_type", "{$this->protocol},{$this->type}", true, array("options" => $game_types)));
		} else {
			$form->add(new \IPS\Helpers\Form\Select("game_type", NULL, true, array("options" => $game_types)));
		}

		if($this->icon) {
			$form->add(new \IPS\Helpers\Form\Upload("game_icon", \IPS\File::get("servidores_fileGame2", $this->icon), false, array("storageExtension" => "servidores_fileGame2", "allowedFileTypes" => array("png"))));
		} else {
			$form->add(new \IPS\Helpers\Form\Upload("game_icon", NULL, false, array("storageExtension" => "servidores_fileGame2", "allowedFileTypes" => array("png"))));
		}
	}

	public function formatFormValues($values) {
		if(!$this->id) {
			$this->save();
		}

		$type = explode(",", $values["game_type"]);
		$values["game_protocol"] = $type[0];
		$values["game_type"] = $type[1];

		if(isset($values["game_shortname"])) {
			\IPS\Lang::saveCustom("servidores", "s_games_{$this->id}", $values["game_shortname"]);
			unset($values["game_shortname"]);
		}

		$values["icon"] = $values["game_icon"];
		unset($values["game_icon"]);

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
	
	public static function allGames() {
		$translate = array();
		$result = \IPS\Db::i()->select("*", "servidores_games", NULL, "game_type");

		foreach($result as $row) {
			$cullomnkey = $row["game_id"];
			$translate[$cullomnkey] = \IPS\Member::loggedIn()->language()->get("s_games_{$row['game_id']}");
  		}

		return $translate;
	}

	protected function getJson($file) {
		if(!file_exists($file)) {
			$json = array();
		} else {
			$json = json_decode(file_get_contents($file), TRUE);
		}
		
		return $json;
	}
}