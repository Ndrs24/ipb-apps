<?php
namespace IPS\servidores;

class _Stats extends \IPS\Patterns\ActiveRecord {
	public static $databaseTable = 'servidores_stats';
	public static $databaseColumnId = 'id';
	public static $databasePrefix = 'stat_';
}