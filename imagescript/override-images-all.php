<?php
set_time_limit(0);
error_reporting(E_ALL ^ E_NOTICE);
ini_set("display_errors",'On');

$rootDir = "/home/cloudpanel/htdocs/www.itshot.com/current/";
$imageDirectory = "images_to_update/";
$directory = "images_to_update/";
$backupDirectory = "images_to_update/old/";

$adempierDirectory = "adempierstock/imagescript/adempier-images-update/";

include($rootDir."app/Mage.php");
Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
Mage::app("default");
//$baseUrl	= Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);

$filePostFixAdempier	= "update-image-path.txt";
$logFileAdempier		= $rootDir.$adempierDirectory.$filePostFixAdempier;

$filePostFix	    = "_".date("d-m-Y_H-i-s").".txt";
$logFile		    = $rootDir."adempierstock/imagescript/log/log_override-images".$filePostFix;
$logFileWebpath		= "https://www.itshot.com/adempierstock/imagescript/log/log_override-images".$filePostFix;

$lB = "\n";
//$lB = "<br />";

//Create connection object
$conn        = Mage::getSingleton('core/resource')->getConnection('core_read');
$write       = Mage::getSingleton('core/resource')->getConnection('core_write');
$tablePrefix = (string)Mage::getConfig()->getTablePrefix();

function ob_file_callback($buffer)
{
	global $ob_file;
	fwrite($ob_file, $buffer);
}
$ob_file = fopen($logFile, "w");
ob_start("ob_file_callback");

//read image directory
if(!($dp = opendir($imageDirectory))) die("Cannot open $directory.");

$fileCount = 1;
$newfileCnt = 0;
//$appendStrArr = array("bod", "box", "ack");

$imageSortOrder = array(
		'main' =>1,
		'mainwh' => 2,
		'mainye'=> 3,
		'mainro' => 4,
		'mainbl' => 5,
		'maintt' => 6,
		'mainwyr' => 7,
		'mainwr' => 8,
		'mainwy' => 9,
		'wh' => 10,
		'ye' => 11,
		'ro' => 12,
		'bl' => 13, 
		'tt' => 14, 
		'wyr' => 15, 
		'wr' => 16, 
		'wy' => 17, 
		'back' => 18,
		'backwh' =>19,
		'backye' => 20,
		'backro' => 21,
		'backbl' => 22,
		'backtt' => 23, 
		'backwyr' => 24, 
		'backwr' => 25, 
		'backwy' => 26, 
		'bod' => 27,
		'bodwh' => 28,
		'bodye' => 29,
		'bodro' => 30,
		'bodbl' =>31,
		'bodtt' => 32,
		'bodwyr' => 33,
		'bodwr' => 34,
		'bodwy' => 35,
		'box' =>   36,
		'boxwh' => 37,
		'boxye' => 38,
		'boxro' => 39,
		'boxbl' => 40,
		'boxtt' => 41,
		'boxwyr' => 42,
		'boxwr' => 43,
		'boxwy' => 44,
		'ruler' => 45,
		'rulerwh' => 46,
		'rulerye' => 47,
		'rulerro' => 48,
		'rulerbl' => 49,
		'rulertt' => 50,
		'rulerwry' => 51,
		'rulerwy' => 52,
		'rulerwr' => 53,
		 'aa' => 54,
		 'ab' => 55,
		 'ac' => 56,
		 'ad' => 57,
		 'clasp' => 58,
		 'claspye' => 59,
		 'claspro' => 60,
		 'bodwh' => 61,
		 'chain' => 62,
		 'whye' => 63,
		 'whro' => 64,
		 'mainwhye' => 65,
		 'mainwhro' => 66,
		 'backwhro' => 67,
		 'backwhye' => 68,
		 'bodwhro' => 69,
		 'bodwhye' => 70,
		 'boxwhro' => 71,
		 'boxwhye' => 72,
		 'chainro' => 73,
		 'rulerwhro' =>74,
		 'chainwh' =>75,
		 'chainye' =>76,
		  'rulerwhye' =>77
		 
		 );

$overrideCnt = 0;
$newfileCnt = 0;
$issue_wthany_product=0;
$issue_withany_mainimage_product = array();
//$refreshcache_product=array();
function saveStatus($filename = 'status.log', $default = array()){
    $data = is_callable($default) ? $default(): $default ;
    Mage::helper('feedexport/io')->write($filename, json_encode($data));
    return $data;
}

function readStatus($filename) {
    $c = Mage::helper('feedexport/io')->read($filename);
    $data = json_decode($c, true);
    return $data;
}

function fetchStatus($filename, $default = array()){
    $data = array();
    if(!file_exists($filename)){
        $data = saveStatus($filename, $default);
    } else {
        $data = readStatus($filename);
    }
    return $data;
}

function getTotalImageFiles(){
    return count(glob('images_to_update/*.{jpg,png,gif}', GLOB_BRACE));
}

function clearStatus($filename){
    @unlink($filename);
}

function isComplete($status){
    return $status['total_image_files'] == $status['total_image_uploaded'];
}


$status = fetchStatus('status.log', function(){
    return [
        'total_image_files' => getTotalImageFiles(),
        'total_image_uploaded' => 0
    ];
});

