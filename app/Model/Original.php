<?php
class Original extends AppModel {
	public $useTable = 'originals';

	public $validate = [

	];

	public $hasMany = [
		'Tran' => [
			'className' => 'Tran',
			'order' => ['Tran.original_id ASC']
		],
		'Comparision' => [
			'className' => 'Comparision',
			'order' => ['Comparision.original_id ASC']
		]
	];


	public function getOriginalById($id){
		return $this->find('first', [
			'conditions' => [
				'Original.id' => $id,
			]
		]);
	}

	public function searchWord($textSearch){
		if(!empty($textSearch)){
			return $this->find('first', [
				'conditions' => [
					'Original.original_text' => $textSearch,
				]
			]);
		}
		return [];
	}

	public function searchWordForSuggestion($textSearch){
		if(!empty($textSearch)){
			return $this->find('first', [
				'conditions' => [
					'Original.original_text' => $textSearch,
				]
			]);
		}
		return [];
	}

}
