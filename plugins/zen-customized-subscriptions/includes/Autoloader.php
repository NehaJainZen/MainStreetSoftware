<?php
defined( 'ABSPATH' ) || exit;
/*
 * Auto load all class files required for plugin.
 * Last updated by : Mark Lopez
 * Last updated on : 29-09-2022
 */
class ZCS_Autoloader {

	private $dir_separator  = '/';

	public static function init() {

		$arr_files = glob(dirname(__FILE__) . "/class-*.php");
		$arr_files = array_merge($arr_files, glob(dirname(dirname(__FILE__)) . "/helper/*.php"));

		foreach ($arr_files as $key => $value) {
			
			if(file_exists($value) && is_readable($value)) {

				require_once $value;
			} else {
				return false;
			}
		}
		return true;
	}
}