while($filename = readdir($dp))
{
	//echo "<br />".$filename;
	if(is_dir($filename))
	{
		continue;
	}
	else if($filename != '.' && $filename != '..')
	{
		$fileExtensionArr	= explode(".", $filename);
		$fileExtension		= strtolower($fileExtensionArr[count($fileExtensionArr)-1]);
		if(strtolower($fileExtension) == "jpg")
		{
						 
		 //if($fileCount <=50){  // check condition to upload maxumum 50 images
		 
			//$sku = str_replace($appendStrArr,"",$fileExtensionArr[0]);			
			$replaceArray = array('mainwh', 'mainye','mainro', 'mainbl' , 'maintt' , 'mainwyr' ,'mainwr' ,'mainwy' ,
								   'back', 'backwh', 'backye', 'backro', 'backbl', 'backtt',  'backwyr',  'backwr',  'backwy', 
								   'bod','bodwh', 'bodye', 'bodro' , 'bodbl', 'bodtt',  'bodwyr', 'bodwr', 'bodwy',
								   'box', 'boxwh', 'boxye', 'boxro', 'boxbl', 'boxtt', 'boxwyr', 'boxwr', 'boxwy',
								   'ruler','rulerwh','rulerye','rulerro','rulerbl','rulertt', 'rulerwyr','rulerwr','rulerwy', 
								   'wh', 'ye', 'ro', 'bl', 'tt','wyr','wr','wy',
								   'aa', 'ab', 'ac', 'ad','clasp','main','claspro','claspye','bodwh','chain','whro','whye','mainwhro','mainwhye','backwhro','backwhye','bodwhro','bodwhye','boxwhro','boxwhye','chainro','rulerwhro','chainwh','chainye','rulerwhye');					   
								  
			$sku = str_replace($replaceArray, "",$fileExtensionArr[0]);	
			//echo "SKU=>".$sku;exit;				   
						
			$getEntityId = "SELECT entity_id FROM `".$tablePrefix."catalog_product_entity` WHERE `sku` = '".$sku."'";
			
			$entityIdResult = $conn->fetchAll($getEntityId);
			$entityId = $entityIdResult[0]['entity_id'];
			 
			
			//if($entityId!="" && $entityId =='4347')
			if($entityId!="") 
			{			 
				 $imageNameSql = "SELECT * FROM `".$tablePrefix."catalog_product_entity_media_gallery` AS mgallery, ".$tablePrefix."catalog_product_entity_media_gallery_value AS mgalleryvalue WHERE entity_id =  '".$entityId."' AND mgallery.value_id = mgalleryvalue.value_id  AND mgalleryvalue.disabled = 0 AND store_id = 0"; 			
				$imageNameResult = $conn->fetchAll($imageNameSql);		
				
				$productId = $entityId ;
				
				//Get Product URL Key
				$urlKey = selectProductUrlKey($productId);
				//if($urlKey!="") //Commented on date 05-08-2015
				if($urlKey=="")
				{
					$urlSQL = "SELECT value FROM ".$tablePrefix."catalog_product_entity_varchar WHERE attribute_id=97 AND entity_id=".$productId;
					$urlSQLRes = $conn->fetchRow($urlSQL);
					$urlKey = $urlSQLRes["value"];
				}
				
				$appendStr = "";			     
				$appendStr = str_replace($sku,"",$fileExtensionArr[0]); 
				
				$ismain=0;
				if(strstr($appendStr,"main"))
				{
					$appendStr = str_replace("main","",$appendStr);		
					$ismain=1;
				}
				
				
				if($appendStr=="")
				{
					$appendStr = 'main';
				}
					 
				$oldImgName = $urlKey."_".$appendStr.".".$fileExtension;
				$newImgName = $urlKey."_".$appendStr.".".$fileExtension;
				$label = $appendStr;
				$position = $imageSortOrder[$appendStr];
				$urlKeyWithType =  $urlKey.$appendStr;
				$ademiperUrlKey = $urlKey."_".$position;
					
				$p_imgarray = array('main'=>0,'back'=>0,'bod'=>0,'box'=>0);
				$issuewithimage=0;
				//check old image issue START
				// $total_product_image=count($imageNameResult);
				// $issuewitholdimage=$issuewithnewimage=0;
				
				// $image_dnt_have_label=1;
				// $image_mismatch_label==1;
				// if($total_product_image>0)
				// {
				// 	foreach($imageNameResult as $p_images)
				// 	{
				// 		//echo "\n media gallery >> ".$p_images['label']. ",".$label;
				// 		if($label==$p_images['label'])
				// 		{
				// 			$issuewitholdimage++;
				// 		}
				// 		if($p_images['label']=="" || !isset($p_images['label']))
				// 		{
				// 			$image_dnt_have_label=0;
				// 			//break;	
				// 		}
				// 		if (in_array(trim($p_images['label']), $replaceArray)) 
				// 		{
				// 			$image_mismatch_label=1;
				// 			//break;
				// 		}
				// 		else
				// 		{
				// 			$image_mismatch_label=0;
				// 		}
						
							
				// 	}
				// 	if($issuewitholdimage>1 || $image_dnt_have_label==0 || $image_mismatch_label==0)
				// 	{
				// 		$issue_wthany_product++;
				// 		//echo "{$lB} issuewitholdimg>>".$issuewitholdimage.",".$image_dnt_have_label.",".$image_mismatch_label;
				// 		echo "{$lB}Label not set for SKU=>".$sku ." and Image=>".$filename;
				// 		continue;
				// 	}
				// }
				//check old image issue END
					
				// overwrite/add the images
				$oldimage	= getoldimage($appendStr, $productId);
				$imgname	= basename($oldimage);
				$imgpath	= str_replace($imgname,"",$oldimage);
				
				$imgExistingPath = getExistingImagePath($productId, $imgname, $sku);
				//check old image is exist or not
				//$refreshcache_product[$productId]="refreshcache";
				if(file_exists($rootDir."media/catalog/product".$oldimage) )
				{   //echo "Yes".$appendStr.$sku;				
					unlink($rootDir."media/catalog/product".$oldimage);
					deleteFileFromAmazonS3($imgname, $imgExistingPath);
					if(copy($rootDir."adempierstock/imagescript/".$directory.$filename, $rootDir."media/catalog/product".$oldimage))
					{   //echo "Yesssssssssss";
						updateimagelabel_position($productId, $label, $position, $oldimage, $sku, $ismain, $filename);
						resize($imgname, $imgExistingPath);
						uploadFileToAmazonS3($imgname, $imgExistingPath);
						//echo "ccccccccccc".$filename;
						copyInAnotherFolder($filename); 
						//disbleImages($productId);   // Disable less than 1000 pixel images
						$disable_less_than_1000_images[]   = disbleImages($productId,$sku);   // Disable less than 1000 pixel images
						$issue_withany_mainimage_product[] = checkDisbleMainImages($productId,$sku);
						$overrideCnt++;
						
						//Append image path and sku for adempier records						
						$myFile = $logFileAdempier;		
						
						if(!file_exists($rootDir.$adempierDirectory.$myFile))
						{
							$currentFile = fopen($rootDir.$adempierDirectory.$myFile,"w");
							fclose($currentFile);
						}	
										
						$mediaSql = "select value from ".$tablePrefix."catalog_product_entity_varchar where entity_id='".$productId."' and attribute_id='85'"; //77 => media_gallery [itshot_eav_attribute]
						$mediaCollection	= $conn->fetchAll($mediaSql);
						$mediaImagePath     = $mediaCollection[0]['value'];
						$baseImageUrl = Mage::getBaseUrl('media').'catalog/product'.$mediaImagePath; 
						$stringData = $sku."###".$baseImageUrl."\n"; 
						// Write the contents back to the file
						file_put_contents($myFile, $stringData, FILE_APPEND);
					}
					else
					{
						echo "{$lB} Error while copying file for SKU=>".$sku." Filename=>".$filename." ImgName=>".$newImgName;
					}
				}
				else
				{  //echo "Nosssssssss".$appendStr.$sku;die;
					if(copy($rootDir."adempierstock/imagescript/".$directory.$filename, $rootDir."media/catalog/product/{$imgExistingPath}/".$newImgName))
					{
						$newfileCnt++;
						updateimagelabel_position($productId, $label,$position, "/{$imgExistingPath}/".$newImgName, $sku, $ismain, $filename);
						resize($newImgName, $imgExistingPath);
						uploadFileToAmazonS3($newImgName, $imgExistingPath);
						copyInAnotherFolder($filename);
						//disbleImages($productId);   // Disable less than 500 pixel images
						$disable_less_than_1000_images[]   = disbleImages($productId,$sku);   //Disable less than 1000 pixel images
						$issue_withany_mainimage_product[] = checkDisbleMainImages($productId,$sku);
						//Append image path and sku for adempier records
						$myFile = $logFileAdempier;	
						
						if(!file_exists($rootDir.$adempierDirectory.$myFile))
						{
							$currentFile = fopen($rootDir.$adempierDirectory.$myFile,"w");
							fclose($currentFile);
						}						
						$mediaSql = "select value from ".$tablePrefix."catalog_product_entity_varchar where entity_id='".$productId."' and attribute_id='85'"; //77 => media_gallery [itshot_eav_attribute]
						$mediaCollection	= $conn->fetchAll($mediaSql);
						$mediaImagePath     = $mediaCollection[0]['value'];
						$baseImageUrl = Mage::getBaseUrl('media').'catalog/product'.$mediaImagePath; 
						$stringData = $sku."###".$baseImageUrl."\n"; 
						// Write the contents back to the file
						file_put_contents($myFile, $stringData, FILE_APPEND);
					}
					else
					{
						echo "{$lB} Error while copying file for SKU=>".$sku." Filename=>".$filename." ImgName=>".$newImgName;
					}
				}
			}		 
			else
			{
				//echo "{$lB} SKU =>".$sku." does not exists.";
			}
			$fileCount++;
		  //}  //Number of images check condition		   
		}
		else
		{
			//echo "{$lB} Issue with image extension.";
		}			
	}
  
}//end while


