<?
	/*
		[Error codes]
			the program will die with the these codes and their meanings are as shown:

			101 : 
			102 : 


		
		
		
		
		
	*/



	class DBConnect{

		private $connect;
		private $dbhost = "127.0.0.1";
		private	$user = 'ga';
		private	$pass = 'gagaga';
		private	$dbname = 'fich';
		
		function __construct($dbhost = "127.0.0.1", $user = 'ga', $pass = 'gagaga', $dbname = 'fich'){

			//echo "object created<br>";

			//development default
			$this->dbhost = $dbhost;
			$this->user = $user;
			$this->pass = $pass;
			$this->dbname = $dbname;

			$this->connect = mysql_connect($this->dbhost, $this->user, $this->pass) or die ('Error connecting~');
			
			mysql_select_db($dbname) or die ('Error selecting db~');


			//$query = 'INSERT INTO `testtable` (`id`, `x`, `y`, `z`, `time`) VALUES (NULL, "'.$x.'", "'.$y.'", "'.$z.'", NULL);';
			//$result = mysql_query($query) or die ('select query error');

		    //mysql_close($connect);
		}

		function count($query = 'SELECT count(*) FROM `testtable`'){
			$result = mysql_query($query) or die ('get count error<br>');
			return intval(mysql_fetch_array($result, MYSQL_NUM)[0])."<br>";
		}

		function insert($query = 'INSERT INTO `testtable` (`id`, `x`, `y`, `z`, `time`) VALUES (NULL, "0.87", "0.87", "0.87", NULL);'){
			//$query = 'INSERT INTO `testtable` (`id`, `x`, `y`, `z`, `time`) VALUES (NULL, "0.87", "0.87", "0.87", NULL);';
			$result = mysql_query($query) or die ('query error<br>');
		}

		function raw_query($query = 'INSERT INTO `testtable` (`id`, `x`, `y`, `z`, `time`) VALUES (NULL, "0.87", "0.87", "0.87", NULL);'){
			
			$result = mysql_query($query) or die ('query error<br>');


			try{
				//result reading and transform to output array
				$output = array();
				while($row = mysql_fetch_array($result, MYSQL_NUM)){
					$temp = array();
					foreach ($row as $per) {
						array_push($temp, $per);
					}
					array_push($output, $temp);
					$temp = null;
			    }
				return $output;
			}catch(Exception $e){
				return $result;
			
			}
			
		}

		function queryJSON($query = 'SELECT * FROM `testtable`'){
			$result = mysql_query($query) or die ('query error<br>');
			$rows = array();
			while($r = mysql_fetch_row($result)) {
			    $rows[] = $r;
			}
			return json_encode($rows);
		}



		function __destruct(){
			if(!$this->connect){
				//echo "connect doesn't exist";
			}else{
				mysql_close($this->connect);
				//echo "connection closed";
			}
		}
	}

	

	

