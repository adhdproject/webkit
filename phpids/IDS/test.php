<?php

print 1

use IDS\Init;
use IDS\Monitor;
//require_once 'vendor/autoload.php';

$init = Init::init('/var/www/phpids/IDS/Config/Config.ini.php');
$ids = new Monitor($request, $init);
$result = $ids->run();

echo $result;
?>