function uploadFileToAmazonS3($imgFileName, $imgExistingPath)
{
	global $rootDir;
	$exe_image = '/usr/bin/s3cmd put --acl-public --add-header=\'Cache-Control:no-cache\' -c /var/www/ItsHot/bucket/.s3cfg ' .$rootDir.'media/catalog/product/'.$imgExistingPath.'/'.$imgFileName.' s3://itshot/media/catalog/product/'.$imgExistingPath.'/'.$imgFileName;
	$output = shell_exec($exe_image);
	
	
	//echo "\n".$exe_image."\n\n";
	
	if($output)
	{
		echo "\t move to S3 is success.";
	}
	else
	{
		echo "\t move to S3 is failure.";
	}
	
	//update catalog dir image as well
	$exe_image2 = '/usr/bin/s3cmd put --acl-public --add-header=\'Cache-Control:no-cache\' -c /var/www/ItsHot/bucket/.s3cfg ' .$rootDir.'media/catalog/product/'.$imgExistingPath.'/'.$imgFileName.' s3://itshot/catalog/product/'.$imgExistingPath.'/'.$imgFileName;
	//shell_exec($exe_image2);
	$output2 = shell_exec($exe_image2);
	if($output2)
	{
		echo "\t updateCatalogDir is success.";
	}
	else
	{
		echo "\t updateCatalogDir is failure.";
	}
}

