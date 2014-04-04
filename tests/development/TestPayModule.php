<?php
/**
* $Id$
*/
class TestPayModule extends UnitTestCase{

	/**
	* True if SMS payments enabled
	*/
	protected $smsEnabled;

	/**
	* True if Viamo payments enabled
	*/
	protected $viamoEnabled;

	/**
	* Config params
	* @var CAttributeCollection
	*/
	protected $params = array();

	/**
	* @var PayModule
	*/
	protected $payModule;

	/**
	* timestamp start test execution - used as DB create_ts param
	*/
	protected $startTs;

	protected function init(){
		$this->params = Yii::app()->params;
		$this->smsEnabled = !empty($this->params['platbaMobilom']['enabled']);
		$this->viamoEnabled = !empty($this->params['viamo']['enabled']);
		$this->payModule = Yii::app()->getModule('pay');
		$this->startTs = time();
	}

	protected function getRandomFormData(){
		$form = IpdfForm::getInstance();
		$data = $form->getFormsSortedById();
		$formId = array_rand($data);
		return $form->initByFormId($formId);
	}

	/**
	* Otestuje vytvorenie platby + uhradu cez SMS
	*/
	function testUnlockFormBySms(){

		// init document and processor to be paid
		$this->init();
		if(!$this->smsEnabled){
			echo 'SMS payments disabled.';
			return;
		}

		$form = $this->getRandomFormData();

		// prepare data for expected payment
		$phone = '09039'.mt_rand(10000,99999);
		$msisdn = PaymentSms::getPhoneMsisdn($phone);
		$price = max($form['price'], IpdfForm::PRICE_UNLOCK_FORM_EUR);
		$purpose = ActiveRecord::PAY_PURPOSE_UNLOCK;

		// NOTE: nemozeme robit HTTP request, pretoze pri odomknuti formulara robime disconnected session, pricom formular nemoze byt nikdy inicializovany
		$res = $this->payModule->createExpectedPayment(substr($phone, 0, -1), $price, $purpose);
		$this->assertTrue($res['success'] == '0', 'Should fail for invalid phone ['.substr($phone, 0, -1).']');

		$res = $this->payModule->createExpectedPayment($phone, 0, $purpose);
		$this->assertTrue($res['success'] == '0', 'Should fail for invalid price [0]');

		$res = $this->payModule->createExpectedPayment($phone, $price, 'aa');
		$this->assertTrue($res['success'] == '0', 'Should fail for invalid purpose [aa]');

		$res = $this->payModule->createExpectedPayment($phone, $price, $purpose);

		if($this->assertTrue(!empty($res['success']) && false !== stripos($res['message'], 'Cena SMS '), 'Error: '.$res['message'])){

			$tmp = array(
				'phone' => $msisdn,
				'purpose' => $purpose,
				//'amount' => str_replace('.', ',', $price), // ak neprehodime, mySQL/MariaDB ju nenajde
				'fk_form_id' => $form['id'],
			);

			// najdeme SMS platbu
			$sms = PaymentSms::model()->findByAttributes($tmp, ' created_ts >= :ts ', array(':ts' => $this->startTs));
			$this->assertTrue(is_object($sms));

			if($this->viamoEnabled){
				// preverime, ze existuje aj viamo platba
				/**
				* @var PaymentViamo
				*/
				$viamo = PaymentViamo::model()->findByAttributes($tmp, ' created_ts >= :ts ', array(':ts' => $this->startTs));
				$this->assertTrue(is_object($viamo));
			}

			// uhradime cez SMS
			$browser = new SimpleBrowser();
			if(!empty($_COOKIE['DBGSESSID'])){
				$browser->setCookie('DBGSESSID', $_COOKIE['DBGSESSID']);
			}
			$smsId = strtoupper(substr(md5($this->startTs), 0, 20)); // max. 20 chars

			// 1. https://ipdf.local/pay/sms/process?msisdn=421903111222&text=ipdf&id=e78965af54654d
			$url = Yii::app()->createAbsoluteUrl('/pay/sms/process', array(
				'msisdn' => $msisdn,
				'text' => 'ipdf unittest '.$this->startTs,
				'id' => $smsId,
			));
			$res = $browser->get($url);

			if($this->assertTrue(false!==stripos($res, 'dakujeme za '), 'Invalid response: '.$res)){

				// 2. simulujeme potvrdenie od operatora
				$url = Yii::app()->createAbsoluteUrl('/pay/sms/confirm', array(
					'res' => 'OK',
					'id' => $smsId,
				));

				$res = $browser->get($url);

				if($this->assertTrue($res=="OK", print_r($res, true))){

					// preverime ajaxom, ci uz bolo zaplatene a vytvorime doklad o zaplateni, statistics, platbu pod usera atd..
					$url = Yii::app()->createAbsoluteUrl('/pay/unlock/check');
					$tmp = array(
						'hs' => IpdfData::encryptUrlSafe($sms->id), // hash SMS platby
						'hv' => empty($viamo) ? '' : IpdfData::encryptUrlSafe($viamo->id), // hash Viamo platby
					);
					$res = $browser->post($url, $tmp);
					$res = json_decode($res, true);
					$this->assertTrue(!empty($res['result']) && $res['result'] == PaymentSms::PAY_CODE_CONFIRMED);

					// viamo platba nesmie uz existovat
					if($this->viamoEnabled && !empty($viamo)){
						$viamoId = $viamo->id;
						$row = PaymentViamo::model()->findByPk($viamoId);
						$this->assertTrue(empty($row), 'Viamo paralel records should be deleted, because was paid already by SMS.');
					}

				}
			}
		}
	}


