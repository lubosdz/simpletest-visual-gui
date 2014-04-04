<?php
/**
* $Id$
*
* Vyjasnit s Viamo:
*
* - Typ algoritmu CBC: 128/192/256
* - Preco je AES sifra v HEX v dokumentacii, kedze mne vracia a-zA-Z0-9 vratane lomitka a rovna sa (openssl)
* - parameter processedOn - obtiazne parsovatelny datum zmenit na jednoduchu timestamp
* - parameter amount urobit povinny vzdy alebo editovatelny - nemal by byt nikdy prazdny
* - parameter SS urobit editovatelny
* - aka je uloha parametra "id" v JSON notifikacii
* - kde presne sa vyuzije prideleny business kluc - ako initiation vector - v tom pripade sa z key pouzije prvych 16 znakov?
* - vhodne by bolo rozhranie, kde si mozno overit vypocet parametra SIG
* - preco dla dokumentacie nie je SIG povinny pre QR platby - moze si ich hocikto nafejkovat?
* - pre business (P2B) by user nemal zadavat nic naviac - napr. meno business partnera by sa malo priradit podla telefonneho cisla, resp. byt nepovinne.
* 		Akakolvek akcia naviac ma tendenciu odradovat userov od pouzitia aplikacie - a to tym viac, cim je akcia vedomostne narocnejsia.
* 		Merali sme napr. vplyv preklikavania v poistnom wizarde - uz zaradenie 1 naviac stranky sposobilo stratu cca 8-10% userov.
*
*/
class TestPlatbaViamo extends UnitTestCase{