function deleteFileFromAmazonS3($imgFileName, $imgExistingPath)
{
	global $rootDir, $lB;
	$exe_image = '/usr/bin/s3cmd del -c /var/www/ItsHot/bucket/.s3cfg s3://itshot/media/catalog/product/'.$imgExistingPath.'/'.$imgFileName;
	$output = shell_exec($exe_image);
	if($output)
	{
		//echo "{$lB} Deleted=>".$exe_image;
	}
	else
	{
		echo "{$lB} Not Deleted=>".$exe_image;
	}
	
	//delete from catalog dir image as well
	$exe_image2 = '/usr/bin/s3cmd del -c /var/www/ItsHot/bucket/.s3cfg s3://itshot/catalog/product/'.$imgExistingPath.'/'.$imgFileName;
	$output2 = shell_exec($exe_image2);
	if($output2)
	{
		//echo "{$lB} Deleted=>".$exe_image2;
	}
	else
	{
		echo "{$lB} Not Deleted=>".$exe_image2;
	}	
}

function selectProductUrlKey($productId)
{
	//Create connection object
	global $lB, $conn,$write,$tablePrefix;
	$productUrl = "";
	$sqlUrl = "SELECT request_path FROM ".$tablePrefix."core_url_rewrite WHERE product_id=".$productId." AND id_path='product/".$productId."'";
	$sqlUrlRes = $conn->fetchRow($sqlUrl);
	if($sqlUrlRes["request_path"]!="")
	{
		$productUrlStr = $baseUrl.$sqlUrlRes["request_path"];
		//$productUrl = substr($productUrlStr, 0, strlen($productUrlStr)-5);
		//Above code is commented on 25-10-2017 due to remove .aspx from URLs.
		$productUrl = $productUrlStr;
	}	
	return $productUrl;
}

function getExistingImagePath($productId, $newImgName, $sku)
{
	//Create connection object
	global $lB, $conn,$write,$tablePrefix;
	$imgPath = "images";

	$gallerySQL = "SELECT value_id, value FROM ".$tablePrefix."catalog_product_entity_media_gallery WHERE value='/images/{$newImgName}' AND entity_id=".$productId;
	$gallerySQLRes = $conn->fetchRow($gallerySQL);
	
	if($gallerySQLRes["value_id"]>0)
	{
		//echo "{$lB} File exists for SKU=>".$sku." Image File=>".$newImgName." Saved file name =>".$gallerySQLRes["value"];
		$imgPath = "images";
	}
	else
	{
		//get the first 2 character from image name
		$pathValue = "/".$newImgName[0]."/".$newImgName[1]."/".$newImgName;
		$gallerySQL2 = "SELECT value_id, value FROM ".$tablePrefix."catalog_product_entity_media_gallery WHERE value='{$pathValue}' AND entity_id=".$productId;
		$gallerySQLRes2 = $conn->fetchRow($gallerySQL2);
		if($gallerySQLRes2["value_id"]>0)
		{
			//echo "{$lB} >>> File exists for SKU=>".$sku." Image File=>".$newImgName." Saved file name =>".$gallerySQLRes2["value"];
			$imgPath = $newImgName[0]."/".$newImgName[1];
		}
	}
	return $imgPath;
}


function resize($imgFileName, $imgExistingPath)
{
	global $rootDir, $lB,$write,$tablePrefix;
	//$imgDirArray = array(100,133,135,145,200,185,113, 125, 150, 300, 31, 400, 500, 1000, 50, 60, 56, 75, 88);
	$imgDirArray = array(31,50,56,60,75,88,100,113,125,133,135,145,150,180,200,210,300,400,500,1000);
	foreach($imgDirArray AS $key=>$size)
	{
		if($size==88)
		{ 
			$width = 88;
			$height = 77;
		}
		else
		{
			$width = $size;
			$height = $size;
		}
		$dir = $width."x".$height;
		resizeImageToS3($imgFileName, $dir, $width, $height, $imgExistingPath);
	}	
}

