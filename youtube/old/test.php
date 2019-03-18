<?php
$root_path = "/home/cloudpanel/htdocs/www.itshot.com/current/";
include($root_path."app/Mage.php");
Mage::app();

//echo "This is test content";
//Send email using Php Mailer and Amazon SES server
	
	$items   = "222222,333333,444444";
	$message = "Found some main images are disabled, please check the below log file and fix the image mannual:";
	$message .= " SKU: ".$items;
	

// send email
  $mail = Mage::getModel('core/email')
 ->setToEmail('ankush@onsinteractive.com')
 ->setBody($message)
 ->setSubject('ItsHot Notification:  Main images is disabled')
 ->setFromEmail('ankush@onsinteractive.com')
 ->setFromName('Magento Store Admin')
 ->setType('html');
  $mail->send(); 

?>