	/**
	* Otestuje vytvorenie platby + uhradu cez SMS
	*/
	function testUnlockFormByViamo(){

		// init document and processor to be paid
		$this->init();
		if(!$this->viamoEnabled){
			echo 'Viamo payments disabled.';
			return;
		}

		$form = $this->getRandomFormData();

		// prepare data for expected payment
		$phone = '09039'.mt_rand(10000,99999);
		$msisdn = PaymentSms::getPhoneMsisdn($phone);
		$price = max($form['price'], IpdfForm::PRICE_UNLOCK_FORM_EUR);
		$purpose = ActiveRecord::PAY_PURPOSE_UNLOCK;

		// NOTE: nemozeme robit HTTP request, pretoze pri odomknuti formulara robime disconnected session, pricom formular nemoze byt nikdy inicializovany
		$res = $this->payModule->createExpectedPayment(substr($phone, 0, -1), $price, $purpose);
		$this->assertTrue($res['success'] == '0', 'Should fail for invalid phone ['.substr($phone, 0, -1).']');

		$tmp = IpdfData::encryptUrlSafe(PaymentViamo::MAX_AMOUNT + 1); // too large amount
		$res = $this->payModule->createExpectedPayment($phone, $tmp, $purpose);
		// must return "Suma k úhrade je príliš vysoká" - tzn. decrypted correctly amount/price
		$this->assertTrue($res['success'] == '0' && false !== stripos($res['message'], ' vysok'), 'Should fail for too large price ['.$tmp.' = '.(PaymentViamo::MAX_AMOUNT + 1).']');

		$res = $this->payModule->createExpectedPayment($phone, $price, '');
		$this->assertTrue($res['success'] == '0', 'Should fail for invalid purpose []');

		$res = $this->payModule->createExpectedPayment($phone, $price, $purpose);

		if($this->assertTrue(!empty($res['success']) && false !== mb_stripos($res['message'], 'mobilnej aplikácie Viamo'), 'Error: '.$res['message'])){

			$tmp = array(
				'phone' => $msisdn,
				'purpose' => $purpose,
				//'amount' => str_replace('.', ',', $price), // ak neprehodime, mySQL/MariaDB ju nenajde
				'fk_form_id' => $form['id'],
			);

			/**
			* @var PaymentViamo
			*/
			$viamo = PaymentViamo::model()->findByAttributes($tmp, ' created_ts >= :ts ', array(':ts' => $this->startTs));
			if($this->assertTrue(is_object($viamo))){
				$viamoId = $viamo->id;
			}

			if($this->smsEnabled){
				// najdeme aj SMS platbu
				$sms = PaymentSms::model()->findByAttributes($tmp, ' created_ts >= :ts ', array(':ts' => $this->startTs));
				if($this->assertTrue(is_object($sms))){
					$smsId = $sms->id;
				}
			}

			// simulujeme uhradu cez viamo SMS relay
			$relaySms = array(
				'sms' => 'SmsForwardViaHttp',
				'from' => $phone,
				// 'body' => 'Viamo test 0,2 eur platba cez relay. Xl',
				'body' => 'Alfonz Unittester Vam poslal/a '.$price.' EUR. Sprava: "ipdf '.$viamo->hash.'". Pre prijatie platby si doplnte cislo uctu na www.viamo.sk/prijem/.',
			);

			$url = Yii::app()->createAbsoluteUrl('/pay/viamo/relay');

			$browser = new SimpleBrowser($url);
			if(!empty($_COOKIE['DBGSESSID'])){
				$browser->setCookie('DBGSESSID', $_COOKIE['DBGSESSID']);
			}

			$res = $browser->post($url, $relaySms);
			$res = trim($res);

			// relay odpoved OK?
			if($this->assertTrue('OK' == substr($res, 0, 2), 'Error: '.print_r($res, true))){

				// preverime ajaxom, ci uz bolo zaplatene a vytvorime doklad o zaplateni, statistics, platbu pod usera atd..
				$url = Yii::app()->createAbsoluteUrl('/pay/unlock/check');
				$tmp = array(
					'hs' => empty($smsId) ? '' : IpdfData::encryptUrlSafe($smsId), // hash SMS platby
					'hv' => empty($viamoId) ? '' : IpdfData::encryptUrlSafe($viamoId), // hash Viamo platby
				);
				$res = $browser->post($url, $tmp);
				$res = json_decode($res, true);
				$this->assertTrue(!empty($res['result']) && $res['result'] == PaymentViamo::PAY_CODE_CONFIRMED);

				// SMS platba uz nesmie existovat
				if(!empty($smsId)){
					$row = PaymentSms::model()->findByPk($smsId);
					$this->assertTrue(empty($row));
				}

			}
		}
	}

