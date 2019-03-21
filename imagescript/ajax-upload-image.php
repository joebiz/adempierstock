<?php
session_start();
set_time_limit(0);
error_reporting(E_ALL ^ E_NOTICE);
ini_set("display_errors", 'On');
header("Content-type:application/json");

$rootDir = "/home/cloudpanel/htdocs/www.itshot.com/current/";
$imageDirectory = "images_to_update/";
$directory = "images_to_update/";
$backupDirectory = "images_to_update/old/";

$adempierDirectory = "adempierstock/imagescript/adempier-images-update/";

include($rootDir . "app/Mage.php");
Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
Mage::app("default");

$filePostFixAdempier = "update-image-path.txt";
$logFileAdempier = $rootDir . $adempierDirectory . $filePostFixAdempier;
//Create connection object
$conn = Mage::getSingleton('core/resource')->getConnection('core_read');
$write = Mage::getSingleton('core/resource')->getConnection('core_write');
$tablePrefix = (string) Mage::getConfig()->getTablePrefix();
include './functions.php';

//read image directory
if (!($dp = opendir($imageDirectory)))
    die("Cannot open $directory.");

$fileCount = 1;
$newfileCnt = 0;

$imageSortOrder = array(
    'main' => 1,
    'mainwh' => 2,
    'mainye' => 3,
    'mainro' => 4,
    'mainbl' => 5,
    'maintt' => 6,
    'mainwyr' => 7,
    'mainwr' => 8,
    'mainwy' => 9,
    'ye' => 10,
    'wh' => 11,
    'ro' => 12,
    'bl' => 13,
    'tt' => 14,
    'wyr' => 15,
    'wr' => 16,
    'wy' => 17,
    'back' => 18,
    'backwh' => 19,
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
    'bodbl' => 31,
    'bodtt' => 32,
    'bodwyr' => 33,
    'bodwr' => 34,
    'bodwy' => 35,
    'box' => 36,
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
    'rulerwhro' => 74,
    'chainwh' => 75,
    'chainye' => 76,
    'rulerwhye' => 77
);