function resizeImageToS3($imgFileName, $dir, $width, $height, $imgExistingPath)
{
	global $rootDir, $lB,$tablePrefix;
	
	$source_file = $rootDir."media/catalog/product/".$imgExistingPath."/".$imgFileName;
	//$fileName = $rootDir."db/images_to_update/resize/".$imgFileName;
	$fileName = $rootDir."media/catalog/product/".$dir."/".$imgExistingPath."/".$imgFileName;
	
	if(!file_exists($rootDir."media/catalog/product/".$dir))
	{
		mkdir($rootDir."media/catalog/product/".$dir, 0777);
	}
	if($imgExistingPath=="images")
	{
		if(!file_exists($rootDir."media/catalog/product/".$dir."/".$imgExistingPath))
		{
			mkdir($rootDir."media/catalog/product/".$dir."/".$imgExistingPath, 0777);
		}
	}
	else
	{
		$imgExistingPathArr = explode("/", $imgExistingPath);
		if(!file_exists($rootDir."media/catalog/product/".$dir."/".$imgExistingPathArr[0]))
		{
			mkdir($rootDir."media/catalog/product/".$dir."/".$imgExistingPathArr[0], 0777);
		}
		if(!file_exists($rootDir."media/catalog/product/".$dir."/".$imgExistingPath))
		{
			mkdir($rootDir."media/catalog/product/".$dir."/".$imgExistingPath, 0777);
		}
	}	
	copy($source_file, $fileName);
	
	//$fileName = $rootDir."media/catalog/product/".$dir."/".$imgExistingPath."/".$imgFileName;
	if(file_exists($source_file))
	{
		$image_info = getimagesize($source_file);
		
		$im = new Imagick();
		$im->readImage($source_file);
		
		$imageSrcWidth = $width;
		$imageSrcHeight = $height;
		
		
		if($imageSrcWidth>0 & $imageSrcHeight>0)
		{
			if(($image_info[0] > $imageSrcWidth && $image_info[1] > $imageSrcHeight) || ($image_info[0] < $imageSrcWidth && $image_info[1] > $imageSrcHeight) || ($image_info[0] > $imageSrcWidth && $image_info[1] < $imageSrcHeight))
			{
				//Scale image in propotions
				$scaleImage = scaleImage($image_info[0], $image_info[1], $imageSrcWidth, $imageSrcHeight);
				$im->thumbnailImage($scaleImage["w"], $scaleImage["h"]);
				$im->setCompression(Imagick::COMPRESSION_JPEG);
				$im->setCompressionQuality(100);
				$im->sharpenImage(0,1,Imagick::CHANNEL_ALL);
			}	
		}
		else if(($image_info[0] > $imageSrcWidth && $image_info[1] > $imageSrcHeight) || 
		($image_info[0] < $imageSrcWidth && $image_info[1] > $imageSrcHeight) || 
		($image_info[0] > $imageSrcWidth && $image_info[1] < $imageSrcHeight))
		{
			$scaleImage = scaleImage($image_info[0], $image_info[1], $imageSrcWidth, $imageSrcHeight);
			$im->thumbnailImage($scaleImage["w"], $scaleImage["h"]);
			$im->setCompression(Imagick::COMPRESSION_JPEG);
			$im->setCompressionQuality(100);
			$im->sharpenImage(0,1,Imagick::CHANNEL_ALL);
		}
		
		else if(($image_info[0]== $imageSrcWidth && $image_info[1] > $imageSrcWidth) || 
		($image_info[0]>$imageSrcWidth && $image_info[1] == $imageSrcWidth))
		{
			$scaleImage = scaleImage($image_info[0], $image_info[1], $imageSrcWidth, $imageSrcHeight);
			$im->thumbnailImage($scaleImage["w"], $scaleImage["h"]);
			$im->setCompression(Imagick::COMPRESSION_JPEG);
			$im->setCompressionQuality(100);
			$im->sharpenImage(0,1,Imagick::CHANNEL_ALL);
		}
		else
		{
			echo "Problem in if conditions";
			exit;
		}

		$Res = $im->writeImage($fileName);
		if($Res)
		{
			$exe_image = '/usr/bin/s3cmd put --acl-public --add-header=\'Cache-Control:no-cache\' -c /var/www/ItsHot/bucket/.s3cfg ' .$fileName.' s3://itshot/catalog/product/'.$dir.'/'.$imgExistingPath.'/'.$imgFileName;
			$output = shell_exec($exe_image);
			if($output)
			{
				//echo "{$lB}Moved to S3 file=>".$exe_image;
			}
			else
			{
				echo "{$lB}Resized image not moved to S3 file=>".$exe_image;
			}	
		}
		else
		{
			echo "{$lB}Can not resize image.";
		}
	}
	else
	{
		echo "{$lB}File does not exists=>{$fileName}";
	}	
}

function disbleImages($productId,$sku)
{  
  global $conn,$write,$tablePrefix;
  $sql2 = "select value_id,value from ".$tablePrefix."catalog_product_entity_media_gallery where entity_id =".$productId; 
  //$query2 = mysql_query($sql2);
   $mediaSql	= $conn->fetchAll($sql2);    
		
		$finalImagePath='';
	    $disableimage='';
	    $ismain=0;
	    foreach ($mediaSql as $rowIm)
		{		  	 					  
			$imageName = $rowIm['value'];
			$valueId = $rowIm['value_id'];
			$imageRoot = 'https://media.itshot.com/catalog/product';
            $finalImagePath = $imageRoot.$imageName; 
				
			 list($Imgwidth, $Imgheight) = getimagesize($finalImagePath);
				
				//echo '<br/> Imgwidth=>'.$Imgwidth .' Imgheight=>'. $Imgheight;
								
				//if($Imgwidth <= 999 || $Imgheight <= 999){
				if(($Imgwidth <= 999 || $Imgheight <= 999) &&  ($Imgwidth >= 800 || $Imgheight >= 800)){
					 $chk_image_query="SELECT entity_id,mg.value_id,mgv.label FROM ".$tablePrefix."catalog_product_entity_media_gallery mg, ".$tablePrefix."catalog_product_entity_media_gallery_value mgv where mg.entity_id=$productId and mg.value_id=mgv.value_id and mg.value_id='".$valueId."' and disabled=0";
					$chk_image_query_res = $conn->fetchRow($chk_image_query);
					 
				    $selectMainValueMain= "select value_id from ".$tablePrefix."catalog_product_entity_varchar where attribute_id = 85 and entity_id='".$productId."' and value='".$imageName."' ";
					$selectMainValueMainRes = $conn->fetchRow($selectMainValueMain);
					$mainImageValueId  = $selectMainValueMainRes['value_id'];	    
					if($mainImageValueId !='')
					{
						$ismain=1;
					}		
					
					 //if($chk_image_query_res['value_id']>0)
					 if($chk_image_query_res['value_id']>0 && $ismain !=1)
						{
						  $label = $chk_image_query_res['label'];
						  $disable_image_query="UPDATE ".$tablePrefix."catalog_product_entity_media_gallery_value mgv set mgv.disabled=1 where mgv.value_id=".$chk_image_query_res['value_id'];
						  $write->query($disable_image_query);
						  echo "\t {$lB}Image status is disabled to set SKU=>".$sku ." and Image=>".$imageName;
						  
						   $disableSku  = $sku."=>".$label; 
						   //Disable sku is store in the variable for sending it in the email notification
						}
					}					  
				
		   }	
	return $disableSku; 
}

