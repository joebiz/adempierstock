<?php
set_time_limit(0);
error_reporting(E_ALL ^ E_NOTICE);
ini_set("display_errors",'On');
session_start();
$root_path = "/home/cloudpanel/htdocs/www.itshot.com/current/";
//include($root_path."app/Mage.php");
include($root_path."adempierstock/youtube/config_youtube_legacy.php");
include($root_path."adempierstock/youtube/includes/DB.php");
include($root_path."app/Mage.php");
$app = Mage::app('default');
$config  = Mage::getConfig()->getResourceConnectionConfig('default_setup');
$dbinfo = array('host' => $config->host,
            'user' => $config->username,
            'pass' => $config->password,
            'dbname' => $config->dbname
);
$dbHost      = $dbinfo['host'];
$dbUsername  = $dbinfo['user'];
$dbPassword  = $dbinfo['pass'];
$dbName      = $dbinfo['dbname'];
//Create an object of class DB.
$db = new DB($dbHost,$dbUsername,$dbPassword,$dbName);


Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
Mage::App('pbo');
/*
$filePostFix	= "_".date("d-m-Y_H-i-s").".txt";
$logFile		= $root_path."feeds/youtube/log/log_import-videos-legacy".$filePostFix;

function ob_file_callback($buffer)
{
	global $ob_file;
	fwrite($ob_file, $buffer);
}
$ob_file = fopen($logFile, "w");
//ob_start("ob_file_callback");
*/
//Create connection object
$conn        = Mage::getSingleton('core/resource')->getConnection('core_read');
$write       = Mage::getSingleton('core/resource')->getConnection('core_write');
$tablePrefix = (string)Mage::getConfig()->getTablePrefix();	

//read directory and map video with existing products
$directory = $root_path."adempierstock/youtube/product_video/";

function moveFileToOld($filename)
{
	global $directory;	
	if(copy($directory.$filename, $directory."old/".$filename))
	{
		unlink($directory.$filename);
	}
}

 
function selectProductUrlKey($productId)
{ 
	//Create connection object
	global $lB, $conn,$tablePrefix;
	$productUrl = "";
	$sqlUrl = "SELECT request_path FROM ".$tablePrefix."core_url_rewrite WHERE product_id=".$productId." AND id_path='product/".$productId."'";
	$sqlUrlRes = $conn->fetchRow($sqlUrl);
	if($sqlUrlRes["request_path"]!="")
	{
		$productUrlStr = $baseUrl.$sqlUrlRes["request_path"];
		//code commented on 01-11-2017 due to .aspx remove from URL
		//$productUrl = substr($productUrlStr, 0, strlen($productUrlStr)-5);
		$productUrl = $productUrlStr;
	}
	else
	{
		$urlSql = "SELECT value FROM ".$tablePrefix."catalog_product_entity_varchar WHERE attribute_id=97 AND entity_id=".$productId;
		$urlSqlRes = $conn->fetchRow($urlSql);
		if($urlSqlRes["value"]!="")
		{
			$productUrl = $urlSqlRes["value"];
		}
	}	
	return $productUrl;
}

//Added by Ankush at 24-08-2018
function checkAvailableVideo($productId)
{
	//Create connection object
	global $lB, $conn,$tablePrefix;
	$urlSql = "SELECT value FROM ".$tablePrefix."catalog_product_entity_text WHERE attribute_id=2046 AND entity_id=".$productId;
		$urlSqlRes = $conn->fetchRow($urlSql);
		if($urlSqlRes["value"]!="")
		{
			$youtubeVideoUrl = $urlSqlRes["value"];
			//https://www.youtube.com/watch?v=CcjogrgiyAw
			$exVideo   = explode("=",$youtubeVideoUrl);
			$videoCode = $exVideo[1]; 
		}
	
	return $videoCode;
}



//Added by Ankush at 09-11-2017
function selectProductTitle($productId)
{
	//Create connection object
	global $lB, $conn,$tablePrefix;
	$urlSql = "SELECT value FROM ".$tablePrefix."catalog_product_entity_varchar WHERE attribute_id=71 AND entity_id=".$productId;
		$urlSqlRes = $conn->fetchRow($urlSql);
		if($urlSqlRes["value"]!="")
		{
			$ProductTitle = $urlSqlRes["value"];
		}
	
	return $ProductTitle;
}

