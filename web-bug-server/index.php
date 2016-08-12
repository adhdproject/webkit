<?php
	$config = array(
		'pdo_connection_string' => 'mysql:host=localhost;dbname=webbug',
		'db_username' => 'webbuguser',
		'db_password' => 'adhd'
	);

	$id = '';
	if(isset($_GET['id'])) {
		$id = $_GET['id'];
	} else {
		echo '<html><body>';
		echo 'This page is intended for receiving Word web bugs as detailed here ';
		echo '<a href="http://ha.ckers.org/webbug.html">http://ha.ckers.org/webbug.html</a><br>';
		echo 'Requests should be in the form http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		echo '?id=<i>&lt;arbitrary document id&gt;</i>&type=&lt;css|img&gt;';
		echo '</body></html>';
		exit();
	}

	$dbhandle = new PDO($config['pdo_connection_string'], 
		$config['db_username'], $config['db_password']);

	$type = $_GET['type']; 

	$ip = '';
	if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != '') {
    		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
    		$ip = $_SERVER['REMOTE_ADDR'];
	}
	$user_agent = $_SERVER['HTTP_USER_AGENT'];

	$dbhandle->query('INSERT INTO requests (id, type, ip_address, user_agent, time)' . 
		' VALUES (' . $dbhandle->quote($id) . ', ' . $dbhandle->quote($type) . ', ' . $dbhandle->quote($ip) . ', ' . $dbhandle->quote($user_agent) . ', ' . time() . ')')
		or die(print_r($dbhandle->errorInfo(), true));

	if($type == 'css') {
		echo file_get_contents('normalize.css');
	} else if($type == 'img') {
		echo file_get_contents('1x1.jpg');
	}
?>
