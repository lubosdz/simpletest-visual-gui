<?php
/**
* $Id: TestFormulars.php 693 2014-03-04 22:39:04Z admin $
*/
class TestFormulars extends UnitTestCase{

	const FORM_ID_PODACI_LISTOK = 2000;

	/**
	* Specificky nazov snapshot suboru - ak existuje, nehladaju sa dalsie snapshot subory
	*/
	const FILENAME_SNAPSHOT = 'unittest.ini.php';

	protected $formData = array();

	/**
	* @var IpdfProcessor
	*/
	protected $processor;

	protected $inputData = array(
		self::FORM_ID_PODACI_LISTOK => array(
			'A01_ODOSIELATEL_MENO' => 'Unit Sender',
			'A02_ODOSIELATEL_ULICA_CISLO_DOMU' => 'senderstreet 123/c',
			'A03_ODOSIELATEL_MESTO_OBEC' => 'Sendercity 123/c',
			'A09_ADRESAT_FIRMA' => 'Unit Adresát, s.r.o.',
			'A10_ADRESAT_MENO_PRIEZVISKO' => 'Adresátsurname',
			'A11_ADRESAT_ULICA_CISLO_DOMU' => 'street 55 - c',
			'A12_ADRESAT_PSC_OBEC' => '957 01',
			'A04_ODOSIELATEL_MOBIL_EMAIL' => 'unit@test.sk',
			'A14_DOBIERKA_CISLO_UCTU' => '000-00321654897/0200',
			'A15_VARIABILNY_SYMBOL' => '65432100',
			'A16_POISTENIE_EUR' => 555.90,
			'A17_DOBIERKA_EUR' => 50.60,
			//'A18_DOBIERKA_NA_ADRESU' => 'adresu',
			'A19_DOBIERKA_NA_UCET' => 'ucet',
		)
	);

	public function setUp(){
		// nothing
	}

	public function tearDown(){
		// refresh user credits
		/**
		* @var WebUser
		*/
		$user = Yii::app()->user;
		$id = (int) $user->id;
		/**
		* @var UserModel
		*/
		$userModel = UserModel::model()->findByPk($id);
		if($userModel){
			$user->setData('credits', $userModel->credits);
		}
	}

	/**
	* Create documents via user-input and WS
	* Used to check radio buttons
	*/
	public function testWebservicesPodaciListok(){
		$this->setFormById(self::FORM_ID_PODACI_LISTOK);
		$this->createDocument();
	}

	protected function setRandomForm(){
		$form = IpdfForm::getInstance();
		$data = $form->getFormsSortedById();
		$formId = array_rand($data);
		$this->setFormById($formId);
	}

	protected function setFormById($formId){
		$this->formData = IpdfForm::getInstance()->initByFormId($formId);
		$this->processor = IpdfForm::getInstance()->getProcessor();
	}

	/**
	* Set all needed user setting to execute all tests
	* @param float $minCredits Minimum credits EUR required to create all paid formulars
	*/
	protected function ensureUser($minCredits = 50){

		// ensure user has enough credits and set domains if needed
		$id = (int) Yii::app()->user->id;
		/**
		* @var UserModel
		*/
		$userModel = UserModel::model()->findByPk($id);
		if($this->assertTrue(!empty($userModel))){
			$update = array();
			// ensure some minimal credits
			if($userModel->credits < $minCredits){
				$userModel->credits += $minCredits;
				$update[] = 'credits';
				Yii::app()->user->setData('credits', $userModel->credits);
			}
			// ensure API key
			if(empty($userModel->api_key)){
				$userModel->api_key = $userModel->getApiKey();
				$update[] = 'api_key';
				Yii::app()->user->setData('api_key', $userModel->api_key);
			}
			// ensure user name for pay receipt
			if(empty($userModel->company_name)){
				$userModel->company_name = 'Unittest, s.r.o.';
				$update[] = 'company_name';
				Yii::app()->user->setData('company_name', $userModel->company_name);
			}

			if($update){
				$userModel->update($update);
				$userModel = UserModel::model()->findByPk($id);
				$this->assertTrue($userModel->credits >= 5 && strlen($userModel->api_key), 'Empty API key or credits < 5 EUR.');
			}

			// ensure authenticated domain (remote for SOAP test)
			list($ip, $dns) = IpdfWs::getRemoteHost();
			$criteria = new CDbCriteria(); // pozor na poradie AND .. OR
			$criteria->compare('LOWER(t.domain)', strtolower($dns)); // case insensitive
			$criteria->compare('domain', $ip, false, 'OR');
			$criteria->compare('fk_user_id', $id);
			$domain = DomainModel::model()->find($criteria);
			if(empty($domain)){
				$domain = new DomainModel();
				$domain->fk_user_id = $id;
				$domain->domain = $ip; // we use IP because DNS could be invalid domain e.g. PC name "LLL1-PC"
				$domain->fk_created_user_id = $id;
				$domain->created_ts = time();
				$this->assertTrue($domain->save(), 'Failed adding domain ['.$ip.'] for user ID=['.$id.'] ... '.$domain->getErrorsFormated());
			}
			// ensure authenticated domain (local for JSON test)
			list($ip, $dns) = array('127.0.0.1', 'localhost');
			$criteria = new CDbCriteria(); // pozor na poradie AND .. OR
			$criteria->compare('LOWER(t.domain)', strtolower($dns)); // case insensitive
			$criteria->compare('domain', $ip, false, 'OR');
			$criteria->compare('fk_user_id', $id);
			$domain = DomainModel::model()->find($criteria);
			if(empty($domain)){
				$domain = new DomainModel();
				$domain->fk_user_id = $id;
				$domain->domain = $ip; // we use IP because DNS could be invalid domain e.g. PC name "LLL1-PC"
				$domain->fk_created_user_id = $id;
				$domain->created_ts = time();
				$this->assertTrue($domain->save(), 'Failed adding domain ['.$ip.'] for user ID=['.$id.'] ... '.$domain->getErrorsFormated());
			}
		}

		return $userModel;
	}