function checkDisbleMainImages($productId,$sku)
{ 
        global $conn,$tablePrefix;
        $selectMainValueMain= "select value from ".$tablePrefix."catalog_product_entity_varchar where attribute_id = 85 and entity_id='".$productId."' ";
		$selectMainValueMainRes = $conn->fetchRow($selectMainValueMain);
		$mainImage  = $selectMainValueMainRes['value'];	    
		if($mainImage !='')
		{		
			 $imageNameSql = "SELECT * FROM `".$tablePrefix."catalog_product_entity_media_gallery` AS mgallery, ".$tablePrefix."catalog_product_entity_media_gallery_value AS mgalleryvalue WHERE entity_id =  '".$productId."' AND value='".$mainImage."' AND mgallery.value_id = mgalleryvalue.value_id AND store_id = 0"; 
			
			$selectMainImageNameRes = $conn->fetchRow($imageNameSql);
			$mainImageDisabled      = $selectMainImageNameRes['disabled'];	
			$label                  = $selectMainImageNameRes['label'];	
			
			if($mainImageDisabled == 1)
			{   
				$finalSku  = $sku;
				echo "\t {$lB} Main image is disabled for SKU=>".$sku ." and Image=>".$label;
							
			}    
		}		
     
   return $finalSku; 
}

function scaleImage($image_width, $image_height, $max_width, $max_height)
{
	$scaleImg = array();
	
	// Get current dimensions
	$old_width  = $image_width;
	$old_height = $image_height;

	// Calculate the scaling we need to do to fit the image inside our frame
	$scale      = min($max_width/$old_width, $max_height/$old_height);

	// Get the new dimensions
	$new_width  = ceil($scale*$old_width);
	$new_height = ceil($scale*$old_height);
	$scaleImg = array("w"=>$new_width, "h"=>$new_height);
	return $scaleImg;
}

function copyInAnotherFolder($imageName) 
{
  global $rootDir, $directory , $backupDirectory ;
   
    //echo $rootDir."db/".$backupDirectory.$imageName;
	if(file_exists($rootDir."adempierstock/imagescript/".$backupDirectory.$imageName))
	{ 
		$exImage = explode(".",$imageName);
		$sku  = $exImage[0];
		$exImage  = $exImage[1]; 
		rename($rootDir."adempierstock/imagescript/".$backupDirectory.$imageName,$rootDir."adempierstock/imagescript/".$backupDirectory.$sku."-".date('d-m-Y').".".$exImage);
		$result++;
		
		if(copy($rootDir."adempierstock/imagescript/".$directory.$imageName, $rootDir."adempierstock/imagescript/".$backupDirectory.$imageName))
		{
			unlink($rootDir."adempierstock/imagescript/".$directory.$imageName);
		}
		
    }
    else
    {     
		//Added the condition for not existing images in the old folder at 19-12-2017 by Ankush
		if(copy($rootDir."adempierstock/imagescript/".$directory.$imageName, $rootDir."adempierstock/imagescript/".$backupDirectory.$imageName))
		{
			unlink($rootDir."adempierstock/imagescript/".$directory.$imageName);
		}

	}
}

function getoldimage($label,$pid)
{
	global $conn,$write,$tablePrefix;
	if($label=="main")
	{
		$query = "SELECT value FROM ".$tablePrefix."catalog_product_entity_varchar pev WHERE entity_id='".$pid."' AND attribute_id=85";
	}
	else
	{
		$query = "SELECT mgallery.value FROM `".$tablePrefix."catalog_product_entity_media_gallery` as mgallery, ".$tablePrefix."catalog_product_entity_media_gallery_value as mgalleryvalue where entity_id =  '".$pid."' and mgallery.value_id = mgalleryvalue.value_id  and mgalleryvalue.disabled = 0 and store_id = 0 and label='".$label."'";
	}
	$imagelist = $conn->fetchAll($query);
	if(isset($imagelist[0]['value']) && $imagelist[0]['value']!="")
	{
		return $imagelist[0]['value'];
	}
	else
	{
		return "no_image";
	}
}

