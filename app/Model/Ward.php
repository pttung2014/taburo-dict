<?php
class Ward extends AppModel{
	public $useTable = 'wards';

	public $belongsTo = array(
		'District' => array(
			'className' => 'District',
			'foreignKey' => 'district_id',
			//'type' => 'inner',
		)
	);
}
