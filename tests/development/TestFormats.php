<?php
/**
* $Id$
*/
class TestFormats extends UnitTestCase{

	/**
	* Testuje formaty VAT cisel pre krajiny EU
	*/
	public function testEuVatNumber(){

		$this->assertTrue(IpdfValidator::isValidEuVat('AT U12345678', 'AT'));
		$this->assertTrue(IpdfValidator::isValidEuVat('At U 1234,56.78', 'AT'));
		$this->assertTrue(IpdfValidator::isValidEuVat('ATU12345678', 'AT'));
		$this->assertFalse(IpdfValidator::isValidEuVat('AT912345678', 'AT'));
		$this->assertTrue(IpdfValidator::isValidEuVat('BE0999998888', 'BE'));
		$this->assertTrue(IpdfValidator::isValidEuVat('BG123456789', 'BG'));
		$this->assertTrue(IpdfValidator::isValidEuVat('CHE123456789', 'CHE'));
		$this->assertTrue(IpdfValidator::isValidEuVat('CY01234567Z', 'CY'));
		$this->assertTrue(IpdfValidator::isValidEuVat('CZ1234567890', 'CZ'));
		$this->assertTrue(IpdfValidator::isValidEuVat('DE123456789', 'DE'));
		$this->assertFalse(IpdfValidator::isValidEuVat('DE023456789', 'DE'));
		$this->assertTrue(IpdfValidator::isValidEuVat('DK12345678', 'DK'));
		$this->assertTrue(IpdfValidator::isValidEuVat('EE101234567', 'EE'));
		$this->assertTrue(IpdfValidator::isValidEuVat('EL 123456789', 'EL'));
		$this->assertTrue(IpdfValidator::isValidEuVat('ES A12345678', 'ES'));
		$this->assertTrue(IpdfValidator::isValidEuVat('ES A1234567J', 'ES'));
		$this->assertFalse(IpdfValidator::isValidEuVat('ES A1234567Z', 'ES'));
		$this->assertTrue(IpdfValidator::isValidEuVat('EU123456798', 'EU'));
		$this->assertTrue(IpdfValidator::isValidEuVat('FI.12345678', 'FI'));
		$this->assertTrue(IpdfValidator::isValidEuVat('FR 12345678901', 'FR'));
		$this->assertTrue(IpdfValidator::isValidEuVat('GB 123456789', 'GB'));
		$this->assertTrue(IpdfValidator::isValidEuVat('GB 123456789012', 'GB'));
		$this->assertFalse(IpdfValidator::isValidEuVat('GB 1234567890', 'GB'));
		$this->assertTrue(IpdfValidator::isValidEuVat('HR 12345678901', 'HR'));
		$this->assertTrue(IpdfValidator::isValidEuVat('hu12345678', 'HU'));
		$this->assertTrue(IpdfValidator::isValidEuVat('IE1234567A', 'IE'));
		$this->assertTrue(IpdfValidator::isValidEuVat('IT12345678901', 'IT'));
		$this->assertTrue(IpdfValidator::isValidEuVat('LV12345678901', 'LV'));
		$this->assertTrue(IpdfValidator::isValidEuVat('LT 123456789', 'LT'));
		$this->assertFalse(IpdfValidator::isValidEuVat('LT 1234567890', 'LT'));
		$this->assertTrue(IpdfValidator::isValidEuVat('LT 123456789012', 'LT'));
		$this->assertTrue(IpdfValidator::isValidEuVat('LU12345678', 'LU'));
		$this->assertFalse(IpdfValidator::isValidEuVat('MT02345678', 'MT'));
		$this->assertTrue(IpdfValidator::isValidEuVat('MT12345678', 'MT'));
		$this->assertTrue(IpdfValidator::isValidEuVat('NL123456789B12', 'NL'));
		$this->assertTrue(IpdfValidator::isValidEuVat('NO123456789', 'NO'));
		$this->assertTrue(IpdfValidator::isValidEuVat('PL1234567891', 'PL'));
		$this->assertTrue(IpdfValidator::isValidEuVat('PT 123456789', 'PT'));
		$this->assertTrue(IpdfValidator::isValidEuVat('RO 1234567890', 'RO'));
		$this->assertTrue(IpdfValidator::isValidEuVat('RS 123456789', 'RS'));
		$this->assertTrue(IpdfValidator::isValidEuVat('SI 11234567', 'SI'));
		$this->assertTrue(IpdfValidator::isValidEuVat('SK 1221234567', 'SK'));
		$this->assertTrue(IpdfValidator::isValidEuVat('SE 123456789001', 'SE'));
	}

}
