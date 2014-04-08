<?php
/**
* $Id$
* This is a simple demo test to demonstrate how to use Selenium HQ server with PHP bindings.
*
* See also:
* http://code.google.com/p/selenium/wiki/JsonWireProtocol
*
* In order to run selenium server (on windows):
* 1. install java and make sure it is executable via command line. E.g. type "java -version" to see if it is system-wide accessible.
* 2. download standalone selenium server from http://docs.seleniumhq.org/download/
* 3. create start.bat file with following content:
		echo Starting Selenium HQ standalone server 2.41.0
		java -jar d:\_projects\selenium\selenium-server-standalone-2.41.0.jar -log ./log/selenium.log -trustAllSSLCertificates
  4. write unit tests for php web bindings ...
*/
class TestSeleniumBrowser extends SeleniumTestCase{

	public function setUp(){
		// delete temporary files older than 1 hour
		// TestUtils::flushTempDir(3600);
	}

	public function tearDown(){
		// nothing to do
	}

	/**
	* Loads google page and searches for some keyword
	*/
	public function testFetchGoogleResults(){

		if($this->assertTrue($this->connect(), 'Failed connecting to Selenium web driver.')){

			$url = 'http://www.google.com/';
			$this->driver->get($url);
			$title = $this->driver->getTitle();

			if($this->assertTrue(false !== stripos($title, 'Google'), 'Failed loading page from ['.$url.']')){

				$element = $this->driver->findElementBy(LocatorStrategy::name, "q");
				$element->sendKeys("php webdriver bindings");
				$element->submit();

				$image = $this->driver->getScreenshot();
				$image = base64_decode($image);
				TestUtils::snapshot($image, 'google-screenshot-1', false, 'google-screenshot-1.png');
			}

			// you may want to place it under tearDown()
			$this->driver->closeWindow();

		}

	}

}