//Added by Ankush at 03-08-2017(Modified: 09-11-2017)
function selectCategoryUrlKey($productId)
{
		//Create connection object
		global $conn,$tablePrefix;
		$categoryUrl = "";
	    //get product categories
		$catSQL = "SELECT category_id FROM ".$tablePrefix."catalog_category_product WHERE product_id=".$productId;
		$categories	= $conn->fetchAll($catSQL);
		$catStr = array();

		foreach ($categories as $cat)
		{
			$catStr[] = $cat["category_id"];
		}
		if(count($catStr))
		{
			$catId = implode(",",$catStr);
			$catSQL2 = "SELECT `entity_id` FROM `".$tablePrefix."catalog_category_entity_int` where `attribute_id`=42 AND `entity_id` IN($catId)";
			$categoriesActive	= $conn->fetchAll($catSQL2);
			$catStrActive = array();

			foreach ($categoriesActive as $catActive)
			{
			$catStrActive[] = $catActive["entity_id"];
			}
			if(count($catStrActive))
			{
			$catId = $catStrActive[0];
			}
			
			//$catId = $catStr[0];
		}
		
		    //select category URL
			$sSqlCategory = "SELECT request_path FROM ".$tablePrefix."core_url_rewrite WHERE category_id=".$catId;
			$sSqlCategory .= " AND id_path='category/".$catId."'";
			$categoryRes = $conn->fetchRow($sSqlCategory);
			if($categoryRes["request_path"])
			{
				$categoryUrl = $categoryRes["request_path"];
			}
			
						
 return $categoryUrl;		
	
}

//Added by Ankush at 09-11-2017(Suggested by Denis)
function selectCategoryName($productId)
{
		//Create connection object
		global $conn,$tablePrefix;
		$categoryUrl = "";
	    //get product categories
		$catSQL = "SELECT category_id FROM ".$tablePrefix."catalog_category_product WHERE product_id=".$productId;
		$categories	= $conn->fetchAll($catSQL);
		$catStr = array();

		foreach ($categories as $cat)
		{
			$catStr[] = $cat["category_id"];
		}
		/*
		if(count($catStr))
		{
			$catId = $catStr[0];
		}
		*/
		if(count($catStr))
		{
			$catId = implode(",",$catStr);
			$catSQL2 = "SELECT `entity_id` FROM `".$tablePrefix."catalog_category_entity_int` where `attribute_id`=42 AND `entity_id` IN($catId)";
			$categoriesActive	= $conn->fetchAll($catSQL2);
			$catStrActive = array();

			foreach ($categoriesActive as $catActive)
			{
			$catStrActive[] = $catActive["entity_id"];
			}
			if(count($catStrActive))
			{
			$catId = $catStrActive[0];
			}
			
			//$catId = $catStr[0];
		}
		    
		    //select category alt text
			$sSqlCategoryAlt = "SELECT value FROM ".$tablePrefix."catalog_category_entity_varchar WHERE entity_id=".$catId;
			$sSqlCategoryAlt .= " AND attribute_id='229'";
			$categoryResAlt = $conn->fetchRow($sSqlCategoryAlt);
			if($categoryResAlt["value"])
			{
				$categoryAltText = $categoryResAlt["value"];
			}		
		    //select category name
			$sSqlCategory = "SELECT value FROM ".$tablePrefix."catalog_category_entity_varchar WHERE entity_id=".$catId;
			$sSqlCategory .= " AND attribute_id='41'";
			$categoryRes = $conn->fetchRow($sSqlCategory);
			if($categoryRes["value"])
			{
				$categoryName = $categoryRes["value"];
			}			
			//set custom URL
			if($categoryAltText !='')
			{
				$categoryName = $categoryAltText;
			}
			else
			{
			  $categoryName = $categoryName; 
			}
			
			
 return $categoryName;		
	
}


if(!($dp = opendir($directory))) die("Cannot open $directory.");

