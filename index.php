<?php
/**
* $Id$
* This is bootstrap file that routes requests to Visual Simplete Gui.
* It is not real controller, only a fake one to demonstrate how it should be correctly implemented.
*/

// handle special request for loading JQuery (checkboxes handling)
if(isset($_GET['jquery'])){
	// we avoid URL detection which is always a trouble on various setups
	// we also won't load from CDN since internet acess may not always be an option
	header('content-type: text/javascript');
	exit(file_get_contents(dirname(__FILE__).DIRECTORY_SEPARATOR.'jquery.min.js'));
}

class TestController
{
	/**
	* This is fake controller class for routing simpletest HTTP request
	*/
	public static function actionIndex(){

		$root = dirname(__FILE__);

		// define directories:
		// absolute path to directory root with all tests for all environments
		define('DIR_TESTS', $root.'/tests/');
		// absolute path to temporary writable directory
		define('DIR_TEMP', $root.'/temp/');
		// absolute path to writable directory for logs
		define('DIR_LOG', $root.'/log/');
		// root directory with simpletest and the extensions
		define('DIR_FRAMEWORK', $root.'/gui/vendor/simpletest/');
		// the name of the template to be used for rendering HTML output
		//define('USE_TEMPLATE', 'myTemplate.php');

		// initiate GUI
		require( $root. '/gui/TestGui.php' );

		// download snapshot file from previous test?
		$snapshot = isset($_GET['snapshot']) ? $_GET['snapshot'] : false;

		if(!empty($snapshot)){
			// yes, download snapshot
			TestUtils::downloadSnapshot($snapshot);
		}else{
			// execute tests and/or render SimpleTest GUI
			echo TestGui::getInstance()
				->runSelectedTests()
				->listTests()
				->getOutput();
		}

	}

}

// run Simpletest GUI
// It will scan all available tests and render HTML interface (GUI) with runable tests.
TestController::actionIndex();
