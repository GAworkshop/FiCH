<?
	/*
	calling parameters:

		action :
			"create_loc" : create a location record
			"select_loc" : return location data
			"create_heart" : create a heart record
			"select_heart" : return heart data

		id :
			insert or select under a given id

		"data" :
			data in JSON format
			ex:(loc)
			[
				[longitude, latitude, time],
				.
				.
				.
				[......]
			]

			ex:(heart)
			[
				[heart-beat-rate, time]
				.
				.
				.
				[....]
			]
		"from" :
			select records newer than a given time-stamp
			ex:
			'20160802213000' = 2016-08-02 21:30:00
		
		"to" :
			select records older thar a given time-stamp

		"newest" : (number)
			select the newest n records

	select returns :
		"newest" :
			return the newest n records, the returned JSON array is in REVERSED order(decreasing)
	

	return codes :



		501 : error
		700 : successs

	*/



	require_once('DBConnect.php');
	$t = new DBConnect();

	$create_loc = 'create_loc';
	$select_loc = 'select_loc';
	$create_heart = 'create_heart';
	$select_heart = 'select_heart';

	
	//$_POST['action'] = $create_heart;
	//$_POST['data'] = '[[50,0]]';
	//$_POST['newest'] = '7';
	

	switch($_POST['action']){
		case $create_loc:
		case $create_heart:
			createData($_POST['action']);
			break;

		case $select_loc:
		case $select_heart:
			selectNewestData($_POST['action']);
			break;

		default:
			exitCode(501);
	}

	function createData($type){
		global $t;
		$id = $_POST['id'];

		try {
			$schema;
			$fields;
			if($type == 'create_loc'){
				$schema = 'loc_';
				$fields = '`longitude`, `latitude`, `time`';
			}else{
				$schema = 'heart_';
				$fields = '`heart`, `time`';
			}

			$data = $_POST['data'];
			$sql = 'INSERT INTO `'.$schema.$id.'` ('.$fields.') VALUES ';
			//directly change JSON to SQL query string
			$data = substr($data, 1, -1);				//remove head and tail [] in JSON data
			$data = str_replace('[', '(', $data);		//replace [ to ( in JSON data
			$data = str_replace(']', ')', $data);		//replace ] to ) in JSON data
			$data = str_replace('#', '"', $data);
			$sql .= $data;


		}catch(Exception $e){
			exitCode(501);
		}
		$t->insert($sql);
		exitCode(700);
	}

	function selectNewestData($type){
		global $t;
		$id = $_POST['id'];

		try{
			$n = intval($_POST['newest']);
		
			if($n == 0){
				exitCode(501);
			}

			$schema;
			if($type == 'select_loc'){
				$schema = 'loc_';
			}else{
				$schema = 'heart_';
			}

			$sql = 'SELECT * FROM `'.$schema.$id.'` ORDER BY `time` DESC LIMIT '.$n;
			//echo $sql;
			
			echo $t->queryJSON($sql);

		}catch(Exception $e){
			exitCode(501);
		}

		
	}

	function exitCode($code = 501){
		die(''.$code);
	}