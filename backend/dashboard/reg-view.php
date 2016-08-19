<?	

	session_start();
	
	$p = "lost stars";
	echo "<link rel='stylesheet' href='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css'>";
	
	
	if($_POST['pw'] == $p){
		DBconnect();
	}else{
		echo "
		<form action='reg-view.php' method='post'>
		<input type='password' name='pw' class='form-control' style='max-width:60%; margin:auto; margin-top:20%; text-align:center' autofocus>
		<input class='btn btn-lg btn-primary btn-block' type='submit' value='Sign In' style='max-width:20%; margin:auto; margin-top:4%'/>
		</form>
		";
	}
	

	///DBconnect();

	function DBconnect(){


		$dbhost = "127.0.0.1";
		$dbuser = 'registerU';
		$dbpass = 'registeradmin2016';

		$connect = mysql_connect($dbhost, $dbuser, $dbpass) or die ('Error connecting~~~~~~');
		
		$dbname = 'register';

		mysql_select_db($dbname);

	
		echo "<div class='container'>";
		
		$query = 'SELECT * FROM `register_data`';
		$result = mysql_query($query) or die ('select query error');
		echo "<h2>個人報名人數: ".mysql_num_rows($result)."</h2>";
		array2DPrint($result);
		

	    $query = 'SELECT * FROM `group_data`';
	    $result = mysql_query($query) or die ('select query error');
	    echo "<br><h2>接力隊數: ".mysql_num_rows($result)."</h2>";
		array2DPrint($result);

		echo "</div>";

		

	    mysql_free_result($result);
		mysql_close($connect);
	}

	function array2DPrint($result){
		echo "<div class='table-responsive'>";
		echo "<table class='table table-hover'>";
		echo "<tr>";
		while($field = mysql_fetch_field($result)){
			echo "<th>".$field->name."</th>";
		}
		echo "</tr>";
		while($row = mysql_fetch_array($result, MYSQL_NUM)){

			echo "<tr>";
			foreach ($row as $col) {
				echo "<td>".$col."</td>";
			}
	        echo "</tr>";
	    }
	    echo "</table>";
	    echo "</div>";
	}



?>