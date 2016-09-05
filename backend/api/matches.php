<?
	/*
	calling parameters:

		action :
			"create" : create a new match of wear and a person
			"select" : select pairs
			"auth" : makes a certain match avaliable
			"unauth" disable a certain match


		"wear_id" : id of wearer, one account one wear

		"family_id" : id of an account connected to wear account



	select returns:

		[
			[wear_id, family_id],
			.
			.
			.
			.
			.
		]

	return codes:

		600 : insert success


	
	*/



	require_once('DBConnect.php');
	$t = new DBConnect();

	$create = 'create';
	$select = 'select';
	$auth = 'auth';
	$unauth = 'unauth';


	$_POST['action'] = $unauth;
	$_POST['wear_id'] = '5';
	$_POST['family_id'] = '6';


	switch($_POST['action']){
		case $create:
			createData();
			break;

		case $select:
			selectPairs();
			break;

		case $auth:
			auth();
			break;

		case $unauth:
			auth(0);
			break;

		default:
			exitCode(501);
	}


	function createData(){
		global $t;

		$wear_id = $_POST['wear_id'];
		$family_id = $_POST['family_id'];

		try {
			$sql = 'INSERT INTO `matches` (`wear_id`, `family_id`) VALUES ("'.$wear_id.'", "'.$family_id.'");';

		}catch(Exception $e){
			exitCode(501);
		}
		$t->insert($sql);
		exitCode(600);
	
	}

	function selectPairs(){
		global $t;

		$sql = 'SELECT `wear_id`, `family_id` FROM `matches` WHERE (';
		if($_POST['wear_id']){
			$sql .= '`wear_id` = '.$_POST['wear_id'];
		}else{
			$sql .= '1';
		}

		if($_POST['family_id']){
			$sql .= ' AND `family_id` = '.$_POST['family_id'].')';
		}else{
			$sql .= ' AND 1)';
		}

		$sql .= ' AND `access` = 1';

		echo $t->queryJSON($sql);
	}

	function auth($a = '1'){
		global $t;

		$wear_id = $_POST['wear_id'];
		$family_id = $_POST['family_id'];

		$sql = 'UPDATE `matches` SET `access` = "'.$a.'" WHERE `wear_id` = "'.$wear_id.'" AND `family_id` = "'.$family_id.'"';
		echo $t->raw_query($sql);
	}




	function exitCode($code = 501){
		die(''.$code);
	}