	/*
	const CYPHER = 'AES-128-ECB'; // pouzivaju 128 bit cypher

	protected $payData = array(
		// verzia aplikacie
		'QP' => '1.0',
		// business identifikator prijemcu, povinne pre P2B
		'BID' => 'IPDF-TEST',
		// referencny identifikator platby pre prijemcu, nepovinny
		//'RID' => '',
		// amount, platena suma, nepovinny, editovatelny, max. 10 znakov, max. 2 desatinne miesta
		'AM' => '',
		// currency, nepovinny, 3 znaky, povolene len EUR
		//'CC' => 'EUR',
		// datum platnosti, 12 cislic, e.g. 120001102014   (12:00 11.02.2014), nepovinny, needitovatelny
		//'DT' => '',
		// var. symbol - nepovinny, editovatelny, max. 10 znakov
		'VS' => '',
		// cost. symbol - 4 cislice 0-9, e.g. 0308
		//'CS' => '',
		// Spec. symbol 0-9, max. 10 znakov, needitovatelny
		//'SS' => '',
		// sprava pre prijimatela, max. 32 znakov, neskor 142 znakov, editovatelny, nepovinny
		'MSG' => '$',
		// End-to-end referencia
		//'E2E' => '',
		// crypto signature
		'SIG' => '',
	);

	protected $payString;

	// prideleny jedinecny kluc pre business prijemcu - urci sa pri podpise zmluvy
	protected $businessKey = 'a86388dd1edadcd5f053399d0667e561'; // 32 znakov

	protected function preparePayment(){
		/**
		* @var WebUser
		* /
		$user = Yii::app()->user;

		// set random payment data
		//$this->payData['AM'] = floor(mt_rand(2, 500)/100)*10; // 0.20 - 50 EUR
		$this->payData['AM'] = 0.5;
		//$this->payData['VS'] = $user->getVarSymbol();
		$this->payData['VS'] = 123;
		unset($this->payData['SIG']);

		// calculate signature
		$payment = $this->getPayString(); // e.g. QP:1.0*BID:IPDF-TEST*AM:0.5*VS:123*MSG:$
		$sign = sha1($payment); // 737b8032c98ad3af578855d396bb71f05a8b12df
		$sign = substr($sign, 0, 32); // prvych 16 bytes = 32 characters ASCII, 737b8032c98ad3af578855d396bb71f
		$length = openssl_cipher_iv_length(self::CYPHER); // 0 pre ECB sifry, 16 pre CBC sifry apod.
		$iv = substr($this->businessKey, 0, $length);
		$this->payData['SIG'] = openssl_encrypt($sign, self::CYPHER, '', false, $iv); // e.g. xT9+9MucOfZ6xAjJCXeXNQ505tpnFp+FZ01l3J+TE6M=

		// final payment request string
		$this->payString = $this->getPayString(); // e.g. QP:1.0*BID:IPDF-TEST*AM:0.5*VS:123*MSG:\$*SIG:xT9+9MucOfZ6xAjJCXeXNQ505tpnFp+FZ01l3J+TE6M=
	}

	/**
	* Return formated viamo payment string
	* /
	protected function getPayString(){
		$pay = array();
		foreach($this->payData as $k => $v){
			$pay[] = $k.':'.$v;
		}
		return implode('*', $pay);
	}

	/**
	* Create image for QR code created from payment query string
	* /
	public function testCreateQrCode(){
		$this->preparePayment();

		$path = Yii::getPathOfAlias('ext').'/tcpdf/tcpdf_barcodes_2d.php';
		require($path);
		$barcode = new TCPDF2DBarcode($this->payString, 'QRCODE,M');
		ob_start();
		$barcode->getBarcodePNG(4,4,array(0,0,0), false);
		$stream = ob_get_clean();
		$path = Helper::getPathOfAlias('temp').'unittest-viamo-'.date('Ymd-His').'.png';

		if($this->assertTrue(file_put_contents($path, $stream) && is_file($path) && filesize($path) > 100)){
			/**
			* @var CAssetManager
			* /
			$am = Yii::app()->assetManager;
			$url = $am->publish($path);
			echo '<img src="'.$url.'" />';
			echo '<code>'.$this->payString.'</code>';
		}
	}

	/**
	* Vytvori Viamo platbu pomocou JSON request notifikacie a presunie ju do Main payments
	*/
	public function testProcessNotificationJson(){

		// init document and processor to be paid
		$form = IpdfForm::getInstance();
		$data = $form->getFormsSortedById();
		$formId = array_rand($data);
		$data = IpdfForm::getInstance()->initByFormId($formId);

		$payment = new PaymentViamo();

		// must fail
		$res = $payment->create('', 5, ActiveRecord::PAY_PURPOSE_UNLOCK);
		$this->assertTrue($res['success']==0);
		$res = $payment->create('09034455', 0.50, ActiveRecord::PAY_PURPOSE_UNLOCK);
		$this->assertTrue($res['success']==0);
		$res = $payment->create('0900 111222333', 1.15, ActiveRecord::PAY_PURPOSE_UNLOCK);
		$this->assertTrue($res['success']==0);
		$res = $payment->create('0900111222', 0, ActiveRecord::PAY_PURPOSE_UNLOCK);
		$this->assertTrue($res['success']==0);
		$res = $payment->create('0900111222', -1, ActiveRecord::PAY_PURPOSE_UNLOCK);
		$this->assertTrue($res['success']==0);

		// create valid payment
		$phone = '0903'.mt_rand(111111, 999999);
		$amount = mt_rand(2, 500)/10;
		$res = $payment->create($phone, $amount, ActiveRecord::PAY_PURPOSE_UNLOCK);

		if($this->assertTrue(!empty($res['success']))){

			$url = Yii::app()->createAbsoluteUrl('/pay/viamo/notify' /*, array(), 'https'*/ );

			$data = array(
				'notificationId' => '1fa494a7-3933-41f9-b99f-'.mt_rand(111, 99999999999),
				'payment' => array(
					'amount' => $amount,
					'currency' => "EUR",
					'id' => "547fbb6df",
					'message' => 'unit test '.date('d.m.Y H:i:s'),
					'payer' => array('name' => 'Michal Bujna', 'phoneNumber' => $phone),
					'processedOn' => '2013-11-13T15:59:08.253+01:00',
					'recipientBusinessIdentifier' => 'ESHOP-01',
					'result' => 'OK',
					'variableSymbol' => '',
					'signature' => array('description' => 'SHA-1 UTF-8 AES/ECB/NoPadding', 'sign' => '12345'),
				),
			);
			$data = json_encode($data);

			$browser = new SimpleBrowser();
			if(!empty($_COOKIE['DBGSESSID'])){
				$browser->setCookie('DBGSESSID', $_COOKIE['DBGSESSID']);
			}

			$res = $browser->post($url, $data);
			$this->assertTrue($res == '1', 'Error: '.$res);
		}
	}