$fileCount         = 1;
$availableVideoSku = array();
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
		if(strtolower($fileExtension) == "mp4" || strtolower($fileExtension) == "wmv" || strtolower($fileExtension) =="avi" || strtolower($fileExtension) =="flv")
		{
			$sku = trim($fileExtensionArr[0]); 
			//$sSQL = "SELECT icpe.entity_id,icpev.value FROM itshot_catalog_product_entity icpe,itshot_catalog_product_entity_varchar icpev WHERE icpe.sku='{$sku}' and icpe.entity_id=icpev.entity_id and icpev.attribute_id=86";
			
			$sqlIdInt = "SELECT entity.entity_id, intpro.value, intpro.attribute_id FROM ".$tablePrefix."catalog_product_entity AS entity INNER JOIN ".$tablePrefix."catalog_product_entity_int AS intpro ON entity.entity_id = intpro.entity_id WHERE entity.sku = '".$sku."' AND intpro.attribute_id =96";		
			$idResInt = $conn->fetchRow($sqlIdInt);
			$status   = $idResInt["value"]; 
						
			if($status == 2)
			{  
			//if($sku =='009805' || $sku == '009422')
			if($fileCount <=19)			
			{
									
			//Change at 14-07-2017 as denis suggest to post regular description
			 $sSQL = "SELECT p.`entity_id` , p.`sku` , pv.`value` AS name, pt2.`value` AS description, pt.`value` AS keyword, icpev.`value` AS url FROM `".$tablePrefix."catalog_product_entity` AS p INNER JOIN `".$tablePrefix."catalog_product_entity_varchar` AS pv ON pv.`entity_id` = p.`entity_id` AND pv.`attribute_id` =71 INNER JOIN `".$tablePrefix."catalog_product_entity_varchar` AS icpev ON icpev.`entity_id` = p.`entity_id` AND icpev.`attribute_id` =97 INNER JOIN `".$tablePrefix."catalog_product_entity_text` AS pt ON pt.`entity_id` = p.`entity_id` AND pt.`attribute_id` =83 INNER JOIN `".$tablePrefix."catalog_product_entity_text` AS pt2 ON pt2.`entity_id` = p.`entity_id` AND pt2.`attribute_id` =72 WHERE p.`sku` = '{$sku}' GROUP BY p.`entity_id`"; 
			 
			$data = $conn->fetchRow($sSQL);
			//echo "<pre>";print_r($data);die; 
					
			$videoTitle    = trim($data['name']);	
			$videoDesTemp  = trim($data['description']);			
			$videoTags     = trim($data['keyword']);
			$sku           = $data['sku']; //die;

			$productId          = $data['entity_id'];
			$videoCode          = checkAvailableVideo($productId);
			if($videoCode !='')
			{
				$availableVideoSku[] = $sku;
			}
			else
			{
			
	      	if($data['entity_id']!="")
			{
				$product_id = $data['entity_id']; 
				$urlKey     = selectProductUrlKey($product_id);
				if($urlKey=="")
				{
					echo "\n URL Key does not exists.";
					continue;
				}
				$url_key	= $urlKey.".".$fileExtension;
				
				//$productURL   = selectProductUrlKey($product_id);  //get product URL
				$categoryAltAndName   = selectCategoryName($productId);    //get category Name
				$productURL           = selectProductUrlKey($product_id);  //get product URL
				$productTitle         = selectProductTitle($product_id);  //get product Title added at 09-11-2017
				$categoryUrl          = selectCategoryUrlKey($product_id); // get category URL added at 03-08-2017
				
				//Denis say to enable prodcut URL at 2 Aug 2017
				//$itemDetails  = "\nITEM CODE: ".$sku."\nProduct URL: www.ItsHot.com"; //Return Home page URL
				//$itemDetails  = "\nITEM CODE: ".$sku."\nProduct URL: http://www.itshot.com/".$productURL.".aspx";  // Return product URL
				//$itemDetails  = "\nITEM CODE: ".$sku."\nProduct URL: http://www.itshot.com/".$categoryUrl;  //Return category URL	
				
				//Its commented at 9 Nov 2017(Its previous code)
				//$siteUrl = "http://www.itshot.com";				
				//$itemDetails  = "Website: ".$siteUrl."\n"; 
				//$itemDetails  .= $categoryName." URL: ".$siteUrl."/".$productURL.".aspx";
				//$itemDetails  .= "\nView more ".$categoryName." here: ".$siteUrl."/".$categoryUrl."\n";
				//$itemDetailsSku  = "\nITEM CODE: ".$sku;	
				
				//Denis say to change description of prodcut at 9 Nov 2017
				$siteUrl         = "https://www.itshot.com";				
				$itemDetails     = "Website: ".$siteUrl."\n";  
				$itemDetails    .= "View this ".$productTitle." here: ".$siteUrl."/".$productURL;
				$itemDetails    .= "\nView more ".$categoryAltAndName." here: ".$siteUrl."/".$categoryUrl."\n";
				$itemDetailsSku  = "\nITEM CODE: ".$sku;			
				
				//$videoDesc = $videoDesTemp." ".$itemDetails; 
				$videoDesc = $itemDetails." ".$videoDesTemp." ".$itemDetailsSku; 	//added at 29-08-2017	
				$video_type = 'new';
				//echo $videoDesc ;die;
			   $sqlproductvideo = "SELECT value FROM ".$tablePrefix."catalog_product_entity_text WHERE entity_id='".$product_id."' AND attribute_id=2046";
				$resproductvideo = $conn->fetchRow($sqlproductvideo);
				//echo "dddddd";print_r($resproductvideo);echo "cccc";
				if(isset($resproductvideo["value"]) && $resproductvideo["value"] !='' && $resproductvideo["value"] !='NULL')
				{ //echo "case 1";die;
					//unlink($root_path."media/catalog/product/video/".$url_key);
					//deleteFileFromAmazonS3($url_key);
					
					if(copy($root_path."adempierstock/youtube/product_video/".$sku.".".$fileExtension, $root_path."media/catalog/product/video/".$url_key))
					{
			
			       /*
					if($resproductvideo["video_url"] =='')
					{
						$insSQLUpdate = "UPDATE tsht_catalog_product_entity_text set value ='".$url_key."' WHERE product_id='".$product_id."' ";
						///$write->query($insSQLUpdate);
					}					
					*/
						//delete existing Video record from DB 
						$delSQLYou = "DELETE FROM tmp_videos WHERE productid=".$product_id;
						$write->query($delSQLYou);
						
						$db->insert($videoTitle,$videoDesc,$videoTags,$url_key,$product_id,$sku,$video_type);
												
						// get last video data
						$result = $db->getLastRow(); 

						//echo "<pre>";print_r($result);die;
						/*
						* You can acquire an OAuth 2.0 client ID and client secret from the
						* Google Developers Console <https://console.developers.google.com/>
						* For more information about using OAuth 2.0 to access Google APIs, please see:
						* <https://developers.google.com/youtube/v3/guides/authentication>
						* Please ensure that you have enabled the YouTube Data API for your project.
						*/
						//print_r($_GET);
						//print_r($client);
 
						if (isset($_GET['code'])) {
						
						$client->authenticate(trim($_GET['code']));
						$_SESSION['token'] = $client->getAccessToken();
						header('Location: ' . REDIRECT_URI);
						}
						if (isset($_SESSION['token'])) {
						$client->setAccessToken($_SESSION['token']);
						}

$htmlBody = '';
// Check to ensure that the access token was successfully acquired.
if ($client->getAccessToken()) {
  try{
	  
	  //$client->setAccessType("offline");
      //$client->setApprovalPrompt("force");

		if($client->isAccessTokenExpired()) {
         //echo FILTER_SANITIZE_URL;die;
         
		$authUrl = $client->createAuthUrl(); 
		//echo  filter_var($authUrl, FILTER_SANITIZE_URL);die;
		header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));

		}

	// REPLACE this value with the path to the file you are uploading.
	//$videoPath = '/var/www/ItsHot/releases/media/catalog/product/video/'.$result['video_path'];
	$videoPath = '/home/cloudpanel/htdocs/www.itshot.com/current/media/catalog/product/video/'.$result['video_path'];
    
    
    
    
	// Create a snippet with title, description, tags and category ID
	// Create an asset resource and set its snippet metadata and type.
	// This example sets the video's title, description, keyword tags, and
	// video category.
	$snippet = new Google_Service_YouTube_VideoSnippet();
	$snippet->setTitle($result['video_title']);
	$snippet->setDescription($result['video_description']);
	$snippet->setTags(explode(",",$result['video_tags']));

	// Numeric video category. See
	// https://developers.google.com/youtube/v3/docs/videoCategories/list 
	//$snippet->setCategoryId("22");  //for People & Blogs
	$snippet->setCategoryId("24"); // for entertainment category

	// Set the video's status to "public". Valid statuses are "public",
	// "private" and "unlisted".
	$status = new Google_Service_YouTube_VideoStatus();
	$status->privacyStatus = "public";

	// Associate the snippet and status objects with a new video resource.
	$video = new Google_Service_YouTube_Video();
	$video->setSnippet($snippet);
	$video->setStatus($status);

	// Specify the size of each chunk of data, in bytes. Set a higher value for
	// reliable connection as fewer chunks lead to faster uploads. Set a lower
	// value for better recovery on less reliable connections.
	$chunkSizeBytes = 1 * 1024 * 1024;

	// Setting the defer flag to true tells the client to return a request which can be called
	// with ->execute(); instead of making the API call immediately.
	$client->setDefer(true);

	// Create a request for the API's videos.insert method to create and upload the video.
	$insertRequest = $youtube->videos->insert("status,snippet", $video);

	// Create a MediaFileUpload object for resumable uploads.
	$media = new Google_Http_MediaFileUpload(
		$client,
		$insertRequest,
		'video/*',
		null,
		true, 
		$chunkSizeBytes
	);
	$media->setFileSize(filesize($videoPath));

	// Read the media file and upload it.
	$status = false;
	$handle = fopen($videoPath, "rb");
	while (!$status && !feof($handle)) {
	  $chunk = fread($handle, $chunkSizeBytes);
	  $status = $media->nextChunk($chunk);
	}
	fclose($handle);

	// If you want to make other calls after the file upload, set setDefer back to false
	$client->setDefer(false);
	
	// Update youtube video ID to database
	$db->update($result['video_id'],$status['id']);
	//Update youtube video code in catalog table to database
	$db->updateYoutubecode($result['productid'],$status['id']);
	// delete video file from local folder 
	@unlink($result['video_path']);
	
	// Move item video in the backup folder(old)
	moveFileToOld($result['sku'].'.'.$fileExtension);
	
	$htmlBody .= "<p class='succ-msg'>Video have been uploaded successfully.</p><ul>";
	$htmlBody .= '<embed width="400" height="315" src="https://www.youtube.com/embed/'.$status['id'].'"></embed>';
	$htmlBody .= '<li><b>Title: </b>'.$status['snippet']['title'].'</li>';
	$htmlBody .= '<li><b>Description: </b>'.$status['snippet']['description'].'</li>';
	$htmlBody .= '<li><b>Tags: </b>'.implode(",",$status['snippet']['tags']).'</li>';
	$htmlBody .= '</ul>';
	$htmlBody .= '<a href="logout.php">Logout</a>';

  } catch (Google_ServiceException $e) {
	$htmlBody .= sprintf('<p>A service error occurred: <code>%s</code></p>',
		htmlspecialchars($e->getMessage()));
  } catch (Google_Exception $e) {
	$htmlBody .= sprintf('<p>An client error occurred: <code>%s</code></p>', htmlspecialchars($e->getMessage()));
	$htmlBody .= 'Please reset session <a href="logout.php">Logout</a>';
  }
  // code commented by Ankush at 24 August
  //$_SESSION['token'] = $client->getAccessToken();
} else {  
	// If the user hasn't authorized the app, initiate the OAuth flow
	$state = mt_rand();
	$client->setState($state);
	$_SESSION['state'] = $state;
  
	$authUrl = $client->createAuthUrl();
	
	$htmlBody = <<<END
	<h3>Authorization Required</h3>
	<p>You need to <a href="$authUrl">authorize access</a> before proceeding.<p>
END;
}


					}
				}
				else
				{  //echo "case 2";die;
					if(copy($root_path."adempierstock/youtube/product_video/".$sku.".".$fileExtension, $root_path."media/catalog/product/video/".$url_key))
					{
						
					$sqlproductvideo2 = "SELECT value_id FROM ".$tablePrefix."catalog_product_entity_text WHERE entity_id='".$product_id."' AND attribute_id=2046";
					$resproductvideo2 = $conn->fetchRow($sqlproductvideo2);
				    //print_r($resproductvideo );die;
				    if($resproductvideo2["value_id"] =='')
				    {
						$insSQL = "INSERT INTO ".$tablePrefix."catalog_product_entity_text(entity_id, attribute_id,) VALUES ('".$product_id."','2046')";
						$write->query($insSQL);
						//echo "\n".$sku." Added";
				    } 
						//unlink($root_path."db/product_video/".$sku.".mp4");
						//moveFileToOld($filename); 
						 
						//delete existing Video record from DB 
						$delSQLYou2 = "DELETE FROM tmp_videos WHERE productid=".$product_id;
						$write->query($delSQLYou2);
						
						$db->insert($videoTitle,$videoDesc,$videoTags,$url_key,$product_id,$sku,$video_type);
						//saveProduct($product_id);
						
						// get last video data
$result = $db->getLastRow(); 
/*
 * You can acquire an OAuth 2.0 client ID and client secret from the
 * Google Developers Console <https://console.developers.google.com/>
 * For more information about using OAuth 2.0 to access Google APIs, please see:
 * <https://developers.google.com/youtube/v3/guides/authentication>
 * Please ensure that you have enabled the YouTube Data API for your project.
 */
//echo "<pre>";print_r($_GET);
//print_r($client);
//echo "</pre>pre>";
if (isset($_GET['code'])) {
	if (strval($_SESSION['state']) !== strval($_GET['state'])) {
	  die('The session state did not match.');
	}
	$client->authenticate(trim($_GET['code']));
	$_SESSION['token'] = $client->getAccessToken();
	header('Location: ' . REDIRECT_URI);
}
if (isset($_SESSION['token'])) {
	$client->setAccessToken($_SESSION['token']);
}

$htmlBody = '';
// Check to ensure that the access token was successfully acquired.
if ($client->getAccessToken()) {
  try{
	  
	 // $client->setAccessType("offline");
     //$client->setApprovalPrompt("force");

	 if($client->isAccessTokenExpired()) {
       	$authUrl = $client->createAuthUrl(); 
		header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
		}
    
    // REPLACE this value with the path to the file you are uploading.
    //$videoPath = '/var/www/ItsHot/releases/media/catalog/product/video/'.$result['video_path']; 
    $videoPath = '/home/cloudpanel/htdocs/www.itshot.com/current/media/catalog/product/video/'.$result['video_path']; 
    
    // Create a snippet with title, description, tags and category ID
    // Create an asset resource and set its snippet metadata and type.
    // This example sets the video's title, description, keyword tags, and
    // video category.
    $snippet = new Google_Service_YouTube_VideoSnippet();
    $snippet->setTitle($result['video_title']);
    $snippet->setDescription($result['video_description']);
    $snippet->setTags(explode(",",$result['video_tags']));
    // Numeric video category. See
    // https://developers.google.com/youtube/v3/docs/videoCategories/list 
   	//$snippet->setCategoryId("22");  //for People & Blogs
	$snippet->setCategoryId("24"); // for entertainment category
    // Set the video's status to "public". Valid statuses are "public",
    // "private" and "unlisted".
    $status = new Google_Service_YouTube_VideoStatus();
    $status->privacyStatus = "public";

    // Associate the snippet and status objects with a new video resource.
    $video = new Google_Service_YouTube_Video();
    $video->setSnippet($snippet);
    $video->setStatus($status);
    // Specify the size of each chunk of data, in bytes. Set a higher value for
    // reliable connection as fewer chunks lead to faster uploads. Set a lower
    // value for better recovery on less reliable connections.
    $chunkSizeBytes = 1 * 1024 * 1024;
    // Setting the defer flag to true tells the client to return a request which can be called
    // with ->execute(); instead of making the API call immediately.
    $client->setDefer(true);
    // Create a request for the API's videos.insert method to create and upload the video.
    $insertRequest = $youtube->videos->insert("status,snippet", $video);

    // Create a MediaFileUpload object for resumable uploads.
    $media = new Google_Http_MediaFileUpload(
        $client,
        $insertRequest,
        'video/*',
        null,
        true,
        $chunkSizeBytes
    );
    $media->setFileSize(filesize($videoPath));

    // Read the media file and upload it.
    $status = false;
    $handle = fopen($videoPath, "rb");
    while (!$status && !feof($handle)) {
      $chunk = fread($handle, $chunkSizeBytes);
      $status = $media->nextChunk($chunk);
    }
    fclose($handle);

    // If you want to make other calls after the file upload, set setDefer back to false
    $client->setDefer(false);
	
	// Update youtube video ID to database
	$db->update($result['video_id'],$status['id']);
	
	//Update youtube video code in catalog table to database
	$db->updateYoutubecode($result['productid'],$status['id']);
	
	// delete video file from local folder 
	@unlink($result['video_path']);
	
	// Move item video in the backup folder(old)
	moveFileToOld($result['sku'].'.'.$fileExtension);
		
    $htmlBody .= "<p class='succ-msg'>Video have been uploaded successfully.</p><ul>";
	$htmlBody .= '<embed width="400" height="315" src="https://www.youtube.com/embed/'.$status['id'].'"></embed>';
	$htmlBody .= '<li><b>Title: </b>'.$status['snippet']['title'].'</li>';
	$htmlBody .= '<li><b>Description: </b>'.$status['snippet']['description'].'</li>';
	$htmlBody .= '<li><b>Tags: </b>'.implode(",",$status['snippet']['tags']).'</li>';
    $htmlBody .= '</ul>';
	$htmlBody .= '<a href="logout.php">Logout</a>';

  } catch (Google_ServiceException $e) {
    $htmlBody .= sprintf('<p>A service error occurred: <code>%s</code></p>',
        htmlspecialchars($e->getMessage()));
  } catch (Google_Exception $e) {
    $htmlBody .= sprintf('<p>An client error occurred: <code>%s</code></p>', htmlspecialchars($e->getMessage()));
	$htmlBody .= 'Please reset session <a href="logout.php">Logout</a>';
  }
  //code commented by Ankush at 24 August 2016
  //$_SESSION['token'] = $client->getAccessToken();
} else {  
	// If the user hasn't authorized the app, initiate the OAuth flow
	$state = mt_rand();
	$client->setState($state);
	$_SESSION['state'] = $state;
  
	$authUrl = $client->createAuthUrl();
	$htmlBody = <<<END
	<h3>Authorization Required</h3>
	<p>You need to <a href="$authUrl">authorize access</a> before proceeding.<p>
END;
}
					}
				}
			}
			else
			{
				echo "\n SKU ".$sku." doest not exists in database.";
			}
			$fileCount++;
			//die;
			
		   } // videocode condition end. 
		}
	 } // close active case
		
  }
}
}
	
