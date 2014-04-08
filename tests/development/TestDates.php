<?php
/**
* $Id$
* This is a simple demo test for Simple GUI interface
*/
class TestDates extends UnitTestCase{

	protected $now;

	public function setUp(){
		$this->now = time();
	}

	public function tearDown(){
		$this->now = null;
	}

	public function testToday(){
		if($this->assertTrue(date('d.m.Y') == date('d.m.Y', $this->now), 'Today\'s date does not match!')){
			// continue with other tests..
		}
	}

	public function testTommorrow(){
		if($this->assertTrue(date('d.m.Y') < date('d.m.Y', $this->now + 86400), 'The dates do not match!')){
			// continue with other tests..
		}
	}

	public function testYesterday(){
		$this->assertTrue(date('d.m.Y') > date('d.m.Y', $this->now - 86400));
	}

	public function testIWillFail(){
		$this->assertTrue(1==="1");
	}

}
