<?php
set_time_limit(0);
error_reporting(E_ALL ^ E_NOTICE);
ini_set("display_errors", 'On');
session_start();
$root_path = "/home/cloudpanel/htdocs/www.itshot.com/current/";
include($root_path . "adempierstock/youtube/includes/DB.php");
include($root_path . "app/Mage.php");
$app        = Mage::app('default');
$config     = Mage::getConfig()->getResourceConnectionConfig('default_setup');
$dbinfo     = array(
    'host' => $config->host,
    'user' => $config->username,
    'pass' => $config->password,
    'dbname' => $config->dbname
);
$dbHost     = $dbinfo['host'];
$dbUsername = $dbinfo['user'];
$dbPassword = $dbinfo['pass'];
$dbName     = $dbinfo['dbname'];
//Create an object of class DB.
$db         = new DB($dbHost, $dbUsername, $dbPassword, $dbName);
Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

//Create connection object
$conn        = Mage::getSingleton('core/resource')->getConnection('core_read');
$write       = Mage::getSingleton('core/resource')->getConnection('core_write');
$tablePrefix = (string) Mage::getConfig()->getTablePrefix();

//read directory and map video with existing products
$directory = $root_path . "adempierstock/youtube/product_video/";

function moveFileToOld($filename)
{
    global $directory;
    if (copy($directory . $filename, $directory . "old/" . $filename)) {
        unlink($directory . $filename);
    }
}


function selectProductUrlKey($productId)
{
    //Create connection object
    global $lB, $conn, $tablePrefix;
    $productUrl = "";
    $sqlUrl     = "SELECT request_path FROM " . $tablePrefix . "core_url_rewrite WHERE product_id=" . $productId . " AND id_path='product/" . $productId . "'";
    $sqlUrlRes  = $conn->fetchRow($sqlUrl);
    if ($sqlUrlRes["request_path"] != "") {
        $productUrlStr = $baseUrl . $sqlUrlRes["request_path"];
        $productUrl    = $productUrlStr;
    } else {
        $urlSql    = "SELECT value FROM " . $tablePrefix . "catalog_product_entity_varchar WHERE attribute_id=97 AND entity_id=" . $productId;
        $urlSqlRes = $conn->fetchRow($urlSql);
        if ($urlSqlRes["value"] != "") {
            $productUrl = $urlSqlRes["value"];
        }
    }
    return $productUrl;
}
//Added by Ankush at 24-08-2018
function checkAvailableVideo($productId)
{
    //Create connection object
    global $lB, $conn, $tablePrefix;
    $urlSql    = "SELECT value FROM " . $tablePrefix . "catalog_product_entity_text WHERE attribute_id=2046 AND entity_id=" . $productId;
    $urlSqlRes = $conn->fetchRow($urlSql);
    if ($urlSqlRes["value"] != "") {
        $youtubeVideoUrl = $urlSqlRes["value"];
        //https://www.youtube.com/watch?v=CcjogrgiyAw
        $exVideo         = explode("=", $youtubeVideoUrl);
        $videoCode       = $exVideo[1];
    }
    
    return $videoCode;
}



//Added by Ankush at 09-11-2017
function selectProductTitle($productId)
{
    //Create connection object
    global $lB, $conn, $tablePrefix;
    $urlSql    = "SELECT value FROM " . $tablePrefix . "catalog_product_entity_varchar WHERE attribute_id=71 AND entity_id=" . $productId;
    $urlSqlRes = $conn->fetchRow($urlSql);
    if ($urlSqlRes["value"] != "") {
        $ProductTitle = $urlSqlRes["value"];
    }
    
    return $ProductTitle;
}

//Added by Ankush at 03-08-2017(Modified: 09-11-2017)
function selectCategoryUrlKey($productId)
{
    //Create connection object
    global $conn, $tablePrefix;
    $categoryUrl = "";
    //get product categories
    $catSQL      = "SELECT category_id FROM " . $tablePrefix . "catalog_category_product WHERE product_id=" . $productId;
    $categories  = $conn->fetchAll($catSQL);
    $catStr      = array();
    
    foreach ($categories as $cat) {
        $catStr[] = $cat["category_id"];
    }
    if (count($catStr)) {
        $catId            = implode(",", $catStr);
        $catSQL2          = "SELECT `entity_id` FROM `" . $tablePrefix . "catalog_category_entity_int` where `attribute_id`=42 AND `entity_id` IN($catId)";
        $categoriesActive = $conn->fetchAll($catSQL2);
        $catStrActive     = array();
        
        foreach ($categoriesActive as $catActive) {
            $catStrActive[] = $catActive["entity_id"];
        }
        if (count($catStrActive)) {
            $catId = $catStrActive[0];
        }
        
    }
    
    //select category URL
    $sSqlCategory = "SELECT request_path FROM " . $tablePrefix . "core_url_rewrite WHERE category_id=" . $catId;
    $sSqlCategory .= " AND id_path='category/" . $catId . "'";
    $categoryRes = $conn->fetchRow($sSqlCategory);
    if ($categoryRes["request_path"]) {
        $categoryUrl = $categoryRes["request_path"];
    }

    return $categoryUrl;
}

