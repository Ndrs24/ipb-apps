<?php
namespace IPS\ventas\extensions\core\FrontNavigation;

if(!\defined('\IPS\SUITE_UNIQUE_KEY')) {
	header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0').' 403 Forbidden');
	exit;
}

class _itemBarRegistroZPA extends \IPS\core\FrontNavigation\FrontNavigationAbstract {
	public static function typeTitle() {
		return \IPS\Member::loggedIn()->language()->addToStack('module__ventas_register_zpa');
	}
	
	public static function isEnabled() {
		return TRUE;
	}
	
	public function canAccessContent() {
		return \IPS\Member::loggedIn()->canAccessModule(\IPS\Application\Module::get('ventas', 'register'));
	}
	
	public function title() {
		return \IPS\Member::loggedIn()->language()->addToStack('module__ventas_register_zpa');
	}
	
	public function link() {
		return \IPS\Http\Url::internal("app=ventas&module=register&controller=zpa", 'front', 'register_zpa');
	}
	
	public function active() {
		return \IPS\Dispatcher::i()->application->directory === 'ventas' and \IPS\Dispatcher::i()->module->key === 'register' and \IPS\Dispatcher::i()->controller === 'zpa';
	}
}