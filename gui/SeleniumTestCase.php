<?php
/**
* $Id$
* Selenium remote web driver
* See:
* http://code.google.com/p/php-webdriver-bindings/
* http://code.google.com/p/selenium/wiki/JsonWireProtocol
*/
class SeleniumTestCase extends WebTestCase{

	/**
	* IP address of server host with running Selenium server
	*/
	protected $host = '127.0.0.1';

	/**
	* Port on which is listening Selenium server, default 4444
	*/
	protected $port = 4444;

	/**
	* Session created on successfully started driver profile
	* See http://code.google.com/p/selenium/wiki/JsonWireProtocol
	* @var WebDriver
	*/
	protected $driver;

	public function __construct(){

		if(!extension_loaded('json') || !extension_loaded('curl')){
			throw new CHttpException(500, 'Missing at least one of required extensions [JSON, CURL]. Please install missing extension(s) and trye again.');
		}

	}

	/**
	* Attempt to connect to Selenium server
	* @return bool TRUE if web driver succesfully initiated
	*/
	protected function connect($browser = 'firefox', $host = '127.0.0.1', $port = 4444){

		$this->host = $host;
		$this->port = $port;

		// initiate web driver PHP bindings
		require_once DIR_FRAMEWORK . '/extensions/seleniumWebDriverBindings/phpwebdriver/WebDriver.php';

		// initatie browser
		$this->driver = new WebDriver($this->host, $this->port);
		$this->driver->connect($browser);

		return !empty($this->driver);
	}

}

