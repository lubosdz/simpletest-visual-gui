<?php
/**
* This is main initiation file for unit tests.
* It prepares environment, runs tests and lists available unit tests.
* $Id$
*/

// set alias for DIRECTORY_SEPARATOR
defined("DS") or define('DS', DIRECTORY_SEPARATOR);

// define some unique number for prepending into saved filenames from HTTP client (easier for orientation)
defined('SEED') or define("SEED", time());

// load utilities from unit tests
require_once( dirname(__FILE__) .DS. 'TestUtils.php');

defined('TIMESTART') or define("TIMESTART", TestUtils::utime());

// charset for converting via utility function TestUtils::convert()
defined('CHARSET_WEB') or define ('CHARSET_WEB', 'utf-8');

// charset in which selenium returns response
defined('CHARSET_SERVER') or define ('CHARSET_SERVER', 'utf-8');

/**
* Main tests runner class for unit tests
*/
class TestGui{

	/**
	* Log filename
	*/
	const LOGNAME = 'unittest';

	protected static
		/**
		*  directory only from which tests will be loaded - production, testing, development
		*/
		$directoryTests = '',

		/**
		* @var TestGui singleton
		*/
		$instance,

		/**
		* Output string listing available tests
		*/
		$output_list_tests = '',

		/**
		* Output buffer for capturing test results
		*/
		$output_result_tests = '',

		/**
		* Available unit tests
		*/
		$tests = array();

	/**
	* Constructor for Unit Tests Wrapper, protected to avoid instantiation.
	* We must have defined 3 paths for running unit tests:
	* - DIR_TEMP => writable temporary directory
	* - DIR_TESTS => directory with unit tests. Unit test can be put into as many as needed subdirectories. If empty, assuming that test are stored in subdirectories within directory of this file.
	* - DIR_FRAMEWORK => directory with deployed unit test framework pointing e.g. DIR_FRAMEWORK/simpletest/..
	*/
	protected function __construct(){

		defined('DIR_TESTS') or define('DIR_TESTS', dirname(__FILE__).DS);
		if(!is_dir(DIR_TESTS)){
			exit('MISSING OR INVALID PARAMETER: [DIR_TESTS='.DIR_TESTS.']. Please define path to directory with unit tests.');
		}

		if(!self::isEnabled()){
			exit('Unit tests are disabled.');
		}

		if(!defined('DIR_FRAMEWORK') || !is_dir(DIR_FRAMEWORK)){
			exit('MISSING OR INVALID PARAMETER: [DIR_FRAMEWORK]. Please define path to unit tests framework.');
		}

		if(!defined('DIR_TEMP') || !is_dir(DIR_TEMP)){
			exit('MISSING OR INVALID PARAMETER: [DIR_TEMP]. Please define path to temporary directory.');
		}

		if(!is_writable(DIR_TEMP)){
			exit('CAUTION - temporary directory ['.DIR_TEMP.'] is not writable! Adjust writing permissions and try again...');
		}

		if(!defined('DIR_LOG') || !is_dir(DIR_LOG)){
			exit('MISSING OR INVALID PARAMETER: [DIR_LOG]. Please define path to writable logging directory.');
		}

		self::loadIncludes();
	}

	/**
	* Return TRUE if unit testing is enabled for current environment.
	* For example you may want to disable executing unit testing on production servers.
	* @return bool False, if executing unit tests is not allowed. Set the directory from which unit tests will be loaded.
	*/
	public static function isEnabled(){
		// load application configuration - uncomment or adjust your needs
		//$conf = App::params('unittest');
		$conf = array('enabled' => 1, 'directory' => 'development');
		if(empty($conf['enabled'])){
			// tests must be explicitly enabled
			return false;
		}
		// each environment has own set of unit tests with different fixtures/data
		if(empty($conf['directory'])){
			// missing directory name for current environment, usually should be something like [development|testing|production]
			throw new CHttpException(500, 'Missing or invalid configuration parameter "unittest[directory]".');
		}
		// load only tests for current environment, e.g. development
		self::$directoryTests = DIR_TESTS . $conf['directory'] . DS;
		if(!is_dir(self::$directoryTests)){
			exit('Invalid configuration - directory for loading unit tests ['.self::$directoryTests.'] does not exist.');
		}
		// OK - tests enabled and directory with tests exists
		return true;
	}

