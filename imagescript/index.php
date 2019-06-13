<?php

define('WWW_DIR', '/home/cloudpanel/htdocs/www.itshot.com/current/');
define('ADEMPIERSTOCK_DIR', WWW_DIR . DIRECTORY_SEPARATOR . 'adempierstock');
define('IMAGESCRIPT_DIR', ADEMPIERSTOCK_DIR . DIRECTORY_SEPARATOR . 'imagescript');
define('UPLOAD_DIR', IMAGESCRIPT_DIR . DIRECTORY_SEPARATOR . 'images_to_update');
define('BACKUP_DIR', UPLOAD_DIR . DIRECTORY_SEPARATOR . 'old');

require(WWW_DIR . 'app/Mage.php');
require './vendor/autoload.php';

Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
Mage::app('default');


