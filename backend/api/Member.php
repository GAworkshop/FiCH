<?
	/*
	post calling parameters:

		action :
			"create" : create user
			"delete" : delete user(soft delete)
			"update" : update user info
			"select" : return user entry
			"login"  : user login
			"check"  : check database status

		"name" :

		"pass" :

		"phone" :

		"id" :

	select returns :



	retrun codes:

		100 : database ok
		200 : user login success
		201 : user login fail
		300 : user create success
		301 : user exists
		401 : user not found
		501 : error


	*/

	require_once('DBConnect.php');
	$t = new DBConnect();

	$create = 'create';
	$delete = 'delete';
	$update = 'update';
	$select = 'select';
	$login  = 'login';

/*
	$_POST['action'] = $login;
	$name = $_POST['name'] = 'n3@nn.nn';
	$pass = $_POST['pass'] = 'pppp';
	$phone = $_POST['phone'] = '011111111';
*/

	switch ($_POST['action']) {
		case $create:
			createUser();
			break;

		case $delete:
			deleteUser();
			break;

		case $update:
			updateUser();
			break;

		case $select:
			selectUser();
			break;

		case $login:
			login();
			break;

		default:
			die(501);
			break;
	}



	function createUser(){
		global $t;
		$name = $_POST['name'];
		$pass = $_POST['pass'];
		$phone = $_POST['phone'];
		#....and other attributes....
		if(isUserExist($name)){
			exitCode(301);
		}else{
			$t->insert('INSERT INTO `member` (`user_name`, `user_pass`, `phone`) VALUES ("'.$name.'", "'.$pass.'", "'.$phone.'");');
			$last_id = mysql_insert_id();
			createUserTable($last_id);
			exitCode(300);
		}

	}

	function deleteUser(){
		global $t;
	}

	function updateUser(){
		global $t;
	}

	function selectUser(){
		global $t;
		if($_POST['id']){
			$id = $_POST['id'];
			echo $t->queryJSON('SELECT * From `member` WHERE `id` = "'.$id.'";');
			return;
		}
		if($_POST['name']){
			$name = $_POST['name'];
			if($name == "*"){
				echo $t->queryJSON('SELECT * From `member`;');
				return;
			}
			echo $t->queryJSON('SELECT * From `member` WHERE `user_name` = "'.$name.'";');
			return;
		}

		exitCode(501);
	}

	function login(){
		global $t;
		$name = $_POST['name'];
		$pass = $_POST['pass'];

		$result = $t->count('SELECT count(*) FROM `member` WHERE `user_name` = "' . $name . '" AND `user_pass` = "' .$pass. '";');


		if($result >= 1){
			echo $t ->queryJSON('SELECT * FROM `member` WHERE `user_name` = "' . $name . '" AND `user_pass` = "' .$pass. '";');
			die();
		}

		exitCode(201);
	}

	

	function isUserExist($name = ''){
		global $t;
		$result = $t->count('SELECT count(*) FROM `member` WHERE `user_name` = "'.$name.'";');
		if($result >= 1){
			//echo "existed<br>";
			return true;
		}
		//echo "free<br>";
		return false;
	}

	function createUserTable($id){
		global $t;
		// $t->count('SELECT count(*) FROM `member` WHERE `user_name` = "'.$name.'";');
		$t->raw_query('CREATE TABLE `fich`.`loc_heart_'.$id.'` ( `id` INT NOT NULL AUTO_INCREMENT , `longitude` FLOAT NOT NULL , `latitude` FLOAT NOT NULL , `heart` INT NULL , `time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , PRIMARY KEY (`id`)) ENGINE = InnoDB');
	}

	function exitCode($code = 501){
		die(''.$code);
	}