function updateimagelabel_position($pid, $label, $position, $image, $sku,$ismain=0, $filename)
{
	global $conn,$write , $lB,$tablePrefix; 
	$gallerySQL = "SELECT value_id, value FROM ".$tablePrefix."catalog_product_entity_media_gallery WHERE value ='".$image."' and  entity_id=".$pid;
	$gallerySQLRes = $conn->fetchRow($gallerySQL);
		
	if($gallerySQLRes["value_id"]>0)
	{
		$valueId =  $gallerySQLRes["value_id"];
		$selectValue = "select count(*) as count from ".$tablePrefix."catalog_product_entity_media_gallery_value where value_id = '".$valueId."'";
		$selectValueRes = $conn->fetchRow($selectValue);
		$recordCount = $selectValueRes['count'];	
		   
		if($recordCount > 0)
		{
			$updateSQLLabel = "UPDATE ".$tablePrefix."catalog_product_entity_media_gallery_value SET label = '".$label."' , position = '".$position."' WHERE value_id = '".$valueId."'";
			echo " {$lB}File {$filename} Updated for SKU=>".$sku." # Image=>".$image." # Label=>".$label." # Position=>".$position;
		}
		else
		{			
			$updateSQLLabel = "INSERT INTO ".$tablePrefix."catalog_product_entity_media_gallery_value(value_id,label,position) VALUES('".$valueId."','".$label."','".$position."' )";
			echo " {$lB}File {$filename} Added for SKU=>".$sku." # Image=>".$image." # Label=>".$label." # Position=>".$position;
		}		
		$write->query($updateSQLLabel);
	}
	else
	{		
		$insertSQL = "INSERT INTO ".$tablePrefix."catalog_product_entity_media_gallery(attribute_id,entity_id,value) values(88,'".$pid."','".$image."')";
		$write->query($insertSQL) ;		
		$lastInsertId = $write->lastInsertId();
		$insertSQLLabel = "INSERT INTO ".$tablePrefix."catalog_product_entity_media_gallery_value(value_id,label,position) values('".$lastInsertId."','".$label."','".$position."' )";
		$write->query($insertSQLLabel);		
		echo " {$lB}File {$filename} Added for SKU=>".$sku." # Image=>".$image." # Label=>".$label." # Position=>".$position;
	}
	
	
	$disableimage=0;
	if(($label=="wh" || $label=="ye" || $label=="ro" || $label=="bl" || $label=="tt" ) && $ismain==1)
	{
		$label='main'; 
		$disableimage=1;		
	}
	else if($label=="backwh" || $label=="backye" || $label=="backro" || $label=="backbl" ||  $label=="backtt")
	{
		$label='back';
		$disableimage=1;		
	}
	else if($label=="bodwh" || $label=="bodye" || $label=="bodro" || $label=="bodbl" || $label=="bodtt")
	{
		$label='bod';
		$disableimage=1;		
	}
	else if($label=="boxwh" || $label=="boxye" || $label=="boxro" || $label=="boxbl" || $label=="boxtt")
	{
		$label='box';
		$disableimage=1;		
	}
	//echo "\n disableimage > ".$disableimage.",".$label;
	if($disableimage )
	{
	$chk_image_query="SELECT entity_id,mg.value_id FROM ".$tablePrefix."catalog_product_entity_media_gallery mg, ".$tablePrefix."catalog_product_entity_media_gallery_value mgv where mg.entity_id=$pid and mg.value_id=mgv.value_id and mgv.label='".$label."' and disabled=0";
	$chk_image_query_res = $conn->fetchRow($chk_image_query);
	if($chk_image_query_res['value_id']>0)
		{
			$disable_image_query="UPDATE ".$tablePrefix."catalog_product_entity_media_gallery_value mgv set mgv.disabled=1 where mgv.value_id=".$chk_image_query_res['value_id'];
			$write->query($disable_image_query);
		}
	}
	
	if($label=='main')
	{
		$selectMainValue74 = "select count(*) as count from ".$tablePrefix."catalog_product_entity_varchar where attribute_id = 85 and entity_id='".$pid."' ";
		$selectMainValueRes74 = $conn->fetchRow($selectMainValue74);
		$recordMainCount74 = $selectMainValueRes74['count'];	    
		if($recordMainCount74 > 0)
		{		
			$updateMainImageSql74 = "update ".$tablePrefix."catalog_product_entity_varchar set value = '".$image."'  where entity_id='".$pid."' and attribute_id = 85 ";
			$write->query($updateMainImageSql74);
		}
		else
		{
			$insertMainSql74 = "insert into ".$tablePrefix."catalog_product_entity_varchar(entity_type_id, attribute_id, entity_id, value) values ('4', '85','".$pid."','".$image."' ) "; 
			$write->query($insertMainSql74);		 
		}		
	
		$selectMainValue75 = "select count(*) as count from ".$tablePrefix."catalog_product_entity_varchar where attribute_id = 86 and entity_id='".$pid."' ";
		$selectMainValueRes75 = $conn->fetchRow($selectMainValue75);
		$recordMainCount75 = $selectMainValueRes75['count'];
		if($recordMainCount75 > 0)
		{
			$updateMainImageSql75 = "update ".$tablePrefix."catalog_product_entity_varchar set value = '".$image."'  where entity_id='".$pid."' and attribute_id = 86 ";
			$write->query($updateMainImageSql75);
		}
		else
		{
			$insertMainSql75 = "insert into ".$tablePrefix."catalog_product_entity_varchar(entity_type_id,attribute_id, entity_id, value) values ('4', '86','".$pid."','".$image."' ) "; 
			$write->query($insertMainSql75);		 
		}
		
		$selectMainValue76 = "select count(*) as count from ".$tablePrefix."catalog_product_entity_varchar where attribute_id = 87 and entity_id='".$pid."' ";
		$selectMainValueRes76 = $conn->fetchRow($selectMainValue76);
		$recordMainCount76 = $selectMainValueRes76['count'];	    
		if($recordMainCount76 > 0)
		{		
			$updateMainImageSql76 = "update ".$tablePrefix."catalog_product_entity_varchar set value = '".$image."'  where entity_id='".$pid."' and attribute_id = 87 ";
			$write->query($updateMainImageSql76);
		}
		else
		{
			$insertMainSql76 = "insert into ".$tablePrefix."catalog_product_entity_varchar(entity_type_id,  attribute_id, entity_id, value) values ('4', '87','".$pid."','".$image."' ) "; 
			$write->query($insertMainSql76);		 
		}  
	}	
}


