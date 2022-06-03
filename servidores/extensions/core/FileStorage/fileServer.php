<?php
namespace IPS\servidores\extensions\core\FileStorage;

if(!\defined('\IPS\SUITE_UNIQUE_KEY')) {
	header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0').' 403 Forbidden');
	exit;
}

class _fileServer {
	public function count() {
		return \IPS\Db::i()->select("MAX(server_id)", "servidores_servers", array("NULLIF(server_logo, '') IS NOT NULL OR NULLIF(server_logo, '') IS NOT NULL"))->first();
	}
	
	public function move($offset, $storageConfiguration, $oldConfiguration=NULL) {
		$db = \IPS\Db::i()->select("*", "servidores_servers", "server_logo IS NOT NULL", "server_id", array($offset, 1))->first();
		$server = \IPS\servidores\Servers::constructFromData($db);
		
		try {
			$server->logo = \IPS\File::get($oldConfiguration ?: "servidores_fileServer", $server->logo)->move($storageConfiguration);
			$server->save();
		} catch(\Exception $e) {

		}
	}

	public function isValidFile($file) {
		try {
			\IPS\Db::i()->select("server_id", "servidores_servers", array("server_logo=?", $file))->first();
			return true;
		} catch(\UnderflowException $e) {
			return false;
		}
	}

	public function delete() {
		foreach(\IPS\Db::i()->select("*", "servidores_servers", "server_logo IS NOT NULL") as $server) {
			try {
				\IPS\File::get("servidores_fileServer", $server["server_logo"])->delete();
			} catch(\Exception $e) {

			}
		}
	}
}