	function testPaidTooLittle(){

		$form = IpdfForm::getInstance();
		$data = $form->getFormsSortedById();
		$formId = array_rand($data);
		$data = IpdfForm::getInstance()->initByFormId($formId);

		$price = $data['price'];
		if(empty($price)){
			$price = IpdfForm::PRICE_UNLOCK_FORM_EUR;
		}

		$priceOrig = $price;
		$phone = '0903 999 '.mt_rand(100,999);

		// create expected viamo payment
		$res = PaymentViamo::model()->create($phone, $price, PaymentMain::PAY_PURPOSE_UNLOCK);

		if($this->assertTrue($res['success'] && $res['paymentId'], 'Error: '.$res['message'])){
			// sumu znizime pod hranicu akceptacie
			$price = $price - PaymentViamo::AMOUNT_TOLERANCE - 0.01;
			if($price < .01){
				$price = .01;
			}

			$price = number_format($price, 2, ',', '');

			$fields = array(
				'notificationId' => '1fa494a7-3933-41f9-b99f-'.mt_rand(111, 99999999999),
				'payment' => array(
					'amount' => $price,
					'currency' => "EUR",
					'id' => "547fbb6df",
					'message' => 'unit test '.date('d.m.Y H:i:s'),
					'payer' => array('name' => 'Michal Bujna', 'phoneNumber' => $phone),
					'processedOn' => '2013-11-13T15:59:08.253+01:00',
					'recipientBusinessIdentifier' => 'ESHOP-01',
					'result' => 'OK',
					'variableSymbol' => '',
					'signature' => array('description' => 'SHA-1 UTF-8 AES/ECB/NoPadding', 'sign' => '12345'),
				),
			);
			$fields = json_encode($fields);

			$url = Yii::app()->createAbsoluteUrl('/pay/viamo/notify' /*, array(), 'https'*/ );

			$browser = new SimpleBrowser($url);
			if(!empty($_COOKIE['DBGSESSID'])){
				$browser->setCookie('DBGSESSID', $_COOKIE['DBGSESSID']);
			}

			$res = $browser->post($url, $fields);
			// zatial nie je jasne, co musime vracat Viamo serveru - Marek Rogula neodpovedal na moj dotaz
			// vraciame 1 = notifikacia spracovana, ale interne dostava admin upozornenie
			$this->assertTrue($res == '1', 'Payment should not be accepted (expected '.$priceOrig.', received '.$price.'). Response: '.$res);
		}
	}


	function testPaidTooMuch(){

		$form = IpdfForm::getInstance();
		$data = $form->getFormsSortedById();
		$formId = array_rand($data);
		$data = IpdfForm::getInstance()->initByFormId($formId);

		$price = $data['price'];
		if(empty($price)){
			$price = IpdfForm::PRICE_UNLOCK_FORM_EUR;
		}

		$priceOrig = $price;
		$phone = '0903 999 '.mt_rand(100,999);

		// create expected viamo payment
		$res = PaymentViamo::model()->create($phone, $price, PaymentMain::PAY_PURPOSE_UNLOCK);

		if($this->assertTrue($res['success'] && $res['paymentId'], 'Error: '.$res['message'])){
			// sumu znizime pod hranicu akceptacie
			$price = $price + PaymentViamo::AMOUNT_TOLERANCE + 0.01;
			$price = number_format($price, 2, ',', '');

			$fields = array(
				'notificationId' => '1fa494a7-3933-41f9-b99f-'.mt_rand(111, 99999999999),
				'payment' => array(
					'amount' => $price,
					'currency' => "EUR",
					'id' => "547fbb6df",
					'message' => 'unit test '.date('d.m.Y H:i:s'),
					'payer' => array('name' => 'Michal Bujna', 'phoneNumber' => $phone),
					'processedOn' => '2013-11-13T15:59:08.253+01:00',
					'recipientBusinessIdentifier' => 'ESHOP-01',
					'result' => 'OK',
					'variableSymbol' => '',
					'signature' => array('description' => 'SHA-1 UTF-8 AES/ECB/NoPadding', 'sign' => '12345'),
				),
			);
			$fields = json_encode($fields);

			$url = Yii::app()->createAbsoluteUrl('/pay/viamo/notify' /*, array(), 'https'*/ );

			$browser = new SimpleBrowser($url);
			if(!empty($_COOKIE['DBGSESSID'])){
				$browser->setCookie('DBGSESSID', $_COOKIE['DBGSESSID']);
			}

			$res = $browser->post($url, $fields);
			// zatial nie je jasne, co musime vracat Viamo serveru - Marek Rogula neodpovedal na moj dotaz
			// vraciame 1 = notifikacia spracovana, ale interne dostava admin upozornenie
			$this->assertTrue($res == '1', 'Payment should not be accepted (expected '.$priceOrig.', received '.$price.'). Response: '.$res);
		}
	}