	/**
	* Test ak user zaplati prilis nizku sumu
	*/
	function testAddCreditsBySms(){

		$this->init();
		if(!$this->smsEnabled){
			echo 'SMS payments disabled.';
			return;
		}

		$price = array_rand($this->payModule->fees);
		while($price > PaymentSms::MAX_AMOUNT){
			$price = array_rand($this->payModule->fees);
		}
		//echo 'Adding SMS credits: '.$price.' EUR.';
		$phone = '0903 '.mt_rand(100000,999999);
		$msisdn = PaymentSms::getPhoneMsisdn($phone);
		$purpose = ActiveRecord::PAY_PURPOSE_CREDITS;

		$res = $this->payModule->createExpectedPayment($phone, $price, $purpose, PaymentMain::SYSTEM_SMS);

		if($this->assertTrue(!empty($res['success']) && false !== stripos($res['message'], 'Cena SMS '), 'Error: '.$res['message'])){

			$tmp = array(
				'phone' => $msisdn,
				'purpose' => $purpose,
				'amount' => $price, // vzdy cele cislo
			);

			// najdeme SMS platbu
			$sms = PaymentSms::model()->findByAttributes($tmp, ' created_ts >= :ts ', array(':ts' => $this->startTs));
			$this->assertTrue(is_object($sms));

			// uhradime cez SMS
			$browser = new SimpleBrowser();
			if(!empty($_COOKIE['DBGSESSID'])){
				$browser->setCookie('DBGSESSID', $_COOKIE['DBGSESSID']);
			}
			$smsId = strtoupper(substr(md5($this->startTs), 0, 20)); // max. 20 chars

			// 1. https://ipdf.local/pay/sms/process?msisdn=421903111222&text=ipdf&id=e78965af54654d
			$url = Yii::app()->createAbsoluteUrl('/pay/sms/process', array(
				'msisdn' => $msisdn,
				'text' => 'ipdf unittest '.$this->startTs,
				'id' => $smsId,
			));
			$res = $browser->get($url);

			if($this->assertTrue(false!==stripos($res, 'dakujeme za '), 'Invalid response: '.$res)){

				// 2. simulujeme potvrdenie od operatora
				$url = Yii::app()->createAbsoluteUrl('/pay/sms/confirm', array(
					'res' => 'OK',
					'id' => $smsId,
				));

				$res = $browser->get($url);

				if($this->assertTrue($res=="OK", print_r($res))){

					// preverime ajaxom, ci uz bolo zaplatene a vytvorime doklad o zaplateni, statistics, platbu pod usera atd..
					$url = Yii::app()->createAbsoluteUrl('/pay/unlock/check');
					$tmp = array(
						'hs' => IpdfData::encryptUrlSafe($sms->id), // hash SMS platby
						'hv' =>'',
					);
					$res = $browser->post($url, $tmp);
					$res = json_decode($res, true);
					if($this->assertTrue(!empty($res['result']) && $res['result'] == PaymentSms::PAY_CODE_CONFIRMED)){

						// preverime, ze platba bola presunuta do PaymentMain
						$row = PaymentMain::model()->findByAttributes(array(
							'payment_system' => PaymentMain::SYSTEM_SMS,
							'fk_source_id' => $sms->id,
							'amount' => $price,
						));

						$this->assertTrue(!empty($row));

					}
				}
			}
		}
	}



