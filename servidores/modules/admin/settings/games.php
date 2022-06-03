<?php
namespace IPS\servidores\modules\admin\settings;

if(!\defined('\IPS\SUITE_UNIQUE_KEY')) {
	header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0').' 403 Forbidden');
	exit;
}

class _games extends \IPS\Node\Controller {
	public static $csrfProtected = false;

	protected $nodeClass = 'IPS\servidores\Games';

	public function execute() {
		\IPS\Dispatcher::i()->checkAcpPermission("games_manage");
		parent::execute();
	}
}