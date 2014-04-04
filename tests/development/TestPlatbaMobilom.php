<?php
/**
* $Id: TestPlatbaMobilom.php 522 2014-01-15 20:38:55Z admin $
* Database unit tests
*/

class TestPlatbaMobilom extends UnitTestCase{

	/**
	* Testuje vytvorenie platby - avizo o SMS - potvrdenie platby
	* Tento test nerobi HTTP requesty
	*/
	public function testSmsUnlockForm(){

		// init document and processor to be paid
		$form = IpdfForm::getInstance();
		$data = $form->getFormsSortedById();
		$formId = array_rand($data);
		$data = IpdfForm::getInstance()->initByFormId($formId);

		$price = $data['price'];
		if(empty($price)){
			$price = IpdfForm::PRICE_UNLOCK_FORM_EUR;
		}

		// invalid phone
		$rand = mt_rand(111,999)*1000;
		$phone = '0903/'.$rand;
		$res = PaymentSms::model()->create($phone, $price, ActiveRecord::PAY_PURPOSE_UNLOCK);
		$this->assertTrue($res['success']==0, 'Phone No. ['.$phone.'] must be invalid.');

		// valid phone
		$phone = '0903 '.$rand;
		$res = PaymentSms::model()->create($phone, $price, ActiveRecord::PAY_PURPOSE_UNLOCK);
		if($this->assertTrue($res['success']==1, $res['message'].' ('.$phone.')')){
			// emulate processing request
			$msisdn = PaymentSms::getPhoneMsisdn($phone);
			$text = 'ipdf';
			$smsId = strtoupper(substr(md5(uniqid()), 0, 20));

			$res = PaymentSms::model()->process($msisdn, $text, $smsId);
			if($this->assertTrue(false!==stripos($res, 'dakujeme za '), 'Invalid response: '.$res)){
				// emulate confirmation
				$result = 'FAIL';
				$confirmed = PaymentSms::model()->confirm($smsId, $result);
				$this->assertTrue($confirmed == false, 'Cannot confirm with operator response ['.$result.']');

				/**
				* @var PaymentSms
				*/
				$model = PaymentSms::model()->findByAttributes(array(
					'sms_id' => $smsId,
				));
				$this->assertTrue(!empty($model) && !empty($model->id));
				$response = PaymentSms::model()->check($model->id);
				$this->assertTrue($response['result']==='error');

				$result = 'OK';
				$confirmed = PaymentSms::model()->confirm($smsId, $result);
				if($this->assertTrue($confirmed == true, 'Must confirm with operator response ['.$result.']')){
					// nacitame data do formulara / import
					/**
					* @var PaymentSms
					*/
					$row = PaymentSms::model()->findByAttributes(array('sms_id' => $smsId));
					if($this->assertTrue(is_object($row), 'Failed finding record sms_id=['.$smsId.']')){
						list($cnt, $catalog) = IpdfData::getInstance()->import('', $row->form_data, '', '', true);
						$this->assertTrue($cnt > 0 && !empty($catalog['DOCUMENT'][0]));
					}
				}

				/**
				* @var PaymentSms
				*/
				$model = PaymentSms::model()->findByAttributes(array(
					'sms_id' => $smsId,
				));
				$this->assertTrue(!empty($model) && !empty($model->id));
				$response = PaymentSms::model()->check($model->id);
				$this->assertTrue($response['result']==='confirmed');

				// zaroven musel vzniknut zaznam pre hlavnu platbu, lebo user je lognuty a bol vygenerovany prijmovy doklad
				$paymentMain = PaymentMain::model()->findByAttributes(array(
					'fk_form_id' => $formId,
					'fk_source_id' => $model->id,
					'payment_system' => PaymentMain::SYSTEM_SMS
				));
				$this->assertTrue(!empty($paymentMain));

				$flash = Yii::app()->user->getFlash('message');
				$this->assertTrue(false !== strpos($flash, 'OK -')); // uspech
				$this->assertTrue(false !== strpos($flash, 'https://')); // secure link na download pokladnicny doklad
			}
		}
	}

	/**
	* Testuje vytvorenie platby - avizo o SMS - potvrdenie platby
	* Tento test robi HTTP requesty cez SmsController - simuluje avizo + potvrdenie od mobilneho operatora
	* We dont use HTTPS - throws errors on socket stream..
	*/
	public function testSmsUnlockFormHttp(){

		// init document and processor to be paid
		$form = IpdfForm::getInstance();
		$data = $form->getFormsSortedById();
		$formId = array_rand($data);
		$data = IpdfForm::getInstance()->initByFormId($formId);

		$price = $data['price'];
		if(empty($price)){
			$price = IpdfForm::PRICE_UNLOCK_FORM_EUR;
		}

		// create expected payment
		$phone = '0903 '.mt_rand(111,999)*1000;
		$res = PaymentSms::model()->create($phone, $price, ActiveRecord::PAY_PURPOSE_UNLOCK);

		if($this->assertTrue($res['success']==1 && !empty($res['paymentId']), $res['message'].' ('.$phone.')')){
			$paymentId = $res['paymentId'];
			// emulate processing request
			$msisdn = PaymentSms::getPhoneMsisdn($phone);
			$text = 'ipdf';
			$smsId = strtoupper(substr(md5(uniqid()), 0, 20));

			$browser = new SimpleBrowser();
			if(!empty($_COOKIE['DBGSESSID'])){
				$browser->setCookie('DBGSESSID', $_COOKIE['DBGSESSID']);
			}

			// https://ipdf.local/pay/sms/process?msisdn=421903111222&text=ipdf&id=e78965af54654d
			$url = Yii::app()->createAbsoluteUrl('/pay/sms/process', array(
				'msisdn' => $msisdn,
				'text' => 'ipdf',
				'id' => $smsId
			), 'http');

			$res = $browser->get($url);

			if($this->assertTrue(false!==stripos($res, 'dakujeme za '), 'Invalid response: '.$res)){
				// emulate confirmation

				// https://ipdf.local/pay/sms/confirm?id=e78965af54654d&res=OK
				$url = Yii::app()->createAbsoluteUrl('/pay/sms/confirm', array(
					'res' => 'OK',
					'id' => $smsId
				), 'http');

				$res = $browser->get($url);

				if($this->assertTrue($res=="OK", $res)){
					// emulate ajax check
					// POST (SMS only): https://ipdf.local/pay/unlock/check?hs=e78965af54654d
					// POST (SMS + Viamo): https://ipdf.local/pay/unlock/check?hs=e78965af54654d&hv=e9e54d5gf_e78965af54654d
					$payHashSms = IpdfData::encryptUrlSafe($paymentId);
					$url = Yii::app()->createAbsoluteUrl('/pay/unlock/check', array(), 'http');
					$res = $browser->post($url, array('hs' => $payHashSms)); // {"result":"confirmed","message":""}
					if($this->assertTrue(!empty($res), $res)){
						$res = json_decode($res, true);
						$this->assertTrue($res['result'] == 'confirmed');

						// preverime presun do PaymentMain - nevytvori zaznam, lebo user nie je prihlaseny
						//$res = PaymentMain::model()->findByAttributes(array('payment_system' => PaymentMain::SYSTEM_SMS, 'fk_source_id' => $paymentId));
						//$this->assertTrue(is_object($res), 'Failed creating record in PaymentMain for ID=['.$paymentId.'].');

						// preverime, ci je form unlocked - neda sa tiez, user je vytvoreny cez stateless HTTP request - mimo samotneho requestu neexistuje - vzdy vytvori nove session + noveho usera

					}

				}
			}
		}
	}


}
