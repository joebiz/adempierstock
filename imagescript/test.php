<?php
$rootDir = "/home/cloudpanel/htdocs/www.itshot.com/current/";
include($rootDir."app/Mage.php");

Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
Mage::app("default");
$filePostFix	= "_".date("d-m-Y_H-i-s").".txt";
$logFile		= $rootDir."adempierstock/imagescript/log/log_override-images".$filePostFix;
	sendEmail($logFile);


function sendEmail($fileName)
{
	global $rootDir;
	
	//Send email using Php Mailer and Amazon SES server
	//require $rootDir."errors/PHPmailer_amazon_SES/class.phpmailer.php";
	$message = "Found some images for which Label is not found, please check the below log file and fix the image mannual:";
	$message .= " File Name=>".$fileName;
	
	$from = "sales@itshot.com";
	//$to = "dstepans@itshot.com";
	$to = "ankush@onsinteractive.com";
	
	// send email using magento function
	$mail = Mage::getModel('core/email')
	->setToEmail($to)
	->setBody($message)
	->setSubject('ItsHot: Issue with image updates.')
	->setFromEmail($from)
	->setFromName('ItsHot') 
	->setType('html'); 
		 
	//$mail->AddAddress("dstepans@luccello.com","Denis Stepans");
 
		 
	if(!$mail->Send())
	{
		echo "\n Email NOT sent.";
	}
	else
	{
		echo "\n Email sent.";
	}	
}


?>


