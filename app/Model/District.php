<?php
class District extends AppModel{
	public $useTable = 'districts';


	public $hasMany = array(
		'Ward' => array(
			'className' => 'Ward',
			'conditions' => array('Ward.del_flag' => 0,),
			'order' => array('Ward.name ASC')
		)
	);

	public $belongsTo = array(
		'City' => array(
			'className' => 'City',
			'foreignKey' => 'city_id',
			//'type' => 'inner',
		)
	);


	public function getDistrictByCityId($city_id, $fields=array()){
		//when recursive<>-1, Cake will join District with City for get result in District and City

		if(empty($fields)){
			$fields = array(
				"District.id","District.name","District.slug",
				"City.id","City.name","City.slug"
			);
		}
		$options = array(
			"fields" => $fields,
			"conditions" => array("city_id" => $city_id)
		);

		return $this->find("all", $options);
	}
	
	public function getDistrictInfoList($need_value_default, $city_id_list=array(), $district_id_list=array(), $fields=array()){
		$result = array();
		$conditions = array(
			"del_flag" => 0,
		);
		if(!empty($city_id_list)){
			$conditions["city_id"] = $city_id_list;
		}
		if(!empty($district_id_list)){
			$conditions["id"] = $district_id_list;
		}

		$col_name = "";
		if(count($fields) == 1){
			foreach($fields as $val){
				$col_name = $val;
			}
			if($col_name != "id"){
				$fields[] = "id";
			}
		}
		
		if(empty($fields)){
			$fields = array("id","name");
			$col_name = "name";
		}
		$data_temp = $this->find("all", array(
			"fields" => $fields,
			"conditions" => $conditions,
			"order" => array("name ASC"),
		));
		
		if(!empty($need_value_default)){
			$result[0] = "Chọn Quận/Huyện";
		}
		
		if(!empty($data_temp)){
			if(!empty($col_name)){
				foreach($data_temp as $row){
					$result[$row["District"]["id"]] = $row["District"][$col_name];
				}				
			} else {
				foreach($data_temp as $row){
					$result[$row["District"]["id"]] = $row["District"];
				}			
			}
			$data_temp = null;
			unset($data_temp);
		}

		return $result;
	}
	public function getDistrictInfoListKeyAuto($need_value_default, $conditions=array(), $fields=array()){
		$result = array();
		$options = array(
			"order" => array("name ASC"),		
		);
		if(!empty($conditions)){
			$options["conditions"] = $conditions;
		}

		if(!empty($fields)){
			$options["fields"] = $fields;
		}
		
		$data_temp = $this->find("all", $options);
		if(!empty($need_value_default)){
			$result[] = array(
				"id" => 0,
				"name" => "Chọn Quận/Huyện",
			);
		}
		
		if(!empty($data_temp)){
			foreach($data_temp as $row){
				$result[] = $row["District"];
			}
			$data_temp = null;
			unset($data_temp);
		}

		return $result;
	}
}
