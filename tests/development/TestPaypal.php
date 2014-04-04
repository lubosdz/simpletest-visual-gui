<?php
/**
* $Id: TestPaypal.php 674 2014-02-28 22:46:28Z admin $
*/
class TestPaypal extends UnitTestCase{

	/**
	* Succesfull Paypal payment with standard buttons and PDT turned ON (PDT = Payment Data Transfer)
	*/
	public function testPayCredits(){

		/**
		* @var PayModule
		*/
		$paymod = Yii::app()->getModule('pay');

		/**
		* @var PaymentPaypal
		*/
		$payment = new PaymentPaypal();

		// test filures
		$price = 0;
		$res = $payment->create($price);
		$this->assertTrue(!$res['success'], 'Payment should fail with invalid price ['.$price.']');
		$price = -5;
		$res = $payment->create($price);
		$this->assertTrue(!$res['success'], 'Payment should fail with invalid price ['.$price.']');
		$price = 1.1;
		$res = $payment->create($price);
		$this->assertTrue(!$res['success'], 'Payment should fail with invalid price ['.$price.']');
		$price = 21;
		$res = $payment->create($price);
		$this->assertTrue(!$res['success'], 'Payment should fail with invalid price ['.$price.']');
		$price = 999;
		$res = $payment->create($price);
		$this->assertTrue(!$res['success'], 'Payment should fail with invalid price ['.$price.']');
		$price = 'abc';
		$res = $payment->create($price);
		$this->assertTrue(!$res['success'], 'Payment should fail with invalid price ['.$price.']');

		// valid price
		$price = array_rand($paymod->fees);
		$res = $payment->create($price);

		if($this->assertTrue(!empty($res['success']) && !empty($res['sign']) )){

			$sign = $res['sign'];

			// emulate return request, which contains following GET params:
			$data = array(
				'tx' => strtoupper(substr(md5(uniqid()), 0, 15)),
				'st' => PaymentPaypal::PAYPAL_STATUS_COMPLETED,
				'amt' => number_format($price, 2), // 5.00
				'cc' => 'EUR',
				'cm' => '', // SIGN, e.g. 9bhVJ_QcsJzUMVkqByyohw
				'item_number' => 'IPDFCREDITS', // anything, not checked
			);

			$browser = new SimpleBrowser();
			//$browser->setConnectionTimeout(30); // default is only 15 secs

			// bez CSRF tokena vrati chybu 500 - exception: invalid CSRF token
			$url = Yii::app()->createAbsoluteUrl('/pay/paypal/success' /*, array(), 'http'*/); // auto detect HTTP/HTTPS
			//$html = $browser->get($url, $data);
			//$this->assertTrue($browser->getResponseCode() == 500 && false !== stripos($html, 'CSRF token'));

			// set YII COOKIE TOKEN
			if(!empty($_COOKIE['YII_CSRF_TOKEN'])){
				$browser->setCookie('YII_CSRF_TOKEN', $_COOKIE['YII_CSRF_TOKEN']);
			}

			//$html = $browser->get($url, $data); // nemame nastaveny SIGN - exception: missing parameter
			//$this->assertTrue($browser->getResponseCode() == 500 && false !== stripos($html, 'parametre platby'));

			// odteraz zapneme debugging - predosle exceptions sa zle testuju
			$data['cm'] = $sign;
			if(!empty($_COOKIE['DBGSESSID'])){
				$browser->setCookie('DBGSESSID', $_COOKIE['DBGSESSID']);
			}

			// OK - vrati login HTML stranku, pretoze po spracovani paypal redirectne na moje/platby - ale unit test ma nelognuteho usera, takze redirectne na login page
			//$html = $browser->post($url, $data);

			// takmer stale hadze socket resource error ...
			//$html = $browser->get($url, $data); // OK

			list($result, $message, $urlPayTicket, $model) = PaymentPaypal::model()->verify($sign, $data['amt'], $data['st'], $data['tx']);

			//sleep(1);
			//if($this->assertTrue(!empty($html) && $browser->getResponseCode() == 200 &&  false !== stripos($html, 'LoginForm_password') )){
			if($this->assertTrue(!empty($result) && !empty($model) )){
				// preverime, ci existuje payment Main
				$sign = IpdfData::decryptUrlSafe($sign);
				list($paymentId, $price, $userId) = explode(IpdfData::SEPARATOR, $sign);
				/**
				* @var PaymentMain
				*/
				$res = PaymentMain::model()->findByAttributes(array('fk_source_id' => $paymentId, 'payment_system' => PaymentMain::SYSTEM_PAYPAL));
				$this->assertTrue(!empty($res) && $res->amount == $price && $res->fk_user_id == $userId);
			}


			// zahadne odsekne socket connection - docasne vypnute, nie velmi dolezity test
			//$browser = new SimpleBrowser();
			/*
			// test cancel link - tiez login page
			$url = Yii::app()->createAbsoluteUrl('/pay/paypal/cancel'); // auto detect HTTP/HTTPS
			$html = $browser->get($url);
			//sleep(1);
			$this->assertTrue(!empty($html) && $browser->getResponseCode() == 200 && false !== stripos($html, 'LoginForm_password'));

			// failed link
			$url = Yii::app()->createAbsoluteUrl('/pay/paypal/failed'); // auto detect HTTP/HTTPS
			$html = $browser->get($url);
			//sleep(1);
			$this->assertTrue(!empty($html) && $browser->getResponseCode() == 200 && false !== stripos($html, 'LoginForm_password'));
			*/
		}

	}




}