	/**
	* Return instance of the class
	* @return TestGui
	*/
	public static function getInstance(){
		if(empty(self::$instance)){
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	* Include all files necessary to run tests (Simpletest environment)
	*/
	public static function loadIncludes(){
		require_once( DIR_FRAMEWORK . 'web_tester.php');
		require_once( DIR_FRAMEWORK . 'unit_tester.php');
	}

	/**
	* Return generated HTML output (results of executed tests and listing of available tests)
	*/
	public static function getOutput(){
		return '<div id="tests">'.
			TestUtils::getOutputHeader()
			. TestUtils::getOutputTitle()
			. '<div class="span3 pull-left alert alert-info">'.self::$output_list_tests.'</div>'
			. '<div class="span9 pull-right">'.self::$output_result_tests.'</div>'
			. TestUtils::getOutputFooter()
			.'</div>';
	}

	/**
	* Return currently used database adapter name/alias
	*/
	public static function getDatabaseName(){
		/**
		// example - Yii framework
		$db = Yii::app()->db;
		return empty($db)? 'n/a' : $db->connectionString;
		*/
		return '*NO DB*';
	}

	/**
	* Return the name of current user
	*/
	public static function getUserName(){
		/*
		// Example - Yii framework
		return Yii::app()->user->id;
		*/
		/*
		// Example Zend framework
		$identity = Zend_Auth::getInstance()->getIdentity();
		return $identity->getUserName();
		*/
		return 'Admin';
	}

	/**
	* Return application name
	*/
	public static function getAppName(){
		return 'My Test Gui';
		// return Yii::app()->name;
	}

	/**
	* Generate HTML list of available unit tests
	* @return TestGui
	*/
	public function listTests(){
		$html = '';
		$tests = self::getAvailableTests();
		if(count($tests)){
			$title = '<input id="toggelAllUnittests" type="checkbox"/> '
					.'<label for="toggelAllUnittests" title="Toggle all tests" style="display:inline-block;padding-top:0px;">  TESTS - '.self::getAppName().'</label><br/>'
					.'<label>@ <strong>'.$_SERVER['SERVER_NAME'].' / '.basename(self::$directoryTests).'</strong></label>';
			$subTitle = 'DB: '.self::getDatabaseName();

			$html .= '<div align="center">'.$title.' </div>'
					.'<div align="center" class="color-red">'.$subTitle.'</div>'
					.'<hr />';
			$html .= '<form action="" method="POST">';
			$html .= '<div align="center" class="margin-bottom-10"><input type="submit" name="run_tests_btn" value=" RUN SELECTED TESTS " class="btn-u btn-u-orange" /></div>';

			$cnt = 1;
			foreach($tests as $className => $methods){
				// $group = '<div style="padding-left:40px;"> &bull; '.implode("\n<br /> &bull; ", array_keys($methods)).'</div>';

				$all_checked = true;
				$group = '<div style="padding-left:10px;">';
				foreach($methods as $k=>$v) {
					$checked = '';
					if (isset($_REQUEST['runtest'][$className][$k])) {
						$checked = 'checked="checked"';
					} else {
						$all_checked = false;
					}
					$group .= '<label><input type="checkbox" name="runtest['.$className.']['.$k.']" id="runtest_'.$className.'_'.$k.'" value="1" '.$checked.' /> '.$k.'</label>';
				}
				$group .= '</div>';

				$checked = $all_checked ? 'checked="checked"' : '';
				$html .= '<div>'
							.'<label>'
								.'<input class="toggleChildren" type="checkbox" name="runtest['.$className.']" id="runtest_'.$className.'" value="1" '.$checked.' />'
								.'<strong> ['.$cnt.'] '. $className .'</strong>'
							.'</label>'
							. $group
						.'</div>';
				++$cnt;
			}
			$html .= '<br /><div align="center"><input type="submit" name="run_tests_btn" value=" RUN SELECTED TESTS " class="btn-u btn-u-orange" /></div>';
			$html .= '</form>';
		}else{
			$html .= 'NO UNIT TESTS FOUND IN ['.DIR_TESTS.']';
		}
		$html .= '<div class="tests-separator"></div>';
				//.'<div> Unit tests will check partial functionalities of the application. Since they may generate arbitraty data into database, it is recommended to <strong>connect to dedicated testing database</strong>. Database connection can be set in configuration file in ../config/main-*.php.</div>';
		self::$output_list_tests .= $html;
		return self::$instance;
	}

	/**
	* Sets array of available (implemented) unit tests
	* It will include sequencally all files from directory DIR_TESTS and collect all newly registered classes.
	* !! This approach will not work if a file has been included previously - it will not recognize its class as newly registered.
	*/
	protected static function getAvailableTests(){
		if(!is_array(self::$tests) || !count(self::$tests)){
			$files = TestUtils::findFilesRecursive(self::$directoryTests, array('addDirs' => false, 'fileTypes' => array('php')));
			$tests = array();

			$dc_one = get_declared_classes();
			foreach($files as $file){
				require_once($file);
			}
			$dc_two = get_declared_classes();
			$testClasses = array_diff($dc_two, $dc_one); // collect class from newly included file

			foreach($testClasses as $testClass){
				$reflection = new ReflectionClass($testClass); // available from PHP5+
				$methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
				$file = $reflection->getFileName();

				foreach($methods as $method){
					// each test method must begin with "test" prefix
					if(preg_match('/^test/i', $method->name)){
						$tests[$testClass][$method->name] = array(
							'file' => $file,
							'class' => $testClass,
							'method' => $method->name,
						);
					}
				}
			}
			ksort($tests);
			self::$tests = $tests;
		}
		return self::$tests;
	}

	/**
	* Run unit tests selected via checkbox
	* @return TestGui
	*/
	public function runSelectedTests(){
		self::getAvailableTests(); // just to include all files

		if (array_key_exists('runtest', $_REQUEST)) {

			// logging
			require_once( dirname(__FILE__) .DS. 'SimpleHtmlReporter.php');

			$out = "\n".str_repeat('=',50)."\nStarting tests, executed by user [".self::getUserName()."]. Deleted [".TestUtils::flushTempDir()."] files from temp dir [".DIR_TEMP."]\n".str_repeat('=',50);
			TestUtils::log($out, self::LOGNAME, TestUtils::LEVEL_INFO);

			$selected_tests = array();

			foreach($_REQUEST['runtest'] as $test_case=>$methods) {
				if(is_array($methods)){
					$selected_tests[$test_case] = array_keys($methods);
				}else{
					echo '<div class="alert alert-error"><button data-dismiss="alert" class="close" type="button">×</button>Caution - TestCase "'.$test_case.'" has no selected unit test.</div>';
				}
			}

			$reporter = new SimpleHtmlReporter();
			$reporter->setTests($selected_tests);

			$test_suite = new TestSuite();
			foreach($selected_tests as $class=>$methods) {
				/**
				* @var WebTestCase
				*/
				$test_case = new $class();

				// uprav nastavenia podla typu prostredia
				$modifier_class = $class.'Set';
				if(class_exists($modifier_class, false)){
					// pozri priklad v testing/database.php
					$test_case = call_user_func(array($modifier_class, 'set'), $test_case);
				}

				$test_suite->add($test_case);
			}

			$test_suite->run($reporter);
			self::$output_result_tests = $reporter->getOutput();

			// logging
			foreach($reporter->getStatus() as $name=>$test_case) {
				$log = '['.$test_case['passed'].'] passed, ['.$test_case['failed'].'] failed for ['.$name.']. Executed methods: '.implode(', ', $test_case['methods']);
				if (sizeof($test_case['messages']) > 0) {
					$log .= "\n" . implode("\n", $test_case['messages']);
				}
				TestUtils::log(strip_tags($log), self::LOGNAME, TestUtils::LEVEL_INFO);
			}
			$timeMemory = " -- Execution time [".TestUtils::execTime().'], memory usage ['.TestUtils::getMemoryUsage().']';
			TestUtils::log($timeMemory, self::LOGNAME, TestUtils::LEVEL_INFO);
		}

		return self::$instance;
	}

}