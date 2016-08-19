<?	

	require_once 'MyMailer.php';
	require_once('registerWeb/MailDilivery.html');
	session_start();
	
	$p = "lost stars";
	//echo "<link rel='stylesheet' href='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css'>";
	
	
	if($_POST['pw'] == $p){
		DBconnect();
	}else{
		echo "
		<form action='regConfig.php' method='post'>
		<input type='password' name='pw' class='form-control' style='max-width:60%; margin:auto; margin-top:20%; text-align:center' autofocus>
		<input class='btn btn-lg btn-primary btn-block' type='submit' value='Sign In' style='max-width:20%; margin:auto; margin-top:4%'/>
		</form>
		";
	}
	

	
	//echo "<script>displayTableDataRow(['aaa','bbb','ccc','ddd'],true);</script>";
	//JSdisplayTableDataRow(table, array("aaa","bbb","ccc","eeee"), true);


	//startMailing();
	///DBconnect();

	function JSdisplayTableDataRow($place, $array = array(), $checked = true){
		
		$a = "[";
		foreach ($array as $each) {
			$a .= "'".$each."',";
		}
		$a .= "],".($checked ? "true" : "false");
		echo "<script>displayTableDataRow('".$place."',".$a.");</script>";
	}

	function JSsetText($id, $text){
		echo "<script>setText('".$id."','".$text."');</script>";
	}

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
		//echo "<h2>個人報名人數: ".mysql_num_rows($result)."</h2>";
		JSsetText("topic1", "個人報名人數: ".mysql_num_rows($result));
		array2DPrint("table1", $result);
		

	    $query = 'SELECT * FROM `group_data`';
	    $result = mysql_query($query) or die ('select query error');
	    //echo "<br><h2>接力隊數: ".mysql_num_rows($result)."</h2>";
	    JSsetText("topic2", "接力隊數: ".mysql_num_rows($result));
		array2DPrint("table2", $result);

		echo "</div>";

		

	    mysql_free_result($result);
		mysql_close($connect);
	}

	function array2DPrint($place = "table1", $result){
		/*
		echo "<div class='table-responsive'>";
		echo "<table class='table table-hover'>";
		echo "<tr>";
		while($field = mysql_fetch_field($result)){
			echo "<th>".$field->name."</th>";
		}
		echo "</tr>";
		*/
		while($row = mysql_fetch_array($result, MYSQL_NUM)){

			/*
			echo "<tr>";
			echo "<td><div class='' data-toggle='buttons'><label class='btn btn-primary'><input class='check' type='checkbox' autocomplete='off'><span class='glyphicon glyphicon-ok' aria-hidden='true'></span></label></div></td>";
			foreach ($row as $col) {
				echo "<td>".$col."</td>";
			}
	        echo "</tr>";
			
	        */
	        JSdisplayTableDataRow($place, $row, true);
	    }
	    echo "</table>";
	    echo "</div>";
		

	}



?>