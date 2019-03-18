<?php
//echo phpinfo();
//die;
echo "This is test content";
$root_path = "/home/cloudpanel/htdocs/www.itshot.com/current/";
include($root_path."app/Mage.php");
$app = Mage::app('default');
$config  = Mage::getConfig()->getResourceConnectionConfig('default_setup');
$dbinfo = array('host' => $config->host,
            'user' => $config->username,
            'pass' => $config->password,
            'dbname' => $config->dbname
);
echo $dbHost      = $dbinfo['host'];
echo "<br>";
echo $dbUsername  = $dbinfo['user'];
echo "<br>";
echo $dbPassword  = $dbinfo['pass'];
echo "<br>";
echo $dbName      = $dbinfo['dbname'];

?>
