<?php
/**
* Selenium remote web driver
* $Id: SeleniumTestCase.php 143 2013-09-19 19:21:15Z admin $
*/
class SeleniumTestCase extends WebTestCase{

	/**
	* IP address of windows server with running Selenium server
	*/
	protected $host;

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
			throw new CHttpException(500, 'Missing at least one of required extensions [JSON, CURL]. Please install missing extension required by Selenium server.');
		}

	}

}



