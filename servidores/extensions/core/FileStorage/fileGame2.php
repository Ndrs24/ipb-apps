<?php
namespace IPS\servidores\extensions\core\FileStorage;

if(!\defined('\IPS\SUITE_UNIQUE_KEY')) {
	header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0').' 403 Forbidden');
	exit;
}

class _fileGame2 {
	public function count() {
		return \IPS\Db::i()->select("MAX(game_id)", "servidores_games", array("NULLIF(game_icon, '') IS NOT NULL OR NULLIF(game_icon, '') IS NOT NULL"))->first();
	}
	
	public function move($offset, $storageConfiguration, $oldConfiguration=NULL) {
		$db = \IPS\Db::i()->select("*", "servidores_games", "game_icon IS NOT NULL", "game_icon", array($offset, 1))->first();
		$game = \IPS\servidores\Games::constructFromData($db);
		
		try {
			$game->icon = \IPS\File::get($oldConfiguration ?: 'servidores_fileGame2', $game->icon)->move($storageConfiguration);
			$game->save();
		} catch(\Exception $e) {

		}
	}

	public function isValidFile($file) {
		try {
			\IPS\Db::i()->select("game_id", "servidores_games", array("game_icon=?", $file))->first();
			return true;
		} catch(\UnderflowException $e) {
			return false;
		}
	}

	public function delete() {
		foreach(\IPS\Db::i()->select("*", "servidores_games", "game_icon IS NOT NULL") as $game) {
			try {
				\IPS\File::get("servidores_fileGame2", $game["game_icon"])->delete();
			} catch(\Exception $e) {

			}
		}
	}
}