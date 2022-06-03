<?php
namespace IPS\servidores\modules\admin\settings;

if(!\defined('\IPS\SUITE_UNIQUE_KEY')) {
	header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0').' 403 Forbidden');
	exit;
}

class _servers extends \IPS\Node\Controller {
	public static $csrfProtected = false;

	protected $nodeClass = '\IPS\servidores\Servers';
	
	public function execute() {
		\IPS\Dispatcher::i()->checkAcpPermission("servers_manage");

		\IPS\Output::i()->sidebar["actions"]["restore"] = array(
			'icon'	=> "refresh",
			'link'	=> \IPS\Http\Url::internal("app=servidores&module=settings&controller=servers&do=refresh"),
			'title'	=> "s_refresh"
		);

		parent::execute();
	}

	protected function form() {
		parent::form();

		if(\IPS\Request::i()->id) {
			\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack("__edit_server").": ".\IPS\Output::i()->title;
		} else {
			\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack("__add_server").": ".\IPS\Output::i()->title;
		}
	}

	protected function refresh() {
		try {
			$db = \IPS\Db::i()->select("*", "core_tasks", array("`app`=? AND `key`=?", "servidores", "queryServers"))->first();
			$task = \IPS\Task::constructFromData($db);
			
			if($task->running) {
				$task->unlock();
			}
			
			$output = $task->run();
			
			if($output !== NULL) {
				throw \Exception;
			}
		} catch(\Exception $e) {
			\IPS\Log::log($e, "uncaught_exception");
			return;
		}
		
		\IPS\Output::i()->redirect(\IPS\Http\Url::internal("app=servidores&module=settings&controller=servers"), "s_refreshed");
	}

	protected function resetStat() {
		try {
			$server = \IPS\servidores\Servers::load(\IPS\Request::i()->id);
			$server->resetServerStat();
		} catch(\Exception $e) {
			\IPS\Log::log($e, "uncaught_exception");
			return;
		}
		
		\IPS\Output::i()->redirect(\IPS\Http\Url::internal("app=servidores&module=settings&controller=servers"), "s_reseted_stats");
	}
}