<?php
/**
* $Id: TestDatabase.php 354 2013-11-29 13:16:36Z admin $
* Database unit tests
*/
class TestDatabase extends WebTestCase{

	function setUp(){}

	/**
	* Testuje CRUD API AR nad databazou
	*/
	public function testCrudActiveRecordYii(){
		// testujeme nad hociktorou tabulka - data z nej budu zmazane

		$ret = array(
			'message' => '',
			'title' => Yii::t('main','Test database permissions (CRUD)'),
		);

		// set up dummy AR model table
		$model = LogModel::model();
		$model->unsetAttributes();

		$model->user = 'simpletest';
		$model->level = CLogger::LEVEL_PROFILE;
		$model->category = 'unittest';
		$model->logtime = time();
		$model->message = 'CRUD test insert ['.date('d.m.Y H:i:s').'].'; // varchar(50)

		$model->setIsNewRecord(true);

		// test INSERT
		if($model->save()){
			// test update
			$model->message .= 'Updated!';
			if($model->save()){
				// test select
				$row = $model->findByAttributes(array('message' => $model->message));
				if(!empty($row)){
					// test delete
					if($model->deleteByPk($model->id)){
						// verify delete
						$row = $model->findByPk($model->id);
						if(!empty($row)){
							// row may not exist at this point
							$ret['message'] = Yii::t('main','Failed deleting record from database! Please set *delete* permissions for all tables.');
						}else{
							// remove any record having *CRUD insert*
							$deleted = $model->deleteAll(array(
								'condition' => $model->quote('message').' LIKE :pattern',
								'params' => array(':pattern' => 'CRUD test-%'),
							));
						}
					}else{
						$ret['message'] = Yii::t('main','Failed deleting record from database! Please set *delete* permissions for all tables.');
					}
				}else{
					$ret['message'] = Yii::t('main','Failed selecting record from database! Please set *select* permissions for all tables.');
				}
			}else{
				$ret['message'] = Yii::t('main','Failed updating record in database! Please set *update* permissions for all tables.');
			}
		}else{
			$ret['message'] = Yii::t('main','Failed writing into database! Please set *writing* permissions for all tables.');
		}

		$ret['success'] = empty($ret['message']);
		$this->assertTrue($ret['success'], 'CRUD test: '.$ret['message']);
	}


}
