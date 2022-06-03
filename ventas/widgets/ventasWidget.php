<?php
namespace IPS\ventas\widgets;

if(!\defined('\IPS\SUITE_UNIQUE_KEY')) {
	header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0').' 403 Forbidden');
	exit;
}

class _ventasWidget extends \IPS\Widget {
	public $key = 'ventasWidget';
	public $app = 'ventas';
	public $plugin = '';
	
	public function init() {
		$this->template(array(\IPS\Theme::i()->getTemplate('widgets', $this->app, 'front'), $this->key));
		parent::init();
	}
	
	public function render() {
		return $this->output();
	}
}