	protected function createDocument(){

		$userModel = $this->ensureUser();
		$this->assertTrue(is_object($userModel)) ;

		// create document
		if($this->assertTrue(!empty($userModel) && !empty($this->formData['id']) && is_object($this->processor) )){
			$id = $this->formData['id'];
			if(isset($this->inputData[$id])){
				$formData = $this->inputData[$id]; // always here so far..
			}else{
				// TODO: genericka funkcia pre naplnenie input fields dla typu validatora
				//$formData = $this->fakeRandomData();
				$formData = array();
			}
			$this->processor->setFieldValues($formData, true, true);

			// pozor - errors pri inicializaci su fake errors, v podstate nas nezaujimaju
			// formulare s prefil user attributes su vzdy validovane pri inicializacii a preto takmer vzdy upozornia na error (required fields ..)
			$errors = $this->processor->collectErrors(true);
			//if($this->assertTrue(empty($errors), 'Errors: '.$errors)){
				// OK no field errors - generate document manually

				$generator = new IpdfGenerator($this->processor);

				// vynulujeme pocitadlo pokusov
				$filename = IpdfGenerator::getFilename($this->formData);
				$criteria=new CDbCriteria;
				$criteria->compare('t.path_rel',$filename,true);
				/**
				* @var ArchiveModel
				*/
				$row = ArchiveModel::model()->find($criteria);
				if($row){
					// there is already previous attempt, so we will also test max attempts
					$row->attempts = IpdfGenerator::MAX_ATTEMPTS_TYPO + 1;
					$row->update(array('attempts'));
					$result = $generator->generate()->getDocument();
					$this->assertTrue($result['success']===false);
					// now reset relevant and irrelevant attempts to ensure generation will pass
					$row->unlock();
				}
				// reset generator
				$generator = new IpdfGenerator($this->processor);
				$result = $generator->generate()->getDocument();
				if($this->assertTrue(!empty($result['success']), 'ERROR: '.$result['message'])){
					unset($result['model']);
					echo 'MANUAL Generation: <a href="'.TestUtils::getUrlDownload($result['path_absolute']).'" class="pretty" target="_blank">Download manual</a><br/><pre>'.print_r($result, true).'</pre>';

					// create document also through WS JSON
					$ws = new IpdfWsJson();
					$result = $ws->runUnitTest('jsonRemoteServer', true);
					if($this->assertTrue(!empty($result['success']), 'CHYBA: '.$result['info'])){
						$result['file_content'] = substr($result['file_content'], 0, 50).' ...';
						echo 'JSON: <a href="'.$result['download_link'].'" target="_blank" class="pretty">Download JSON</a><br/><pre>'.print_r($result, true).'</pre>';
					}

					// create document also through WS SOAP
					$ws = new IpdfWsSoap();
					$result = $ws->runUnitTest('createDocument', true);
					if($this->assertTrue(!empty($result['success']), 'CHYBA: '.$result['info'])){
						$result['file_content'] = substr($result['file_content'], 0, 50).' ...';
						echo 'SOAP: <a href="'.$result['download_link'].'" target="_blank" class="pretty">Download SOAP</a><br/><pre>'.print_r($result, true).'</pre>';
					}

					// initiate document via iframe - should return HTML for iframe content if authorized successfully
					$browser = new SimpleBrowser();
					// must fail - invalid API KEY
					$url = IpdfForm::getInstance()->getUrlByDocId($id, 'http').'?iframe=someDummyApiKey';
					$html = $browser->get($url);
					$this->assertTrue(false!==stripos($html, 'Pozor - vyskytla sa chyba'), 'Should fail with invalid API key.');
					// OK - valid API KEY and enough credits
					$url = IpdfForm::getInstance()->getUrlByDocId($id, 'http').'?iframe='.$userModel->api_key;
					$html = $browser->get($url);
					$this->assertTrue(false===stripos($html, 'Pozor - vyskytla sa chyba'), 'Should not fail with valid API key.');
				}
			//}
		}
	}

