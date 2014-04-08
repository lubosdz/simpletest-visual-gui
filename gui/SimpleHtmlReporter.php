<?php
/**
* $Id$
* Renders HTML output with test results
*/
class SimpleHtmlReporter extends SimpleReporter {
	/**
	* @var array Associative array with keys being names of test cases and values being arrays with method names
	*/
	private $tests = array();

	/**
	* @var array Results array
	*/
	private $results = array();

	/**
	* @var float Microtime before running Test method
	*/
	private $start_time;

	/**
	* @var integer Memory usage before running Test method
	*/
	private $start_memory;

	/**
	* @var array Method status array - messages for different statuses
	*/
	private $method_status = array(
		'passes' => array(),
		'fails' => array(),
		'exceptions' => array(),
		'errors' => array()
	);

	/**
	* constructor
	*/
	public function __construct() {
		parent::__construct();
	}

	/**
	* Sets which test cases and methods shoud be run
	* @param array List of test cases and their methods
	*/
	public function setTests(array $tests) {
		$this->tests = $tests;
	}

	/**
	* Check if called method should be invoked (is in methods list) and then proceed with parents implementation
	* @param string test case
	* @param string method
	* @return boolean TRUE if method should be invoked
	*/
	public function shouldInvoke($test_case, $method) {
		if (count($this->tests) > 0) {
			if (!array_key_exists($test_case, $this->tests)) {
				return false;
			}
			if (count($this->tests[$test_case]) > 0 && !in_array($method, $this->tests[$test_case])) {
				return false;
			}
		}
		return parent::shouldInvoke($test_case, $method);
	}

	/**
	* Returns output
	* @return string Output
	*/
	public function getOutput() {
		return $this->generateOutput();
	}

	/**
	* Generate output from results
	* @return string Output
	*/
	private function generateOutput() {
		$out = '';
		$out .= $this->getCss();

		$test_count = 0;
		foreach($this->results as $test_case) {
			$test_count += count($test_case['methods']);
		}

		$totals = array(
			'passes' => 0,
			'fails' => 0,
			'exceptions' => 0,
			'errors' => 0,
			'time' => 0,
			'memory' => 0,
		);

		foreach($this->results as $test_case) {
			$out .= sprintf('<h2>%s</h2>', $test_case['name']);
			foreach($test_case['methods'] as $name=>$method) {
				$passed = (sizeof($method['fails']) + sizeof($method['exceptions']) + sizeof($method['errors']) == 0 ? true : false);
				$out .= sprintf('<div class="%s">', ($passed ? 'passed' : 'failed'));
				$out .= sprintf(
					'<table class="table-test-result"><tr><th>%s</th><td>Time: %.3f s</td><td>Mem: %.3f MB</td><td>Passes: %d</td><td>Fails: %d</td><td>Exceptions: %d</td><td>Errors: %d</td></tr></table>',
					$name, $method['time'], ($method['memory'] / 1024 / 1024), sizeof($method['passes']), sizeof($method['fails']), sizeof($method['exceptions']), sizeof($method['errors'])
				);
				if (strlen($method['buffer']) > 0) {
					$out .= sprintf('<div class="%s">%s</div>', ($test_count == 1 ? 'buffer' : 'buffer buffer-scroll'),  $method['buffer']);
				}
				$out .= "</div>";
				$totals['passes'] += sizeof($method['passes']);
				$totals['fails'] += sizeof($method['fails']);
				$totals['exceptions'] += sizeof($method['exceptions']);
				$totals['errors'] += sizeof($method['errors']);
				$totals['time'] += $method['time'];
				$totals['memory'] += $method['memory'];
			}
		}

		if ($test_count > 1) {
			$count = sprintf("%d test %s, %d methods", sizeof($this->results), sizeof($this->results) == 1 ? 'case' : 'cases', $test_count);
			$out .= sprintf('<h2>Summary</h2>');
			$out .= sprintf('<div class="summary">');
			$out .= sprintf(
				'<table><tr><th>%s</th><td>Time: %.3f s</td><td>Mem: %.3f MB</td><td>Passes: %d</td><td>Fails: %d</td><td>Exceptions: %d</td><td>Errors: %d</td></tr></table>',
				$count, $totals['time'], ($totals['memory'] / 1024 / 1024), $totals['passes'], $totals['fails'], $totals['exceptions'], $totals['errors']
			);
			$out .= sprintf('</div>');
		}

		return $out;
	}

