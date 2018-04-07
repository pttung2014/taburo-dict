<?php
class City extends AppModel {
	public $useTable = 'cities';

	public $hasMany = array(
		'District' => array(
			'className' => 'District',
			'conditions' => array('District.del_flag' => 0),
			'order' => array('District.name ASC')
		)
	);

	public function getCityById($id){
		return $this->find('first', array(
			'conditions' => array('id' => $id)
		));
	}
}
