<?
	/*
	calling parameters:

		action :
			"create" : create a record
			"select" : return sensor data

		"data" :
			data in JSON format
			ex:
			[
				[ax, ay, az, rx, ry, rz],
				.
				.
				.
				[......]
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

	*/

	require_once('DBConnect.php');
	$t = new DBConnect();

	$create = 'create';
	$select = 'select';

	/*
	$_POST['action'] = $select;
	$_POST['data'] = '[[-34.551,150.521,72],[55.8456,-8.5584,120],[0.1548,179.255,5]]';
	$_POST['newest'] = '7';
	*/

	switch($_POST['action']){
		case $create:
			createData();
			break;

		case $select:
			selectNewestData();
			break;

		default:
			exitCode(501);
	}

	function createData(){
		global $t;

		try {
			$data = $_POST['data'];
			$sql = 'INSERT INTO `sensors` (`ax`, `ay`, `az`, `rx`, `ry`, `rz`) VALUES ';
			//directly change JSON to SQL query string
			$data = substr($data, 1, -1);				//remove head and tail [] in JSON data
			$data = str_replace('[', '(', $data);		//replace [ to ( in JSON data
			$data = str_replace(']', ')', $data);		//replace ] to ) in JSON data
			$sql .= $data;

		}catch(Exception $e){
			exitCode(501);
		}
		$t->insert($sql);
	}

	function selectNewestData(){
		global $t;

		try{
			$n = intval($_POST['newest']);
		}catch(Exception $e){
			exitCode(501);
		}

		if($n == 0){
			exitCode(501);
		}

		$sql = 'SELECT * FROM `sensors` ORDER BY `time` DESC LIMIT '.$n;
		//echo $sql;
		
		echo $t->queryJSON($sql);
	}

	function exitCode($code = 501){
		die(''.$code);
	}



