<?php
/**
* $Id$
* This is a simple demo test to demonstrate how to use built-in static HTTP client browser.
* See http://www.simpletest.org/en/browser_documentation.html
*/
class TestSimpleBrowser extends UnitTestCase{

	protected $page;

	public function setUp(){
		// delete temporary files older than 1 hour
		TestUtils::flushTempDir(3600);
	}

	public function tearDown(){
		// nothing to do
	}

	/**
	* Fetch page from google and search for some keyword.
	*/
	public function testFetchGoogleResults(){
		$browser = new SimpleBrowser();
		// inject debugging cookie, if we want to debug request - makes sense only if we do request to the same server with DBG module
		if(!empty($_COOKIE['DBGSESSID'])){
			$browser->setCookie('DBGSESSID', $_COOKIE['DBGSESSID']);
		}

		$url = 'http://www.google.com/';
		$html = $browser->get($url);

		// store fetched page into temporary file and display quick download link
		TestUtils::snapshot($html, 'google-main-page');

		if($this->assertTrue(false !== stripos($browser->getTitle(), 'google'), 'Failed loading page from '.$url.'!')){

			$keyword = 'simpletest';

			// load search results for "simpletest"
			$browser->setField('q', $keyword);
			$html = $browser->clickSubmitByName('btnG');

			if($this->assertTrue(false !== strpos($browser->getTitle(), $keyword), 'Failed loading search results for '.$keyword.'!')){
				TestUtils::snapshot($html, 'google-search-results');
			}
		}
	}

}