$data = getConfig();
if(!$data){
    saveConfig(array(
        'total_files' =>  0,
        'total_files_uploaded' => 0,
        'is_complete' => false,
        'percent_uploaded' => 0
    ));
}
 
 
$overrideCnt = 0;
$newfileCnt = 0;
$issue_wthany_product = 0;
while ($filename = readdir($dp)) {
    if (is_dir($filename)) {
        continue;
    } else if ($filename != '.' && $filename != '..') {
        $fileExtensionArr = explode(".", $filename);
        $fileExtension = strtolower($fileExtensionArr[count($fileExtensionArr) - 1]);
        if (strtolower($fileExtension) == "jpg") {
            $replaceArray = array_keys($imageSortOrder);

            $sku = str_replace($replaceArray, "", $fileExtensionArr[0]);
            $getEntityId = "SELECT entity_id FROM `" . $tablePrefix . "catalog_product_entity` WHERE `sku` = '" . $sku . "'";

            $entityIdResult = $conn->fetchAll($getEntityId);
            $entityId = $entityIdResult[0]['entity_id'];
            if ($entityId != "") {
                $imageNameSql = "SELECT * FROM `" . $tablePrefix . "catalog_product_entity_media_gallery` AS mgallery, " . $tablePrefix . "catalog_product_entity_media_gallery_value AS mgalleryvalue WHERE entity_id =  '" . $entityId . "' AND mgallery.value_id = mgalleryvalue.value_id  AND mgalleryvalue.disabled = 0 AND store_id = 0";
                $imageNameResult = $conn->fetchAll($imageNameSql);
                $productId = $entityId;

                //Get Product URL Key
                $urlKey = selectProductUrlKey($productId);
                if ($urlKey == "") {
                    $urlSQL = "SELECT value FROM " . $tablePrefix . "catalog_product_entity_varchar WHERE attribute_id=97 AND entity_id=" . $productId;
                    $urlSQLRes = $conn->fetchRow($urlSQL);
                    $urlKey = $urlSQLRes["value"];
                }

                $appendStr = "";
                $appendStr = str_replace($sku, "", $fileExtensionArr[0]);

                $ismain = 0;
                if (strstr($appendStr, "main")) {
                    $appendStr = str_replace("main", "", $appendStr);
                    $ismain = 1;
                }

                if ($appendStr == "") {
                    $appendStr = 'main';
                }

                $oldImgName = $urlKey . "_" . $appendStr . "." . $fileExtension;
                $newImgName = $urlKey . "_" . $appendStr . "." . $fileExtension;
                $label = $appendStr;
                $position = $imageSortOrder[$appendStr];
                $urlKeyWithType = $urlKey . $appendStr;
                $ademiperUrlKey = $urlKey . "_" . $position;

                $p_imgarray = array('main' => 0, 'back' => 0, 'bod' => 0, 'box' => 0);
                $issuewithimage = 0;
                // overwrite/add the images
                $oldimage = getoldimage($appendStr, $productId);
                $imgname = basename($oldimage);
                $imgpath = str_replace($imgname, "", $oldimage);

                $imgExistingPath = getExistingImagePath($productId, $imgname, $sku);
                //check old image is exist or not
                if (file_exists($rootDir . "media/catalog/product" . $oldimage)) {
                    unlink($rootDir . "media/catalog/product" . $oldimage);
                    deleteFileFromAmazonS3($imgname, $imgExistingPath);
                    if (copy($rootDir . "adempierstock/imagescript/" . $directory . $filename, $rootDir . "media/catalog/product" . $oldimage)) {
                        updateimagelabel_position($productId, $label, $position, $oldimage, $sku, $ismain, $filename);
                        resize($imgname, $imgExistingPath);
                        uploadFileToAmazonS3($imgname, $imgExistingPath);
                        copyInAnotherFolder($filename);
                        disbleImages($productId, $sku);   // Disable less than 1000 pixel images
                        $overrideCnt++;
                        //Append image path and sku for adempier records						
                        $myFile = $logFileAdempier;

                        if (!file_exists($rootDir . $adempierDirectory . $myFile)) {
                            $currentFile = fopen($rootDir . $adempierDirectory . $myFile, "w");
                            fclose($currentFile);
                        }

                        $mediaSql = "select value from " . $tablePrefix . "catalog_product_entity_varchar where entity_id='" . $productId . "' and attribute_id='85'"; //77 => media_gallery [itshot_eav_attribute]
                        $mediaCollection = $conn->fetchAll($mediaSql);
                        $mediaImagePath = $mediaCollection[0]['value'];
                        $baseImageUrl = Mage::getBaseUrl('media') . 'catalog/product' . $mediaImagePath;
                        $stringData = $sku . "###" . $baseImageUrl . "\n";
                        // Write the contents back to the file
                        file_put_contents($myFile, $stringData, FILE_APPEND);
                    } else {
                        echo "{$lB} Error while copying file for SKU=>" . $sku . " Filename=>" . $filename . " ImgName=>" . $newImgName;
                    }
                } else {
                    if (copy($rootDir . "adempierstock/imagescript/" . $directory . $filename, $rootDir . "media/catalog/product/{$imgExistingPath}/" . $newImgName)) {
                        $newfileCnt++;
                        updateimagelabel_position($productId, $label, $position, "/{$imgExistingPath}/" . $newImgName, $sku, $ismain, $filename);
                        resize($newImgName, $imgExistingPath);
                        uploadFileToAmazonS3($newImgName, $imgExistingPath);
                        copyInAnotherFolder($filename);
                        disbleImages($productId, $sku);   //Disable less than 1000 pixel images
                        //Append image path and sku for adempier records
                        $myFile = $logFileAdempier;

                        if (!file_exists($rootDir . $adempierDirectory . $myFile)) {
                            $currentFile = fopen($rootDir . $adempierDirectory . $myFile, "w");
                            fclose($currentFile);
                        }
                        $mediaSql = "select value from " . $tablePrefix . "catalog_product_entity_varchar where entity_id='" . $productId . "' and attribute_id='85'"; //77 => media_gallery [itshot_eav_attribute]
                        $mediaCollection = $conn->fetchAll($mediaSql);
                        $mediaImagePath = $mediaCollection[0]['value'];
                        $baseImageUrl = Mage::getBaseUrl('media') . 'catalog/product' . $mediaImagePath;
                        $stringData = $sku . "###" . $baseImageUrl . "\n";
                        // Write the contents back to the file
                        file_put_contents($myFile, $stringData, FILE_APPEND);
                    } else {
                        echo "{$lB} Error while copying file for SKU=>" . $sku . " Filename=>" . $filename . " ImgName=>" . $newImgName;
                    }
                }
            } else {
                //echo "{$lB} SKU =>".$sku." does not exists.";
            }
            $fileCount++;
            //}  //Number of images check condition		   
        } else {
            //echo "{$lB} Issue with image extension.";
        }
    }
}//end while

echo json_encode($respones);
