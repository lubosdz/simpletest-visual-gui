<?php
/**
* $Id$
* This is bootstrap file that routes requests to Visual Simplete Gui.
* It is not real controller, only a fake one to demonstrate how it should be correctly implemented.
*/

class TestController
{

	/**
	* This is fake controller class for routing simpletest HTTP request
	*/
	public static function actionIndex(){

		$root = dirname(__FILE__);

		// define directories:
		// directroy with all tests for all environments
		define('DIR_TESTS', $root.'/tests/');
		// temporary writable directroy
		define('DIR_TEMP', $root.'/temp/');
		// writable directory for loggin
		define('DIR_LOG', $root.'/log/');
		// root directory with simplement and extensions
		define('DIR_FRAMEWORK', $root.'/gui/vendor/simpletest/');
		// the name of the template to be used for rendering HTML output
		define('USE_TEMPLATE', 'simpleTemplate.php');

		// initiate GUI
		require( $root. '/gui/TestGui.php' );

		// download snapshot file from previous test?
		$snapshot = isset($_GET['snapshot']) ? $_GET['snapshot'] : false;

		if(!empty($snapshot)){
			// download snapshot
			TestUtils::downloadSnapshot($snapshot);
		}else{
			// render SimpleTest GUI
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