	/**
	* Prehlada vo vsetkych formularok pracovnyc adresar a ak v nom najde:
	*  - unittest.ini.php
	*  - ipdf-snapshot-X.ini.php
	* tak z nich vytvori PDF subor.
	*/
	public function testAllFormsBySnapshots(){

		set_time_limit(120); // formulare generuje dlho...

		// how many PDFs should be generated at maximum
		$maxGenerate = 50;
		$excludeArchived = true;

		/**
		* @var UserModel - ensure enough credits
		*/
		$userModel = $this->ensureUser();

		/**
		* @var WebUser
		*/
		$user = Yii::app()->user;

		// reset attempts in DB
		$pattern = $user->getData('uniqueId');
		$res = ArchiveModel::model()->deleteAll("doc_title LIKE '%unittest%' AND path_rel LIKE '%{$pattern}%' ");

		$oAdmin = IpdfAdmin::getInstance();
		$oForm = IpdfForm::getInstance();
		$oData = IpdfData::getInstance();
		$cats = IpdfCategory::getInstance()->getList();

		$oForm->ensureForms(false, true); // ignore studio forms
		$forms = $oForm->getFormsSortedById();

		$ok = $found = $failedValidation = $na = array();
		$stamp = date('Ymd-Hi');

		foreach($forms as $id => $form){
			if(count($ok) >= $maxGenerate){
				break;
			}
			$title = $id.' - '.$form['title'];
			if($excludeArchived){
				$url = $cats[$form['category.id']]['url'];
				if(false !== strpos($url, 'archiv')){
					$na[] = $title;
					continue;
				}
			}
			// uncomment to test only desired category
/*
			if(false === strpos($form['basedir'], 'prikaz')
				&& false === strpos($form['basedir'], 'prijmovy')
				&& false === strpos($form['basedir'], 'vydavkovy')
				&& false === strpos($form['basedir'], 'faktura')
				&& false === strpos($form['basedir'], 'adresu')
				&& false === strpos($form['basedir'], 'na-ucet')
				&& false === strpos($form['basedir'], 'harok')
				&& false === strpos($form['basedir'], 'listok')
			){
				continue;
			}
*/

			// TO FIX: templates s rovnakymi nazvami sa spravne vygeneruju len v prvej classe - priklad podaci harok a ces. prikaz
			// maju template "Items" - v cest. prikaze sa nevygeneruje. lebo class s nazvom "Items" je uz nahrata!

			$root = $form['basedir'] .IpdfAdmin::WORKING_DIRECTORY. DS;
			$path = $root . self::FILENAME_SNAPSHOT;
			if(!is_file($path)){
				// find latest snapshot
				$path = $oAdmin->getLastSnapshotFilename($root);
			}
			if(!is_file($path)){
				// snapshot not available
				$na[$id] = $id.' - '.$form['title'];
			}else{
				// OK, found some file - import into PDF
				$found[$id] = $title;
				$res = $oForm->initByFormId($form['id'], false); // dont init processor, or it would delete any form stored in session
				if($this->assertTrue(!empty($res['id']) && $res['id']==$id, 'Failed initiating the formular ID=['.$title.']')){
					$processor = new IpdfProcessor($oForm, false);
					$res = $oData->setProcessor($processor)->load($path);
					if($this->assertTrue(false !== stripos($res, 'OK'))){
						$res = $processor->validateAll()->collectErrors();
						if($this->assertTrue(empty($res))){// OK no validation errors
							$oGenerator = new IpdfGenerator($processor);
							$res = $oGenerator->generate()->getDocument();
							if($this->assertTrue($res['success'] && is_file($res['path_absolute']), 'Error: '.$res['message']) ){
								$ok[$id] = '<a href="'.$res['url'].'" target="_blank" class="color-green"> <i class="icon-awesome icon-15x">&#xf01a;</i> <b>'.$title.'</b></a> ('.intval(filesize($res['path_absolute'])/1024).' kB, '.number_format($res['charged_credits'], 2).' / '.number_format($res['remaining_credits'], 2).' &euro;, '.basename($path).')';
								/**
								* @var ArchiveModel
								*/
								$model = $res['model'];
								$model->doc_title .= ' (unittest '.$stamp.')';
								$model->update(array('doc_title'));
							}
						}else{
							$failedValidation[$id] = $title . ' ('.strip_tags($res).')';
						}
					}
				}
			}
		}

		$failed = array_diff_key($found, $ok);
		$this->assertTrue(count($found) == count($ok), 'Failed creating some formulars.');

		// list results
		echo '<hr/><strong>Failed generation ('.count($failed).'):</strong><br/>'.implode('<br/>', $failed);
		echo '<hr/><strong>Failed validation ('.count($failedValidation).'):</strong><br/>'.implode('<br/>', $failedValidation);
		echo '<hr/><strong>Skipped ('.count($na).'):</strong><br/>'.implode('<br/>', $na);
		echo '<hr/><strong>Created ('.count($ok).'):</strong><br/>'.implode('<br/>', $ok);
	}

