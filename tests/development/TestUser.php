<?php
/**
* $Id: TestUser.php 306 2013-11-13 20:31:17Z admin $
*/
class TestUser extends UnitTestCase{

	public function testVariableSymbol(){
		/**
		* @var WebUSer
		*/
		$user = Yii::app()->user;
		$failed = $total = 0;

		// low user IDs
		for($uid = 1; $uid<10000; $uid+=101){
			++$total;
			$vs = $user->getVarSymbol($uid, false);
			$decodedUid = $user->getUserIdByVarSymbol($vs);
			$this->assertTrue($decodedUid == $uid, 'Failed low VS check: uid ['.$uid.'] => decoded as ['.$decodedUid.']');

			// negativny test vzdy zlyhava v urcitom pocte pripadov, nakolko nahodne sa nespravne vyhodnoti VS ako korektny
			// neprisiel som na sposob, ako s jednym kontrolnym cislom garantovat 100% validaciu - je to ako brute force attack -
			// velkym poctom nahodnych kombinacii sa najde percento pripadov, ktore prejdu kontrolnymi podmienkami
			// riesenim by boli prvocisla alebo VS dlhsie ako 10 cislic...
			$isValid = WebUser::isVarSymbolValid(substr($vs, 0, -1));
			if($isValid){
				++$failed;
			}
			// zakomentovany - assert je na konci
			//$this->assertFalse($isValid, 'Failed low VS check *forgot last digit* : VS ['.$vs.'] must be invalid.');
			$isValid = WebUser::isVarSymbolValid($vs+20);
			if($isValid){
				++$failed;
			}
			$this->assertFalse($isValid, 'Failed low VS check *added +20* : VS ['.$vs.'] must be invalid.');

		}

		// high user IDs
		for($uid = 1000000; $uid<99999999; $uid+=500001){
			++$total;
			$vs = $user->getVarSymbol($uid, false);
			if($vs == '125000030'){ // 125000030 vracia mod 11 = 0  ... nespravne, nahoda
				$x = 1;
			}
			$decodedUid = $user->getUserIdByVarSymbol($vs);
			$this->assertTrue($decodedUid == $uid, 'Failed high VS check: uid ['.$uid.'] => decoded as ['.$decodedUid.']');
			$isValid = WebUser::isVarSymbolValid(substr($vs, 0, -1));
			if($isValid){
				++$failed;
			}
			// zakomentovany - assert je na konci
			//$this->assertFalse($isValid, 'Failed high VS check *forgot last digit* : VS ['.$vs.'] must be invalid.');
			$isValid = WebUser::isVarSymbolValid($vs+20);
			if($isValid){
				++$failed;
			}
			$this->assertFalse($isValid, 'Failed high VS check *added +20* : VS ['.$vs.'] must be invalid.');
		}

		$this->assertTrue($failed <= 3, 'Exceeded maximum allowed fails '.$failed.' > 3.');

		// Effectiveness of VS checking algorithm: 98.99 % (failed 3 out of 297 variable symbols).
		// If 5% of 1000 payments have wrong VS, then 1 payments will be falsely evaluated as correct.
		if($failed){
			$eff = 100 - round($failed / $total * 100, 2);
			echo '<hr/><strong>Effectiveness of VS checking algorithm: '.number_format($eff, 2).' % (failed '.$failed.' out of '.$total.' variable symbols).<br/>If 5% of 1000 payments have wrong VS, then '.ceil(1000 * 0.05 * (1-$eff/100)).' payments will be falsely evaluated as correct.</strong>';
		}
	}

}
