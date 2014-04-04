<?php
/**
* $Id: TestCronJobs.php 773 2014-03-22 12:26:24Z admin $
* unit tests for Cron jobs
*/

class TestCronJobs extends UnitTestCase{

	/**
	* Testuje vytvorenie platby - avizo o SMS - potvrdenie platby
	*/
	public function testRotateLogs(){
		// create some old log file to ensure at least 1 file is rotated
		$path = Helper::getPathOfAlias('log').'unittest-'.date('ynj-His').'.log';
		file_put_contents($path, 'This is unit test log file for test ['.__CLASS__.'] created on ['.date('d.m.Y H:i:s').']');
		touch($path, time()-86400*7); // 7 days ago

		// import admin components
		Yii::app()->getModule('admin');
		$rotated = Cron::execute()->taskRotateLogs();
		$this->assertTrue($rotated > 0, 'At least 1 file should have been rotated.');
	}

	/**
	* Odosle asynchronne testovaci email
	*/
	public function testSendEmail(){
		$params = array(
			'sendInstantly' => true,
			'subject' => '['.$_SERVER['SERVER_NAME'].'] Unit test [sendMail]',
			'to' => Yii::app()->params['adminEmail'],
			'body' => 'This is unit test ['.__CLASS__.'] executed at ['.date('d.m.Y H:i:s').'] by ['.Yii::app()->user->name.'] on ['.$_SERVER['SERVER_NAME'].'] by remote client ['.$_SERVER['REMOTE_ADDR'].'].'
		);
		// queue email, dont send
		$cntSuccess = Mailer::sendmail($params);
		$this->assertTrue($cntSuccess > 0);
		// send via crontask
		list($success, $failed) = Cron::execute()->taskSendEmailFromQueue();
		$this->assertTrue($failed == 0);
	}

	public function testCleanupTemp(){
		// create some old log file to ensure at least 1 file is rotated
		$path = Helper::getPathOfAlias('temp').'unittest-'.date('ynj-His').'.tmp';
		file_put_contents($path, 'This is unit test log file for test ['.__CLASS__.'] created on ['.date('d.m.Y H:i:s').']');
		touch($path, time()-(Cron::DELETE_TEMP_FILES_AFTER_DAYS*86400+1000));

		// import admin components
		Yii::app()->getModule('admin');
		$deletedCnt = Cron::execute()->taskCleanupTemp();
		$this->assertTrue($deletedCnt > 0, 'At least 1 file should have been deleted.');
	}

	public function testCleanupBin(){
		// create some old log file to ensure at least 1 file is rotated
		$stamp = time()-(Cron::DELETE_TEMP_FILES_AFTER_DAYS*86400)-1;
		/*
		// neda sa testnut directory, lebo nefunguje touch na dirs pod windows
		$path = Helper::getPathOfAlias('bin').'unittest-'.date('ynj-His').DS;
		IpdfHelper::mkdir($path);
		// dla dokumentacie by od 5.3+ malo fungovat touch aj na directories, ale zda sa, nefunguje
		touch($path, $stamp);
		*/
		$path = Helper::getPathOfAlias('bin');
		$path .= 'unittest-fake-file.php';
		file_put_contents($path, 'This is unit test log file for test ['.__CLASS__.'] created on ['.date('d.m.Y H:i:s').']');
		touch($path, $stamp);
		clearstatcache(true);

		// import admin components
		Yii::app()->getModule('admin');
		$deletedCnt = Cron::execute()->taskCleanupBin();
		$this->assertTrue($deletedCnt > 0, 'At least 1 file should have been deleted.');
	}

	/**
	* Spusti hlavnu funkciu, ktoru vykonava aj CRON daemon
	*/
	public function testScheduledTasks(){
		$result = Cron::execute()->scheduledTasks();
		$this->assertTrue($result === true); // always if no errors

		// test monthdays
		$now = mktime(0,0,0,1,30,2014); // 00:00:00 30.01.2014 = THURS
		$lastCron = CronModel::model();
		$this->assertFalse(Cron::execute()->getNextExecTsTEST(array('monthday' => '14, 30'), null, $now)); // by default executes 01:10
		$this->assertTrue(Cron::execute()->getNextExecTsTEST(array('monthday' => '14, 30'), null, mktime(1,10,0,1,30,2014))); // by default executes 01:10
		$this->assertFalse(Cron::execute()->getNextExecTsTEST(array('monthday' => '13, 29'), null, $now));
		$this->assertFalse(Cron::execute()->getNextExecTsTEST(array('monthday' => '15, 31', 'hour' => 3, 'minute' => 15), null, $now));
		$this->assertTrue(Cron::execute()->getNextExecTsTEST(array('monthday' => '15, 30', 'hour' => 3, 'minute' => 15), null, mktime(3,15,10,1,30,2014)));

		// test weekdays
		$this->assertTrue(Cron::execute()->getNextExecTsTEST(array('weekday' => '3, 4'), null, mktime(1,30,0,1,30,2014))); // by default executes 01:30
		$this->assertTrue(Cron::execute()->getNextExecTsTEST(array('weekday' => '3, 4', 'hour' => '0, 8'), null, mktime(8,30,1,1,30,2014)));
		$this->assertFalse(Cron::execute()->getNextExecTsTEST(array('weekday' => '3, 4', 'hour' => '1', 'minute' => 10), null, $now));
		$this->assertFalse(Cron::execute()->getNextExecTsTEST(array('weekday' => '2, 5'), null, $now));
		$this->assertFalse(Cron::execute()->getNextExecTsTEST(array('weekday' => '2, 5', 'hour' => '0', 'minute' => 0), null, $now));

		// test hours
		$this->assertTrue(Cron::execute()->getNextExecTsTEST(array('hour' => '0'), null, $now));
		$this->assertFalse(Cron::execute()->getNextExecTsTEST(array('hour' => '1, 2'), null, $now));
		$this->assertTrue(Cron::execute()->getNextExecTsTEST(array('hour' => '0', 'minute' => '0'), null, $now));
		$this->assertTrue(Cron::execute()->getNextExecTsTEST(array('hour' => '0', 'minute' => '1'), null, $now));
		$lastCron->started_ts = $now-15;
		$this->assertFalse(Cron::execute()->getNextExecTsTEST(array('hour' => '0', 'minute' => '10'), $lastCron, $now)); // executed 15 secs ago
		$this->assertTrue(Cron::execute()->getNextExecTsTEST(array('hour' => '0,2,4,6', 'minute' => '0, 10, 20, 50'), null, $now));
		$this->assertFalse(Cron::execute()->getNextExecTsTEST(array('hour' => '0,2,4,6', 'minute' => '10, 20, 50'), null, $now));

		// bugfix - rotate logs sa ma vykonat o 00:15, ale spusti sa 00:01, 00:04, 00:08, 00:12
		$this->assertFalse(Cron::execute()->getNextExecTsTEST(array('hour' => '0', 'minute' => '15'), null, $now));

		// test minutes
		$this->assertTrue(Cron::execute()->getNextExecTsTEST(array('minute' => '0, 2, 4'), null, $now));
		$this->assertTrue(Cron::execute()->getNextExecTsTEST(array('minute' => '1, 3, 6'), null, $now));
		$lastCron->started_ts = $now-Cron::CRON_RUNS_EVERY_MINUTE*60;
		$this->assertFalse(Cron::execute()->getNextExecTsTEST(array('minute' => '1, 3, 6'), $lastCron, $now)); // task executed 1 second before expiring tolerated timespan
	}

