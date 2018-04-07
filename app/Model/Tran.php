<?php
class Tran extends AppModel{
	public $useTable = 'trans';

	public $belongsTo = [
		'Original' => [
			'className' => 'Original',
			'foreignKey' => 'original_id',
		]
	];

	public function getTransByOriginalId($originalId){
		return $this->find('all', [
			'conditions' => [
				'Tran.original_id' => $originalId,
			]
		]);
	}

}
