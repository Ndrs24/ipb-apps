<?php
namespace IPS\top15\modules\front\main;

if(!\defined('\IPS\SUITE_UNIQUE_KEY')) {
	header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0').' 403 Forbidden');
	exit;
}

class _main extends \IPS\Dispatcher\Controller {
	public function execute() {
		$url = \IPS\Http\Url::internal('app=top15&module=main&controller=main', 'front', 'top15');

		\IPS\Output::i()->breadcrumb['module'] = array($url, 'Top 15');
		\IPS\Output::i()->sidebar['enabled'] = FALSE;

		parent::execute();
	}

	protected function manage() {
		\IPS\Output::i()->error('No has especificado el servidor.', 'TOP15/0', 403, '');
	}
}