	public function testDeleteEmptyStudioForms(){
		// create fake dummy studio formular(s)
		$formCnt = 2;
		$newIDs = $this->createFakeStudioForm($formCnt);
		if($this->assertTrue(count($newIDs) == $formCnt)){
			$deletedCnt = Cron::execute()->taskDeleteEmptyStudioForms();
			$this->assertTrue($deletedCnt >= $formCnt, 'Created ['.$formCnt.'] forms but deleted only ['.$deletedCnt.'].');
			foreach($newIDs as $id){
				$row = StudioModel::model()->findByPk($id);
				$this->assertTrue(empty($row), 'Empty formular not deleted.');
				$row = StudioLang::model()->findByAttributes(array('fk_studio_id' => $id));
				$this->assertTrue(empty($row), 'Language related record not deleted.');
			}
		}
	}

	public function testDeleteGuestFiles(){
		$days = 15;
		$sinceTs = mktime(0,0,0,date('n'), date('j'), date('Y'))-($days*86400);

		/**
		* @var ArchiveModel
		*/
		$archive = ArchiveModel::model()->find('id > :id', array(':id' => 0)); // any file
		if($this->assertTrue(!empty($archive), 'No file in archive.')){

			// reset some attributes
			$form = IpdfForm::getInstance();
			$data = $form->getFormsSortedById();
			$formId = array_rand($data);

			$archive->id = null;
			$archive->fk_user_id = 0;
			$archive->fk_form_id = $formId; // ensure we dont use any non-existing formular
			$archive->doc_title .= ' (unittest archive '.$sinceTs.')'; // ensure we dont use any non-existing formular
			$archive->created_ts = $sinceTs-1000;
			$archive->setIsNewRecord(true);

			if($this->assertTrue($archive->save(), 'Failed creating fake recored: '.$archive->getErrorsFormated())){
				$res = Cron::execute()->taskDeleteGuestFiles();
				$this->assertTrue($res > 0);
			}
		}
	}

	/**
	* Used to test email entered by a user - they are sometime invalid
	*/
	public function testIsEmailDomainValid(){
		$mails = array(
			// email => expected result "is valid"?
			'ipdf@ipdf.sk' => true,
			'ipdf@IPdf.sk' => true, // case sensitive is OK
			'ipdf@ipdfXinvalid.sk' => false,
			'invalid_ipdf@ipdf.sk' => true, // domain will be valid, we are not able to check email address
			'somebody@gmail.com' => true,
			'somebody@gmail.sk' => false,
			'somebody@gialm.com' => false,
		);
		foreach($mails as $mail => $expectedResult){
			$result = Mailer::isEmailDomainValid($mail);
			$this->assertTrue($result === $expectedResult, 'Error: ('.$mail.')');
		}
	}

	#################################################
	############   UTILS
	#################################################

	protected function createFakeStudioForm($cnt = 1){

		$cnt = (int) $cnt;
		if($cnt < 1){
			$cnt = 1;
		}
		if($cnt > 50){
			$cnt = 50;
		}

		$setTime = time()-86400*7;
		$adminDoc = new IpdfAdminDoc();
		$newID = array();

		for($c = 1; $c <= $cnt; ++$c){
			$newForm = new NewForm();
			$newForm->url = 'fake-unit-form-'.$c;
			$newForm->title = 'Fake Unit Form '.$c;
			list($success, $err, $msg, $js, $url, $path) = $adminDoc->createViaStudio($newForm);
			// nastavime datum poslednej zmeny input descriptora
			$path = $path . IpdfForm::DEFAULT_INPUT_DESCRIPTOR;
			if(!is_file($path)){
				exit('Input descriptoe not found in ['.$path.']');
			}
			touch($path, $setTime);
			$newID[] = $adminDoc->id;
		}

		return $newID;
	}


}