//Added by Ankush at 09-11-2017(Suggested by Denis)
function selectCategoryName($productId)
{
    //Create connection object
    global $conn, $tablePrefix;
    $categoryUrl = "";
    //get product categories
    $catSQL      = "SELECT category_id FROM " . $tablePrefix . "catalog_category_product WHERE product_id=" . $productId;
    $categories  = $conn->fetchAll($catSQL);
    $catStr      = array();
    
    foreach ($categories as $cat) {
        $catStr[] = $cat["category_id"];
    }

    if (count($catStr)) {
        $catId            = implode(",", $catStr);
        $catSQL2          = "SELECT `entity_id` FROM `" . $tablePrefix . "catalog_category_entity_int` where `attribute_id`=42 AND `entity_id` IN($catId)";
        $categoriesActive = $conn->fetchAll($catSQL2);
        $catStrActive     = array();
        
        foreach ($categoriesActive as $catActive) {
            $catStrActive[] = $catActive["entity_id"];
        }
        if (count($catStrActive)) {
            $catId = $catStrActive[0];
        }
    }
    
    //select category alt text
    $sSqlCategoryAlt = "SELECT value FROM " . $tablePrefix . "catalog_category_entity_varchar WHERE entity_id=" . $catId;
    $sSqlCategoryAlt .= " AND attribute_id='229'";
    $categoryResAlt = $conn->fetchRow($sSqlCategoryAlt);
    if ($categoryResAlt["value"]) {
        $categoryAltText = $categoryResAlt["value"];
    }
    //select category name
    $sSqlCategory = "SELECT value FROM " . $tablePrefix . "catalog_category_entity_varchar WHERE entity_id=" . $catId;
    $sSqlCategory .= " AND attribute_id='41'";
    $categoryRes = $conn->fetchRow($sSqlCategory);
    if ($categoryRes["value"]) {
        $categoryName = $categoryRes["value"];
    }
    //set custom URL
    if ($categoryAltText != '') {
        $categoryName = $categoryAltText;
    } else {
        $categoryName = $categoryName;
    }
    
    return $categoryName;
}


if (!($dp = opendir($directory)))
    die("Cannot open $directory.");

