<?php
/* 
 * Plugin Name: WP Shadow Demo
 * Description: An alternative to WP Cron.
 * Author: Michael Uno
 * Version: 1.0.0
 */


if ( ! defined( 'ABSPATH' ) ) exit;	// Exit if accessed directly

// Load the class
include_once( dirname( __FILE__ ) . '/class/WP_Shadow.php' );	

new WP_Shadow_Demo;

class WP_Shadow_Demo {
	
	public function __construct() {
		
		if ( isset( $_GET['doing_wp_cron' ] ) ) {
			return;	// say WP Cron is disabled.
		}
			
		// Assume the doTask() method is the one that you need to run in the background.
		add_action( 'do_wp_shadow_demo', array( $this, 'doTask' ) );
		
		$this->scheduleCronTask();
		
		new WP_Shadow( 'do_wp_shadow_demo' );
		
	}
	
	
	private function scheduleCronTask() {

		$aArgs = array( 'a', 'b', 'c' );
	
		if ( wp_next_scheduled( 'do_wp_shadow_demo', array( $aArgs ) ) ) return; 
		wp_schedule_single_event( 
			time(), 	// passing the current time means to do it as soon as possible but WP Cron requires another page load to perform that.
			'do_wp_shadow_demo', 	// the action hook name
			array( $aArgs )	// the data to be passed 
		);				
		WP_Shadow::see();
		
	}
	
	/**
	 * Simulates a task which should be performed in the background.
	 * 
	 */
	public function doTask( $aArgs ) {
		
		static $_iPageLoadID;
		$_iPageLoadID = $_iPageLoadID ? $_iPageLoadID : uniqid();		
		
		$oCallerInfo = debug_backtrace();
		$sCallerFunction = $oCallerInfo[ 1 ]['function'];
		$sCallerClasss = $oCallerInfo[ 1 ]['class'];
		file_put_contents( 
			$sFilePath ? $sFilePath : dirname( __FILE__ ) . '/log.txt', 
			date( "Y/m/d H:i:s", current_time( 'timestamp' ) ) . ' ' . "{$_iPageLoadID} {$sCallerClasss}::{$sCallerFunction} " . $this->_getCurrentURL() . PHP_EOL
			. print_r( $aArgs, true ) . PHP_EOL . PHP_EOL,
			FILE_APPEND 
		);		
		
	}
	
		/**
		 * Retrieves the currently loaded page url.
		 */
		protected function _getCurrentURL() {
			$sSSL = ( !empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' ) ? true:false;
			$sServerProtocol = strtolower( $_SERVER['SERVER_PROTOCOL'] );
			$sProtocol = substr( $sServerProtocol, 0, strpos( $sServerProtocol, '/' ) ) . ( ( $sSSL ) ? 's' : '' );
			$sPort = $_SERVER['SERVER_PORT'];
			$sPort = ( ( !$sSSL && $sPort=='80' ) || ( $sSSL && $sPort=='443' ) ) ? '' : ':' . $sPort;
			$sHost = isset( $_SERVER['HTTP_X_FORWARDED_HOST'] ) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
			return $sProtocol . '://' . $sHost . $sPort . $_SERVER['REQUEST_URI'];
		}

	
}