	/**
	* Otestuje platbu preposlanu z mobilu ako SMS na server prostrednictvom mobilnej aplikacie (ipdf.viamo.relay)
	* Received relay SMS - example:
		[sms] => SmsForwardViaHttp
		[from] => +421903966046
		[body] => Michal Bujna Vam poslal/a 0,1 EUR. Sprava: "pmnojktupqrs"
				  Pre prijatie platby si doplnte cislo uctu na www.viamo.sk/prijem
	*/
	/*
	function testUnlockFormByRelay(){

		// init document and processor to be paid
		//$params = Yii::app()->params;
		//$smsEnabled = !empty($params['platbaMobilom']['enabled']);

		$form = IpdfForm::getInstance();
		$data = $form->getFormsSortedById();
		$formId = array_rand($data);
		$data = IpdfForm::getInstance()->initByFormId($formId);

		$price = $data['price'];
		if(empty($price)){
			$price = IpdfForm::PRICE_UNLOCK_FORM_EUR;
		}

		$phone = '+421903999'.mt_rand(100,999);

		// create expected viamo payment
		$res = PaymentViamo::model()->create($phone, $price, PaymentMain::PAY_PURPOSE_UNLOCK);

		if($this->assertTrue($res['success'] && $res['paymentId'], 'Error: '.$res['message'])){

			/*
			if($smsEnabled){
				// check that paralel SMS payment exists
				$row = PaymentSms::model()->deleteEmptyByPhone($phone);
				$this->assertTrue(!empty($row), 'Caution - Parallel SMS payment for [phone='.$phone.', '.$price.'] was not created.');
			}
			* /

			$price = number_format($price, 1, ',', '');

			$sms = array(
				'sms' => 'SmsForwardViaHttp',
				'from' => $phone,
				'body' => 'Michal Unittester Vam poslal/a '.$price.' EUR. Sprava: "IPDF ab98"

Pre prijatie platby si doplnte cislo uctu na www.viamo.sk/prijem',
			);

			$url = Yii::app()->createAbsoluteUrl('/pay/viamo/relay', array(), 'http');
			$browser = new SimpleBrowser($url);
			if(!empty($_COOKIE['DBGSESSID'])){
				$browser->setCookie('DBGSESSID', $_COOKIE['DBGSESSID']);
			}

			$html = $browser->post($url, $sms); // fails
			$html = trim($html);
			$this->assertTrue('OK' != substr($html, 0, 2), $html);

			$sms = array(
				'sms' => 'SmsForwardViaHttp',
				'from' => $phone,
				'body' => 'Michal Unittester Vam poslal/a '.$price.' EUR. Sprava: "IPDF '.$res['hash'].'"

Pre prijatie platby si doplnte cislo uctu na www.viamo.sk/prijem',
			);

			$html = $browser->post($url, $sms);
			$html = trim($html); // OK

			if($this->assertTrue('OK' == substr($html, 0, 2), $html)){

				// preverime vytvorenie platby v PaymentMain
				// pozor - HTTP request robi HTTP bez prihlasenia usera - ziadny presun do main payment sa nedeje
				/*
				$row = PaymentMain::model()->findByAttributes(array(
					//'phone' => PaymentSms::getPhoneMsisdn($phone),
					'amount' => $price,
					'fk_form_id' => $formId,
					'payment_system' => PaymentMain::SYSTEM_VIAMO,
				));
				$this->assertTrue(empty($row), 'Caution - Payment not moved into PaymentMain table [phone='.$phone.', '.$price.' EUR].');
				*/

				// preverime, ze neexistuje paralelny SMS zaznam ocakavanej platby, ak su povolene SMS platby
				// pozor - nic spolocne so SMS platbami, museli by sme ist cez payment GUI - na to vytvorit samostatny test
				/*
				if($smsEnabled){
					$row = PaymentSms::model()->deleteEmptyByPhone($phone);
					$this->assertTrue(empty($row), 'Caution - Parallel SMS payment for [phone='.$phone.', '.$price.' EUR] was not deleted.');
				}
				* /
			}
		}
	}
	*/