$fileCount         = 1;
$availableVideoSku = array();
while ($filename = readdir($dp)) {
    //echo "<br />".$filename; die;
    if (is_dir($filename)) {
        continue;
    } else if ($filename != '.' && $filename != '..') {
        $fileExtensionArr = explode(".", $filename);
        $fileExtension    = strtolower($fileExtensionArr[count($fileExtensionArr) - 1]);
        if (strtolower($fileExtension) == "mp4" || strtolower($fileExtension) == "wmv" || strtolower($fileExtension) == "avi" || strtolower($fileExtension) == "flv") {
            $sku = trim($fileExtensionArr[0]);
            $sqlIdInt = "SELECT entity.entity_id, intpro.value, intpro.attribute_id FROM " . $tablePrefix . "catalog_product_entity AS entity INNER JOIN " . $tablePrefix . "catalog_product_entity_int AS intpro ON entity.entity_id = intpro.entity_id WHERE entity.sku = '" . $sku . "' AND intpro.attribute_id =96";
            $idResInt = $conn->fetchRow($sqlIdInt);
            $status   = $idResInt["value"];
    
            // Accept only active products
            if ($status == 1) {
                // Allow 20 videos one time
                if ($fileCount <= 19) {
                    //Change at 14-07-2017 as denis suggest to post regular description
                    $sSQL = "SELECT p.`entity_id` , p.`sku` , pv.`value` AS name, pt2.`value` AS description, pt.`value` AS keyword, icpev.`value` AS url FROM `" . $tablePrefix . "catalog_product_entity` AS p INNER JOIN `" . $tablePrefix . "catalog_product_entity_varchar` AS pv ON pv.`entity_id` = p.`entity_id` AND pv.`attribute_id` =71 INNER JOIN `" . $tablePrefix . "catalog_product_entity_varchar` AS icpev ON icpev.`entity_id` = p.`entity_id` AND icpev.`attribute_id` =97 INNER JOIN `" . $tablePrefix . "catalog_product_entity_text` AS pt ON pt.`entity_id` = p.`entity_id` AND pt.`attribute_id` =83 INNER JOIN `" . $tablePrefix . "catalog_product_entity_text` AS pt2 ON pt2.`entity_id` = p.`entity_id` AND pt2.`attribute_id` =72 WHERE p.`sku` = '{$sku}' GROUP BY p.`entity_id`";
                    
                    $data      = $conn->fetchRow($sSQL);
                    $productId = $data['entity_id'];
                    //Only not available video uplaod at YouTube 
                    $videoCode = checkAvailableVideo($productId);
                    $videoTitle   = trim($data['name']);
                    $videoDesTemp = trim($data['description']);
                    $videoTags    = trim($data['keyword']);
                    $sku          = $data['sku']; //die;			
                    if ($data['entity_id'] != "") {
                        $product_id = $data['entity_id'];
                        $urlKey     = selectProductUrlKey($product_id);
                        if ($urlKey == "") {
                            echo "\n URL Key does not exists.";
                            continue;
                        }
                        $url_key = $urlKey . "." . $fileExtension;
                        
                        //$productURL   = selectProductUrlKey($product_id);  //get product URL
                        $categoryAltAndName = selectCategoryName($productId); //get category Name
                        $productURL         = selectProductUrlKey($product_id); //get product URL
                        $productTitle       = selectProductTitle($product_id); //get product Title added at 09-11-2017
                        $categoryUrl        = selectCategoryUrlKey($product_id); // get category URL added at 03-08-2017
                        
                        //Denis said to change description of prodcut at 9 Nov 2017
                        $siteUrl     = "https://www.itshot.com";
                        $itemDetails = "Website: " . $siteUrl . "\n";
                        $itemDetails .= "View this " . $productTitle . " here: " . $siteUrl . "/" . $productURL;
                        $itemDetails .= "\nView more " . $categoryAltAndName . " here: " . $siteUrl . "/" . $categoryUrl . "\n";
                        $itemDetailsSku = "\nITEM CODE: " . $sku;
                        
                        //$videoDesc = $videoDesTemp." ".$itemDetails; 
                        $videoDesc       = $itemDetails . " " . $videoDesTemp . " " . $itemDetailsSku; //added at 29-08-2017	
                        $video_type      = 'new';
                       
                        if (copy($root_path . "adempierstock/youtube/product_video/" . $sku . "." . $fileExtension, $root_path . "media/catalog/product/video/" . $url_key)) {
                            //delete existing Video record from DB 
                            $delSQLYou = "DELETE FROM tmp_videos WHERE productid=" . $product_id;
                            $write->query($delSQLYou);
                            $db->insert($videoTitle, $videoDesc, $videoTags, $url_key, $product_id, $sku, $video_type);
                            // delete video file from local folder 
                            $result = $db->getLastRow(); 
                            @unlink($result['video_path']);
                            
                            // Move item video in the backup folder(old)
                            moveFileToOld($result['sku'].'.'.$fileExtension);
                        }
                    } else {
                        echo "\n SKU " . $sku . " doest not exists in database.";
                    }
                    $fileCount++;                    	
                } // Check available 20 videos
                
            } //Close active case
        } //Close video extention check.
    } // Read files
} // End While Loop	
closedir($dp);
//ob_end_flush();

if ($availableVideoSku > 0) {
    sendEmailExistingVideoNotification($availableVideoSku);
}

function sendEmailExistingVideoNotification($availableVideoSku)
{
    //Send email using Php Mailer and Amazon SES server
    $videoSku = array_unique($availableVideoSku);
    $items    = implode(",", $videoSku);
    if ($items != '') {
        $message = "Found some item videos that are already exist at YouTube. Please review these videos.";
        $message .= " SKU: " . $items;
        
        $to      = "dstepans@itshot.com";
        $subject = "ItsHot Notification: During upload the video found some videos are available at YouTube.";
        
        // send email
        $mail = Mage::getModel('core/email')->setToEmail($to)->setBody($message)->setSubject($subject)->setFromEmail('dstepans@itshot.com')->setFromName('Magento Store Admin')->setType('html');
        //  $mail->send(); 
        if (!$mail->Send()) {
            echo "\n Email NOT sent.";
        } else {
            //echo "\n Email sent.";
        }
    }
}

?> 
<!DOCTYPE html>
<html>
<head>
<title>Upload video to YouTube using PHP</title>
<link rel="stylesheet" type="text/css" href="css/style.css"/>
</head>
<body>
<div class="youtube-box">
	<h1>Upload video to YouTube using PHP</h1>
	<div class="video-up"><a href="<?php
echo BASE_URI;
?>">New Upload</a></div>
	<div class="content">
		<?php
echo $htmlBody;
?>
	</div>
</div>
</div>
</body>
</html>
