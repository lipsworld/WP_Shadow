WP Shadow
=========

This is a PHP class for WordPress that loads the site in the background and performs registered WP Cron tasks.

## Scenario ##
Let's say you have published a plugin that retrieves data from external servers. Since fetching data from external servers takes some time and is slow to process, you used WP Cron to renew the data in the background and it worked well.

A while later, a user came up and said, "It doesn't update the data on my server! Here is the login info. FIX IT PLEAESE!!" You investigated the problem and figured out that the problem lies in the fact that the host of the server that the user used had suspended `wp-cron.php`.

You told the user "Your host seems to have a restriction on WP Cron. So talk to your host." The user replied, "I have no idea what you are talking about. Please fix the problem or do you want me to write a bad review on your plugin? I'm going crazy!"

So here is the solution.

## Basic Usage ##

1. Register a task normally with WP Cron functions such as [wp_schedule_single_event()](http://codex.wordpress.org/Function_Reference/wp_schedule_single_event).
2. Register the action hook with the WP_Shadow class.
3. Call the background process with the `see()` static method.

To register an action hook(s) 
```php
new WP_Shadow( array( 'my_action_hook_a', 'my_action_hook_b' ) );
```

To call a background process.
```php
WP_Shadow::see();
```

## Demo ##
```php
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
	 * Assuming this is the task that should be performed in the background, this creates a log file in the script directory.
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
```