	/**
	* Vytvori exportne XML subory pre formulare, ak podporuju eDane
	*/
	public function testEDaneXmlExport(){
		//set_time_limit(120); // formulare generuje dlho...

		// how many PDFs should be generated at maximum
		$excludeArchived = true;

		/**
		* @var UserModel - ensure enough credits
		*/
		$userModel = $this->ensureUser();

		/**
		* @var WebUser
		*/
		$user = Yii::app()->user;

		$oAdmin = IpdfAdmin::getInstance();
		$oForm = IpdfForm::getInstance();
		$oData = IpdfData::getInstance();
		$oAction = IpdfAction::getInstance();
		$cats = IpdfCategory::getInstance()->getList();

		$oForm->ensureForms(false, true); // ignore studio forms
		$forms = $oForm->getFormsSortedById();

		$ok = $found = $na = $failed = array();
		$stamp = date('Ymd-Hi');

		foreach($forms as $id => $form){
			$title = $id.' - '.$form['title'];

			// preverime, ci je edane podporovane
			if(empty($form['hasEdane'])){
				continue;
			}

			if($excludeArchived){
				$url = $cats[$form['category.id']]['url'];
				if(false !== strpos($url, 'archiv')){
					$na[] = $title;
					continue;
				}
			}
			// uncomment to test only desired category
			/*
			if(false === strpos($form['basedir'], 'posta')){
				continue;
			}
			*/

			$root = $form['basedir'] .IpdfAdmin::WORKING_DIRECTORY. DS;
			$path = $root . self::FILENAME_SNAPSHOT;
			if(!is_file($path)){
				// find latest snapshot
				$path = $oAdmin->getLastSnapshotFilename($root);
			}
			if(!is_file($path)){
				// snapshot sample data not available
				$na[$id] = $id.' - '.$form['title'];
			}else{
				// OK, found some file - import into PDF
				$found[$id] = $title;
				$res = $oForm->initByFormId($form['id'], false); // dont init processor, or it would delete any form stored in session
				if($this->assertTrue(!empty($res['id']) && $res['id']==$id, 'Failed initiating the formular ID=['.$title.']')){
					$processor = new IpdfProcessor($oForm, false);
					$res = $oData->setProcessor($processor)->load($path);
					if($this->assertTrue(false !== stripos($res, 'OK'))){
						$oAction->setProcessor($processor);
						//$res = $oAction->process('export-edane')->getOutput();
						$res = $oAction->process('export-edane');
						$err = $res->getErrors();
						if($this->assertTrue(empty($err), print_r($err, true))){
							$js = $res->getJS();
							// href="dddf"
							if($this->assertTrue(preg_match('/href=\"(.+)\"/', $js[0], $match))){
								$ok[] = '<a href="'.$match[1].'" class="color-green">'.$title.'</a>'; // e.g. /yii/ipdf/web/download/d/ldyOSelLYyCtXsREvbw4LbSVNQBYADoKj3Fopum72-MlSgWh3cVwK73UouSt6xrxYD-7a3YaB7q_6UhbY_pP_A
							}
						}else{
							$failed[] = $title;
						}
					}else{
						$failed[] = $title;
					}
				}else{
					$failed[] = $title;
				}
			}
		}

		// cannot redeclare class eDane !!!!

		// list results
		echo '<hr/><strong>Failed ('.count($failed).'):</strong><br/>'.implode('<br/>', $failed);
		echo '<hr/><strong>Skipped - no sample data loaded ('.count($na).'):</strong><br/>'.implode('<br/>', $na);
		echo '<hr/><strong>Created ('.count($ok).'):</strong><br/>'.implode('<br/>', $ok);
	}

}
