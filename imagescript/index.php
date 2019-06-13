<?php
define('WWW_DIR', '/home/cloudpanel/htdocs/www.itshot.com/current/');

require(WWW_DIR . 'app/Mage.php');
require './vendor/autoload.php';

Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
Mage::app('default');