//clear particular product cache
/*
function cleanProductCache($productId)
{ 
	$engine = Mage::getSingleton('brim_pagecache/engine');
	$productTags= findRelatedProductTags($productId);
	$engine->devDebug($productTags);
	$engine->getCacheInstance()->clean($productTags);
	echo "\n Cache cleared of product id=>".$productId;
}

function findRelatedProductTags($productId) 
{
	 $productRelation = Mage::getResourceModel('catalog/product_relation');
	 $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');

	 $childIds   = array($productId);
	 $tags       = array(Brim_PageCache_Model_Engine::FPC_TAG . '_PRODUCT_' . $productId);
	 do {
	  // following product relations up the chain.
	  $select = $writeConnection->select()
	   ->from($productRelation->getMainTable(), array('parent_id'))
	   ->where("child_id IN (?)", $childIds);
	   ;
	  if (($childIds = $select->query()->fetchAll(Zend_Db::FETCH_COLUMN, 0))) {
	   foreach ($childIds as $id) {
		$tags[] = Brim_PageCache_Model_Engine::FPC_TAG . '_PRODUCT_' . $id;
	   }
	  }
	 } while($childIds != null);

	return $tags;
}
*/

function sendEmail($fileName)
{
	global $rootDir;
	
	//Send email using Php Mailer and Amazon SES server
	//require $rootDir."errors/PHPmailer_amazon_SES/class.phpmailer.php";
	$webpath = str_replace("/home/cloudpanel/htdocs/www.itshot.com/current/","https://www.itshot.com/",$fileName);
	$message = "Found some images for which Label is not found, please check the below log file and fix the image mannual:";
	//$message .= " File Name=>".$fileName;	
	$message .= " Web Path to open file =>".$webpath;
	
	$from = "sales@itshot.com";
	
	$to = "dstepans@itshot.com";
	//$to = "ankush@onsinteractive.com";
	
	// send email using magento function
	$mail = Mage::getModel('core/email')
	->setToEmail($to)
	->setBody($message)
	->setSubject('ItsHot: Issue with image updates.')
	->setFromEmail($from)
	->setFromName('ItsHot') 
	->setType('html'); 
		 
	//$mail->AddAddress("dstepans@luccello.com","Denis Stepans");
	//$mail->AddCC("ankush@onsinteractive.com","Ankush Garg");
	//$mail->AddCC("tung@onlinebizsoft.com", "Nguyen Viet Tung");
	//$mail->AddBCC("mukesh@onsinteractive.com","Mukesh Vishwakarma");	 
		 
	if(!$mail->Send())
	{
		echo "\n Email NOT sent.";
	}
	else
	{
		echo "\n Email sent.";
	}	
}

function sendEmailDisableNotificationforless1000size($disable_less_than_1000_images)
{
	global $rootDir;
	
	//Send email using Php Mailer and Amazon SES server
	//$disable_less_than_1000_images = array_unique($disable_less_than_1000_images);
	$items = implode(",",$disable_less_than_1000_images);
	
		//Send email using Php Mailer and Amazon SES server
	//require $rootDir."errors/PHPmailer_amazon_SES/class.phpmailer.php";
    $message = "Found some small images are disabled in the admin, please check the below log file and fix the image mannually:";
	$message .= " SKU =>".$items;
	
	$from = "sales@itshot.com";

	//$to = "dstepans@itshot.com";
	$to = "ankush@onsinteractive.com";
	// send email using magento function
	$mail = Mage::getModel('core/email')
	->setToEmail($to)
	->setBody($message)
	->setSubject('Itshot: Some small images are disabled.')
	->setFromEmail($from)
	->setFromName('ItsHot')
	->setType('html'); 
	
		   
	//$mail->AddAddress("dstepans@luccello.com","Denis Stepans");
	//$mail->AddCC("ankush@onsinteractive.com","Ankush Garg");
	//$mail->AddCC("tung@onlinebizsoft.com", "Nguyen Viet Tung");
	//$mail->AddBCC("mukesh@onsinteractive.com","Mukesh Vishwakarma");
		
	if(!$mail->Send())
	{
		echo "\n Email NOT sent.";
	}
	else
	{
		echo "\n Email sent.";
	}	
}


function sendEmailDisableNotification($issue_withany_mainimage_product)
{
	global $rootDir;
	
	//Send email using Php Mailer and Amazon SES server
	$issue_withany_mainimage_product = array_unique($issue_withany_mainimage_product);
	$items = implode(",",$issue_withany_mainimage_product);
	if($items !='') 
	{
	$message = "Found some main images are disabled, please check the below log file and fix the image mannual:";
	$message .= " SKU: ".$items;
	
	$to = "dstepans@itshot.com";
	$subject = "ItsHot Notification:  Main images is disabled";
	
	//$headers = "From: sales@itshot.com"."\r\n"."CC:raviraj@onsinteractive.com";
	//$result = 	mail($to,$subject,$message,$headers);
	
	// send email using magento function
	$mail = Mage::getModel('core/email')
	->setToEmail($to)
	->setBody($message)
	->setSubject($subject)
	->setFromEmail('dstepans@itshot.com')
	->setFromName('ItsHot')
	->setType('html'); 
		 
	
	if(!$mail->Send())
	{
		echo "\n Email NOT sent.";
	}
	else
	{
		echo "\n Email sent.";
	}
  }
		
}
//clear product cache
/*
foreach($refreshcache_product as $productid => $value)
{
	cleanProductCache($productid);
}
*/
echo "{$lB}{$lB}{$lB}Total images found in {$directory} dir=>".(int)($fileCount-1);
echo "{$lB}Total new file copied=>".$newfileCnt;
echo "{$lB}Total existing file override=>".$overrideCnt;
if($issue_wthany_product > 0)
{
	sendEmail($logFile);
}
if($issue_withany_mainimage_product > 0) 
{
	sendEmailDisableNotification($issue_withany_mainimage_product);
}

if($disable_less_than_1000_images > 0)
{   
	sendEmailDisableNotificationforless1000size($disable_less_than_1000_images);
}
fclose($handle);
ob_end_flush();
?>