closedir($dp);
//ob_end_flush();

if($availableVideoSku > 0)
{
  sendEmailExistingVideoNotification($availableVideoSku);
		
}

function sendEmailExistingVideoNotification($availableVideoSku)
{
	
	//Send email using Php Mailer and Amazon SES server
	$videoSku = array_unique($availableVideoSku);
	$items = implode(",",$videoSku);
	
	$message = "Found some item video that is already exist at YouTube:";
	$message .= " SKU: ".$items;
	
	$to = "ankush@onsinteractive.com";
	$subject = "ItsHot Notification:  Available videos at YouTube";
	
	// send email
	  $mail = Mage::getModel('core/email')
	 ->setToEmail('ankush@onsinteractive.com')
	 ->setBody($message)
	 ->setSubject($subject)
	 ->setFromEmail('ankush@onsinteractive.com')
	 ->setFromName('Magento Store Admin')
	 ->setType('html');
	//  $mail->send(); 
	if(!$mail->Send())
	{
		echo "\n Email NOT sent.";
	}
	else
	{
		//echo "\n Email sent.";
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
	<div class="video-up"><a href="<?php echo BASE_URI; ?>">New Upload</a></div>
	<div class="content">
		<?php echo $htmlBody; ?>
	</div>
</div>
</div>
</body>
</html>