	private function getCss() {
return '<style type="text/css">
h2 { margin-bottom: 7px; }
.failed, .passed, .summary { margin-bottom: 7px; }
.failed table  { background: red;     color: white; border: 4px solid red; }
.passed table  { background: green;   color: white; border: 4px solid green; }
.summary table { background: gray; color: white; border: 4px solid gray; }
.buffer { font-size: 0.9em; padding: 5px; }
.buffer-scroll { overflow: auto; max-height: 200px; }
.failed .buffer { background: #ffdddd; }
.passed .buffer { background: #ddffdd; }
th { padding: 0 5px; text-align: left; font-size: 1.2em; font-weight: normal; }
td { padding: 0 5px; border-left: 1px solid white; width: 80px; font-size: 1em; }
td:nth-child(2) { width: 100px; }
td:nth-child(3) { width: 120px; }
td:nth-child(6) { width: 100px; }
</style>
';
	}

	/**
	* return status for each test case
	* @return array
	*/
	public function getStatus() {
		$a = array();
		foreach($this->results as $test_case) {
			$m = array();
			$total = 0;
			$passed = 0;
			foreach ($test_case['methods'] as $method) {
				$total++;
				if ($this->isPassed($method)) {
					++$passed;
				} else {
					$m = array_merge($method['fails'], $method['exceptions'], $method['errors']);
				}
			}

			$a[$test_case['name']] = array(
				'methods'=> array_keys($test_case['methods']),
				'messages' => $m,
				'passed' => $passed,
				'failed' => ($total - $passed)
			);
		}
		return $a;
	}

	/**
	* return true if method doesnt have fails/exceptions/errors
	* @param array $method
	* @return boolean TRUE if wihout errors
	*/
	private function isPassed($method) {
		return sizeof($method['fails']) + sizeof($method['exceptions']) + sizeof($method['errors']) == 0 ? true : false;
	}


	/* OUTPUT FUNCTIONS */

	public function paintCaseStart($test_case) {
		parent::paintCaseStart($test_case);
		$this->results[] = array('name' => $test_case, 'methods' => array());
	}

	public function paintMethodStart($method) {
		parent::paintMethodStart($method);

		/* this one is PHP 5.2+
		$this->method_status = array_fill_keys(array_keys($this->method_status), 0); // reset method status
		*/
		$this->method_status = array_combine(array_keys($this->method_status), array_fill(0, sizeof($this->method_status), array())); // reset method status

		ob_start();
		$this->start_time = microtime(true);
		$this->start_memory = memory_get_usage();
	}

	public function paintMethodEnd($method) {
		$elapsed = microtime(true) - $this->start_time;
		$memory = memory_get_usage() - $this->start_memory;
		$buffer = ob_get_clean();

		$test_case = array_pop($this->results);
		$a = $this->method_status;
		$a['buffer'] = $buffer;
		$a['time'] = $elapsed;
		$a['memory'] = $memory;
		$test_case['methods'][$method] = $a;
		array_push($this->results, $test_case);

		parent::paintMethodEnd($method);
	}

	public function paintException($exception) {
		parent::paintException($exception);
		$message = sprintf('Unexpected exception of type [%s] with message [%s] in [%s:%s]', get_class($exception), $exception->getMessage(), $exception->getFile(), $exception->getLine());
		$this->method_status['exceptions'][] = $message;
		printf("<div>".'<strong>Exception</strong>: %s</div>', $message);
	}

	public function paintError($message) {
		parent::paintError($message);
		$this->method_status['errors'][] = $message;
		printf("<div>".'<strong>Error</strong>: %s</div>', $message);
	}

	public function paintFail($message) {
		parent::paintFail($message);
		$this->method_status['fails'][] = $message;
		printf("<div>".'<strong>Fail</strong>: %s</div>', $message);
	}

	public function paintSkip($message) {
		parent::paintSkip($message);
		// not implemented
	}

	public function paintPass($message) {
		parent::paintPass($message);
		$this->method_status['passes'][] = $message;
	}

}
