<?
	require_once('DBConnect.php');

	$t = new DBConnect();
	//$t->insert('INSERT INTO `testtable` (`id`, `x`, `y`, `z`, `time`) VALUES (NULL, "0.77", "0.77", "0.77", NULL);');

	$x = $_POST['x'];
	$y = $_POST['y'];
	$z = $_POST['z'];

	$t->insert('INSERT INTO `testtable` (`id`, `x`, `y`, `z`, `time`) VALUES (NULL, "'.$x.'", "'.$y.'", "'.$z.'", NULL);');
	//echo "<br>".'INSERT INTO `testtable` (`id`, `x`, `y`, `z`, `time`) VALUES (NULL, "'.$x.'", "'.$y.'", "'.$z.'", NULL);'."<br>";

	$t->count();

	$t->insert('INSERT INTO `test` (`text`, `time`) VALUES ("'.$x.'*'.$y.'*'.$z.'", NULL);');
	echo $x.'*'.$y.'*'.$z;

	
	//$t->raw_query('CREATE TABLE `fich`.`health_test2` ( `id` INT(11) NOT NULL , `heart` INT NOT NULL , `x` FLOAT NOT NULL , `y` FLOAT NOT NULL , `z` FLOAT NOT NULL , `time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ) ENGINE = InnoDB;');
	