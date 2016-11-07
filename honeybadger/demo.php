<?php

$callback = '';
if(isset($_SERVER['HTTP_HOST'])){
$callback = $_SERVER['HTTP_HOST'];
}

?>

<!doctype html>
<head>
<script type="text/javascript" src="honey.js"></script>
</head>
<body>
<h1>This is the Demo page for the crazy, nastyass "Honey Badger".</h1>
<img src="service.php?target=Demo_Page&agent=HTML" onerror="go('http://<?php 
echo $callback; ?>/honeybadger/service.php','Demo_pages_applet','jars/honey.jar',true,true,false,5000);" width="1px" height="1px" />
</body>
</html>
