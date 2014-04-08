## Simpletest Visual GUI, version 1.0.0, released 08.04.2014

This is simple customizable visual user interface for running unit tests written for simpletest unit testing framework.
It provides simple friendly listing of available unit tests and displaying test results.

## Installation

Unzip into your application apropriate place, e.g. directory "extensions" or "vendor".
If using MVC framework, create dedicated controller e.g. "TestController" within administration module/section to ensure only authorized access.
You can actually move content from file "index.php" into "testController".
By accessing "index.php" or "TestControler" you should be able to load listing of available tests.

Once unzipped, following directories will be available:
	* /gui/  .. main application directory, that also includes "/vendor/" subdirectory with simpletest and php web bindings.
	* /log/  .. output directory for logging, must be writable
	* /temp/  .. output directory for temporary files created during tests as well as snapshots and/or screenshots. Must be writable.
	* /tests/  .. all unit tests divided into subdirectories depending on environment type (development, testing, production)
	* index.php  .. bootstrap script that may be moved into MVC controller.
	* jquery.min.js  .. just auxiliary JS library, remove loading link from /gui/template1.php if your application already load jquery.

## Writing unit tests

There are assumed 3 development environments:
* development - these tests are assumed to rung ONLY during development stage
* testing - these tests are assumed to rung ONLY during testing stage
* production - these tests are assumed to rung ONLY on production servers

Please check out existing unit test examples under directory "/tests/".
There are also included web browser sample test for built-in SimpleBrowser as well as for Selenium Server with PHP bindings.

## Customization

Please note, that the purpose of this little utility is to provide easy customizable inteface for you - it is not intended to be perfect,
but practical and easy customizable. You may want to modify:

	* output rendering template (by default it is set to "/gui/template1.php")
	* directories deployment - please refer to "index.php" where are defined few constants:
		DIR_TESTS .. absolute path to directory root with all tests
		DIR_TEMP .. absolute path to temporary directory
		DIR_LOG .. absolute path to directory for logging
		DIR_FRAMEWORK .. absolute path to directory with simpletest framework and PHP web bindings.

## Reporting bugs

Please report bugs to lubosdz AT hotmail DOT com.

## Enjoy!
