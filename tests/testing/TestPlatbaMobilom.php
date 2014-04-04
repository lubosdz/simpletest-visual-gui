<?php

// nacitame unit test pre development
$path = dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'development'.DIRECTORY_SEPARATOR.basename(__FILE__);
require($path);

/**
* Podporna classa pre modifikaciu testovacich parametrov
* ak existuje je automaticky volana z TestGui::runSelectedTests()
*/
/*
class databaseSet{
	public static function set(WebTestCase $testClass){
		return $testClass;
	}
}
*/