	/**
	* Test ak user zaplati prilis nizku sumu
	*/
	/*
	function testPaidTooLittle(){

		$form = IpdfForm::getInstance();
		$data = $form->getFormsSortedById();
		$formId = array_rand($data);
		$data = IpdfForm::getInstance()->initByFormId($formId);

		$price = $data['price'];
		if(empty($price)){
			$price = IpdfForm::PRICE_UNLOCK_FORM_EUR;
		}

		$priceOrig = $price;
		$phone = '+421903999'.mt_rand(100,999);

		// create expected viamo payment
		$res = PaymentViamo::model()->create($phone, $price, PaymentMain::PAY_PURPOSE_UNLOCK);

		if($this->assertTrue($res['success'] && $res['paymentId'], 'Error: '.$res['message'])){
			// sumu znizime pod hranicu akceptacie
			$price = $price - PaymentViamo::AMOUNT_TOLERANCE - 0.01;
			if($price < .01){
				$price = .01;
			}

			$price = number_format($price, 2, ',', '');

			$sms = array(
				'sms' => 'SmsForwardViaHttp',
				'from' => $phone,
				// 'body' => 'Viamo test 0,2 eur platba cez relay. Xl',
				'body' => 'Viamo '.$price.' EUR. Sprava: "ipdf '.$res['hash'].'"', // SMS musi obsahovat slovo "poslal", "eur" a "Sprava". 7 znakov pred "eur" sa prefiltruje na cislo. 18 znakov za spravou sa konvertuje na PK.
			);

			//$url = Yii::app()->createAbsoluteUrl('/pay/viamo/relay');
			$url = Yii::app()->createAbsoluteUrl('/pay/viamo/relay');

			$browser = new SimpleBrowser($url);
			if(!empty($_COOKIE['DBGSESSID'])){
				$browser->setCookie('DBGSESSID', $_COOKIE['DBGSESSID']);
			}

			$html = $browser->post($url, $sms);
			$html = trim($html);
			$this->assertTrue(false !== PaymentViamo::PAY_CODE_TOO_LITTLE, 'Payment should not be accepted (expected '.$priceOrig.', received '.$price.'). Response: '.$html);
		}
	}
	*/


	/**
	* Test ak user zaplati prilis nizku sumu
	*/
	/*
	function testPaidTooMuch(){

		$form = IpdfForm::getInstance();
		$data = $form->getFormsSortedById();
		$formId = array_rand($data);
		$data = IpdfForm::getInstance()->initByFormId($formId);

		$price = $data['price'];
		if(empty($price)){
			$price = IpdfForm::PRICE_UNLOCK_FORM_EUR;
		}

		$phone = '+421903999'.mt_rand(100,999);

		// create expected viamo payment
		$res = PaymentViamo::model()->create($phone, $price, PaymentMain::PAY_PURPOSE_UNLOCK);

		if($this->assertTrue($res['success'] && $res['paymentId'], 'Error: '.$res['message'])){
			// sumu znizime pod hranicu akceptacie
			$price = $price + PaymentViamo::AMOUNT_TOLERANCE + 0.01;
			$price = number_format($price, 2, ',', '');

			$sms = array(
				'sms' => 'SmsForwardViaHttp',
				'from' => $phone,
				// 'body' => 'Viamo test 0,2 eur platba cez relay. Xl',
				'body' => 'Niekto vam poslal '.$price.' EUR. Sprava: "ipdf '.$res['hash'].'"\n\nZaregistrujet sa blah blah..', // SMS musi obsahovat slovo "viamo" a "eur". 7 znakov pred "eur" sa prefiltruje na cislo.
			);

			//$url = Yii::app()->createAbsoluteUrl('/pay/viamo/relay');
			$url = Yii::app()->createAbsoluteUrl('/pay/viamo/relay');
			$browser = new SimpleBrowser($url);
			if(!empty($_COOKIE['DBGSESSID'])){
				$browser->setCookie('DBGSESSID', $_COOKIE['DBGSESSID']);
			}

			$html = $browser->post($url, $sms);
			$html = trim($html);
			$this->assertTrue('OK' == substr($html, 0, 2), 'Payment too high should be accepted. Error: '.$html);
		}
	}
	*/

	/**
	* Test viamo hash codes
	*/
	/*
	public function testViamoHash(){
		for($i=1; $i<1000; $i+=25){
			$hash = PaymentViamo::getHash($i);
			$int = PaymentViamo::getUnhash($hash);
			$this->assertTrue($i == $int);
		}
		for($i=1000; $i<1000000; $i=$i+mt_rand(3000, 8000)){
			$hash = PaymentViamo::getHash($i);
			$int = PaymentViamo::getUnhash($hash);
			$this->assertTrue($i == $int);
		}
	}
	*/

}
