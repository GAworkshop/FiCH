<?

	require_once('DBConnect.php');
	$t = new DBConnect();

	$name = $_POST['name'];
/*
	$result = $t->raw_query('SELECT * FROM `member` WHERE 1');
	
	foreach($result as $row){
		foreach($row as $col){
			echo $col;
			echo "#c#";
		}
		echo "#r#";
	}

	echo "<br>";
*/
	echo $t->queryJSON('SELECT * FROM `testtable`');


