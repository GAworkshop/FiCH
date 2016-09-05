<?
	/*
		[Error codes]
			the program will die with the these codes and their meanings are as shown:

			101 : raw query error
			102 : query JSON error
			103 : get count error
			104 : insert error


			901 : can't connect to database
			902 : error select db
				
		
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

			$this->connect = mysql_connect($this->dbhost, $this->user, $this->pass) or die ('901');
			
			mysql_select_db($dbname) or die ('902');


			//$query = 'INSERT INTO `testtable` (`id`, `x`, `y`, `z`, `time`) VALUES (NULL, "'.$x.'", "'.$y.'", "'.$z.'", NULL);';
			//$result = mysql_query($query) or die ('select query error');

		    //mysql_close($connect);
		}

		function count($query = 'SELECT count(*) FROM `testtable`'){
			$result = mysql_query($query) or die ('103');
			return intval(mysql_fetch_array($result, MYSQL_NUM)[0])."<br>";
		}

		function insert($query = 'INSERT INTO `testtable` (`id`, `x`, `y`, `z`, `time`) VALUES (NULL, "0.87", "0.87", "0.87", NULL);'){
			//$query = 'INSERT INTO `testtable` (`id`, `x`, `y`, `z`, `time`) VALUES (NULL, "0.87", "0.87", "0.87", NULL);';
			$result = mysql_query($query) or die ('104');
		}

		function raw_query($query = 'INSERT INTO `testtable` (`id`, `x`, `y`, `z`, `time`) VALUES (NULL, "0.87", "0.87", "0.87", NULL);'){
			
			$result = mysql_query($query) or die ('101');


			if(is_resource($result)){
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
			}else{
				return $result;
			}

			
			
		}

		function queryJSON($query = 'SELECT * FROM `testtable`'){
			$result = mysql_query($query) or die ('102');
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

	

	