	/**
	* Test ak user zaplati prilis nizku sumu
	*/
	function testAddCreditsByViamo(){

		$this->init();
		if(!$this->viamoEnabled){
			echo 'Viamo payments disabled.';
			return;
		}

		$price = array_rand($this->payModule->fees);
		//echo 'Adding SMS credits: '.$price.' EUR.';
		$phone = '0903 '.mt_rand(100000,999999);
		$msisdn = PaymentSms::getPhoneMsisdn($phone);
		$purpose = ActiveRecord::PAY_PURPOSE_CREDITS;

		$res = $this->payModule->createExpectedPayment($phone, $price, $purpose, PaymentMain::SYSTEM_VIAMO);

		if($this->assertTrue(!empty($res['success']) && false !== mb_stripos($res['message'], 'mobilnej aplikácie Viamo'), 'Error: '.$res['message'])){

			$tmp = array(
				'phone' => $msisdn,
				'purpose' => $purpose,
				'amount' => $price, // vzdy cele cislo
			);

			// najdeme viamo platbu
			/**
			* @var PaymentViamo
			*/
			$viamo = PaymentViamo::model()->findByAttributes($tmp, ' created_ts >= :ts ', array(':ts' => $this->startTs));
			if($this->assertTrue(is_object($viamo))){

				// simulujeme uhradu cez viamo SMS relay
				$relaySms = array(
					'sms' => 'SmsForwardViaHttp',
					'from' => $phone,
					// 'body' => 'Viamo test 0,2 eur platba cez relay. Xl',
					'body' => 'Alfonz Unittester Vam poslal/a '.$price.' EUR. Sprava: "ipdf  '.$viamo->hash.'". Pre prijatie platby si doplnte cislo uctu na www.viamo.sk/prijem/.',
				);

				$url = Yii::app()->createAbsoluteUrl('/pay/viamo/relay');

				$browser = new SimpleBrowser($url);
				if(!empty($_COOKIE['DBGSESSID'])){
					$browser->setCookie('DBGSESSID', $_COOKIE['DBGSESSID']);
				}

				$res = $browser->post($url, $relaySms);
				$res = trim($res);

				// relay odpoved OK?
				if($this->assertTrue('OK' == substr($res, 0, 2), 'Error: '.print_r($res, true))){

					// preverime ajaxom, ci uz bolo zaplatene a vytvorime doklad o zaplateni, statistics, platbu pod usera atd..
					$url = Yii::app()->createAbsoluteUrl('/pay/unlock/check');
					$tmp = array(
						'hs' => '',
						'hv' => IpdfData::encryptUrlSafe($viamo->id), // hash Viamo platby
					);
					$res = $browser->post($url, $tmp);
					$res = json_decode($res, true);
					if($this->assertTrue(!empty($res['result']) && $res['result'] == PaymentViamo::PAY_CODE_CONFIRMED)){

						// preverime, ze platba bola presunuta do PaymentMain
						$row = PaymentMain::model()->findByAttributes(array(
							'payment_system' => PaymentMain::SYSTEM_VIAMO,
							'fk_source_id' => $viamo->id,
							'amount' => $price,
						));

						$this->assertTrue(!empty($row));

					}
				}
			}
		}
	}


}
