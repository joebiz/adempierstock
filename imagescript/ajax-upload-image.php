<?php

set_time_limit(0);
error_reporting(E_ALL ^ E_NOTICE);
ini_set("display_errors", 'On');

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

$filePostFix = "_" . date("d-m-Y_H-i-s") . ".txt";
$logFile = $rootDir . "adempierstock/imagescript/log/log_override-images" . $filePostFix;
$logFileWebpath = "https://www.itshot.com/adempierstock/imagescript/log/log_override-images" . $filePostFix;

$conn = Mage::getSingleton('core/resource')->getConnection('core_read');
$write = Mage::getSingleton('core/resource')->getConnection('core_write');
$tablePrefix = (string) Mage::getConfig()->getTablePrefix();

if (!($dp = opendir($imageDirectory)))
    die("Cannot open $directory.");

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


$images = getImages();
$status = fetchStatus('status.log', function() {
    global $images;

    $total_image_files = count($images);
    return [
        'state' => 'init',
        'total_image_files' => $total_image_files,
        'total_image_uploaded' => 0,
        'exclude_files' => []
    ];
});
$exclude_files = $status['exclude_files'];
$filename = getFirstImage($images, $exclude_files);

if (($status['state'] == 'init' || $status['state'] == 'processing') && $filename) {
    $lock = Mage::getSingleton("core/resource")->getConnection("core_write");
    $keyLock = 'lock_file_state_' . basename($filename);

    if ($lock->fetchOne("SELECT GET_LOCK('{$keyLock}', 0)")) {
        $shouldExclude = false;
        $imageInfo = parseImage($filename);
        if ($imageInfo) {
            list($sku, $appendStr, $fileExtension) = $imageInfo;
            $getEntityId = "SELECT entity_id FROM `" . $tablePrefix . "catalog_product_entity` WHERE `sku` = '" . $sku . "'";
            $entityIdResult = $conn->fetchCol($getEntityId);
            $entityId = $entityIdResult[0];
            if ($entityId) {
                $imageNameSql = "SELECT * FROM `" . $tablePrefix . "catalog_product_entity_media_gallery` AS mgallery, " . $tablePrefix . "catalog_product_entity_media_gallery_value AS mgalleryvalue WHERE entity_id =  '" . $entityId . "' AND mgallery.value_id = mgalleryvalue.value_id  AND mgalleryvalue.disabled = 0 AND store_id = 0";
                $imageNameResult = $conn->fetchAll($imageNameSql);
                $productId = $entityId;

                $urlKey = selectProductUrlKey($productId);
                if ($urlKey == "") {
                    $urlSQL = "SELECT value FROM " . $tablePrefix . "catalog_product_entity_varchar WHERE attribute_id=97 AND entity_id=" . $productId;
                    $urlSQLRes = $conn->fetchRow($urlSQL);
                    $urlKey = $urlSQLRes["value"];
                }

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
                $oldimage = getoldimage($appendStr, $productId);
                $imgname = basename($oldimage);
                $imgpath = str_replace($imgname, "", $oldimage);

                $imgExistingPath = getExistingImagePath($productId, $imgname, $sku);
                if (file_exists($rootDir . "media/catalog/product" . $oldimage)) {
                    unlink($rootDir . "media/catalog/product" . $oldimage);
                    deleteFileFromAmazonS3($imgname, $imgExistingPath);
                    if (copy($rootDir . "adempierstock/imagescript/" . $directory . $filename, $rootDir . "media/catalog/product" . $oldimage)) {
                        updateimagelabel_position($productId, $label, $position, $oldimage, $sku, $ismain, $filename);
                        resize($imgname, $imgExistingPath);
                        uploadFileToAmazonS3($imgname, $imgExistingPath);
                        copyInAnotherFolder($filename);
                        disbleImages($productId, $sku);
                        $myFile = $logFileAdempier;

                        if (!file_exists($rootDir . $adempierDirectory . $myFile)) {
                            $currentFile = fopen($rootDir . $adempierDirectory . $myFile, "w");
                            fclose($currentFile);
                        }

                        $mediaSql = "select value from " . $tablePrefix . "catalog_product_entity_varchar where entity_id='" . $productId . "' and attribute_id='85'";
                        $mediaCollection = $conn->fetchAll($mediaSql);
                        $mediaImagePath = $mediaCollection[0]['value'];
                        $baseImageUrl = Mage::getBaseUrl('media') . 'catalog/product' . $mediaImagePath;
                        $stringData = $sku . "###" . $baseImageUrl . "\n";
                        file_put_contents($myFile, $stringData, FILE_APPEND);
                    } else {
                        $shouldExclude = true;
                    }
                } else {
                    if (copy($rootDir . "adempierstock/imagescript/" . $directory . $filename, $rootDir . "media/catalog/product/{$imgExistingPath}/" . $newImgName)) {
                        updateimagelabel_position($productId, $label, $position, "/{$imgExistingPath}/" . $newImgName, $sku, $ismain, $filename);
                        resize($newImgName, $imgExistingPath);
                        uploadFileToAmazonS3($newImgName, $imgExistingPath);
                        copyInAnotherFolder($filename);
                        disbleImages($productId, $sku);
                        $myFile = $logFileAdempier;

                        if (!file_exists($rootDir . $adempierDirectory . $myFile)) {
                            $currentFile = fopen($rootDir . $adempierDirectory . $myFile, "w");
                            fclose($currentFile);
                        }
                        $mediaSql = "select value from " . $tablePrefix . "catalog_product_entity_varchar where entity_id='" . $productId . "' and attribute_id='85'";
                        $mediaCollection = $conn->fetchAll($mediaSql);
                        $mediaImagePath = $mediaCollection[0]['value'];
                        $baseImageUrl = Mage::getBaseUrl('media') . 'catalog/product' . $mediaImagePath;
                        $stringData = $sku . "###" . $baseImageUrl . "\n";
                        file_put_contents($myFile, $stringData, FILE_APPEND);
                    } else {
                        $shouldExclude = true;
                    }
                }
            } else {
                $shouldExclude = true;
            }
        } else {
            $shouldExclude = true;
        }

        if ($shouldExclude) {
            $status['total_image_files'] -= 1;
            $status['exclude_files'][] = $filename;
        } else {
            $status['total_image_uploaded'] += 1;
        }

        if ($status['total_image_uploaded'] == $status['total_image_files']) {
            $status['total_image_uploaded'] = 'complete';
        } else {
            $status['total_image_uploaded'] = 'processing';
        }

        saveStatus('status.log', $status);
        $lock->query("SELECT RELEASE_LOCK('{$keyLock}')");
    }
}elseif($status['state'] == 'complete'){
    // sent mail notify
    clearStatus();
} else {
    
}


function getImages()
{
    return glob('images_to_update/*.{jpg,png,gif}', GLOB_BRACE);
}

function saveStatus($filename = 'status.log', $default = array())
{
    $data = is_callable($default) ? $default() : $default;
    Mage::helper('feedexport/io')->write($filename, json_encode($data));
    return $data;
}

function readStatus($filename = 'status.log')
{
    $c = Mage::helper('feedexport/io')->read($filename);
    $data = json_decode($c, true);
    return $data;
}

function fetchStatus($filename = 'status.log', $default = array())
{
    $data = array();
    if (!file_exists($filename)) {
        $data = saveStatus($filename, $default);
    } else {
        $data = readStatus($filename);
    }
    return $data;
}

function clearStatus($filename = 'status.log')
{
    @unlink($filename);
}

function getFirstImage($images, $exclude = array())
{
    $i = false;
    foreach ($images as $img) {
        if (!in_array($img, $exclude)) {
            $i = $img;
            break;
        }
    }
    return $i;
}

function parseImage($filename)
{
    global $imageSortOrder;
    $sku = '';
    $label = '';
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $name = basename($filename, '.' . $ext);
    foreach ($imageSortOrder as $k => $v) {
        if (preg_match("/{$k}$/", $name) == 1) {
            $sku = str_replace($k, '', $name);
            $label = $k;
            break;
        }
    }
    if ($sku || $label) {
        return false;
    }
    return array($sku, $label, $ext);
}

function uploadFileToAmazonS3($imgFileName, $imgExistingPath)
{
    global $rootDir;
    $exe_image = '/usr/bin/s3cmd put --acl-public --add-header=\'Cache-Control:no-cache\' -c /var/www/ItsHot/bucket/.s3cfg ' . $rootDir . 'media/catalog/product/' . $imgExistingPath . '/' . $imgFileName . ' s3://itshot/media/catalog/product/' . $imgExistingPath . '/' . $imgFileName;
    shell_exec($exe_image);
    $exe_image2 = '/usr/bin/s3cmd put --acl-public --add-header=\'Cache-Control:no-cache\' -c /var/www/ItsHot/bucket/.s3cfg ' . $rootDir . 'media/catalog/product/' . $imgExistingPath . '/' . $imgFileName . ' s3://itshot/catalog/product/' . $imgExistingPath . '/' . $imgFileName;
    shell_exec($exe_image2);
}

function deleteFileFromAmazonS3($imgFileName, $imgExistingPath)
{
    $exe_image = '/usr/bin/s3cmd del -c /var/www/ItsHot/bucket/.s3cfg s3://itshot/media/catalog/product/' . $imgExistingPath . '/' . $imgFileName;
    shell_exec($exe_image);
    $exe_image2 = '/usr/bin/s3cmd del -c /var/www/ItsHot/bucket/.s3cfg s3://itshot/catalog/product/' . $imgExistingPath . '/' . $imgFileName;
    shell_exec($exe_image2);    
}

function selectProductUrlKey($productId)
{
    //Create connection object
    global $conn, $write, $tablePrefix;
    $productUrl = "";
    $sqlUrl = "SELECT request_path FROM " . $tablePrefix . "core_url_rewrite WHERE product_id=" . $productId . " AND id_path='product/" . $productId . "'";
    $sqlUrlRes = $conn->fetchRow($sqlUrl);
    if ($sqlUrlRes["request_path"] != "") {
        $productUrlStr = $baseUrl . $sqlUrlRes["request_path"];
        $productUrl = $productUrlStr;
    }
    return $productUrl;
}

function getExistingImagePath($productId, $newImgName)
{
    global $conn, $tablePrefix;
    $imgPath = "images";

    $gallerySQL = "SELECT value_id, value FROM " . $tablePrefix . "catalog_product_entity_media_gallery WHERE value='/images/{$newImgName}' AND entity_id=" . $productId;
    $gallerySQLRes = $conn->fetchRow($gallerySQL);

    if ($gallerySQLRes["value_id"] > 0) {
        $imgPath = "images";
    } else {
        $pathValue = "/" . $newImgName[0] . "/" . $newImgName[1] . "/" . $newImgName;
        $gallerySQL2 = "SELECT value_id, value FROM " . $tablePrefix . "catalog_product_entity_media_gallery WHERE value='{$pathValue}' AND entity_id=" . $productId;
        $gallerySQLRes2 = $conn->fetchRow($gallerySQL2);
        if ($gallerySQLRes2["value_id"] > 0) {
            $imgPath = $newImgName[0] . "/" . $newImgName[1];
        }
    }
    return $imgPath;
}

function resize($imgFileName, $imgExistingPath)
{
    $imgDirArray = array(31, 50, 56, 60, 75, 88, 100, 113, 125, 133, 135, 145, 150, 180, 200, 210, 300, 400, 500, 1000);
    foreach ($imgDirArray AS $size) {
        if ($size == 88) {
            $width = 88;
            $height = 77;
        } else {
            $width = $size;
            $height = $size;
        }
        $dir = $width . "x" . $height;
        resizeImageToS3($imgFileName, $dir, $width, $height, $imgExistingPath);
    }
}

function resizeImageToS3($imgFileName, $dir, $width, $height, $imgExistingPath)
{
    global $rootDir;

    $source_file = $rootDir . "media/catalog/product/" . $imgExistingPath . "/" . $imgFileName;
    $fileName = $rootDir . "media/catalog/product/" . $dir . "/" . $imgExistingPath . "/" . $imgFileName;

    if (!file_exists($rootDir . "media/catalog/product/" . $dir)) {
        mkdir($rootDir . "media/catalog/product/" . $dir, 0777);
    }
    if ($imgExistingPath == "images") {
        if (!file_exists($rootDir . "media/catalog/product/" . $dir . "/" . $imgExistingPath)) {
            mkdir($rootDir . "media/catalog/product/" . $dir . "/" . $imgExistingPath, 0777);
        }
    } else {
        $imgExistingPathArr = explode("/", $imgExistingPath);
        if (!file_exists($rootDir . "media/catalog/product/" . $dir . "/" . $imgExistingPathArr[0])) {
            mkdir($rootDir . "media/catalog/product/" . $dir . "/" . $imgExistingPathArr[0], 0777);
        }
        if (!file_exists($rootDir . "media/catalog/product/" . $dir . "/" . $imgExistingPath)) {
            mkdir($rootDir . "media/catalog/product/" . $dir . "/" . $imgExistingPath, 0777);
        }
    }
    copy($source_file, $fileName);
    if (file_exists($source_file)) {
        $image_info = getimagesize($source_file);

        $im = new Imagick();
        $im->readImage($source_file);

        $imageSrcWidth = $width;
        $imageSrcHeight = $height;


        if ($imageSrcWidth > 0 & $imageSrcHeight > 0) {
            if (($image_info[0] > $imageSrcWidth && $image_info[1] > $imageSrcHeight) || ($image_info[0] < $imageSrcWidth && $image_info[1] > $imageSrcHeight) || ($image_info[0] > $imageSrcWidth && $image_info[1] < $imageSrcHeight)) {
                //Scale image in propotions
                $scaleImage = scaleImage($image_info[0], $image_info[1], $imageSrcWidth, $imageSrcHeight);
                $im->thumbnailImage($scaleImage["w"], $scaleImage["h"]);
                $im->setCompression(Imagick::COMPRESSION_JPEG);
                $im->setCompressionQuality(100);
                $im->sharpenImage(0, 1, Imagick::CHANNEL_ALL);
            }
        } else if (($image_info[0] > $imageSrcWidth && $image_info[1] > $imageSrcHeight) ||
                ($image_info[0] < $imageSrcWidth && $image_info[1] > $imageSrcHeight) ||
                ($image_info[0] > $imageSrcWidth && $image_info[1] < $imageSrcHeight)) {
            $scaleImage = scaleImage($image_info[0], $image_info[1], $imageSrcWidth, $imageSrcHeight);
            $im->thumbnailImage($scaleImage["w"], $scaleImage["h"]);
            $im->setCompression(Imagick::COMPRESSION_JPEG);
            $im->setCompressionQuality(100);
            $im->sharpenImage(0, 1, Imagick::CHANNEL_ALL);
        } else if (($image_info[0] == $imageSrcWidth && $image_info[1] > $imageSrcWidth) ||
                ($image_info[0] > $imageSrcWidth && $image_info[1] == $imageSrcWidth)) {
            $scaleImage = scaleImage($image_info[0], $image_info[1], $imageSrcWidth, $imageSrcHeight);
            $im->thumbnailImage($scaleImage["w"], $scaleImage["h"]);
            $im->setCompression(Imagick::COMPRESSION_JPEG);
            $im->setCompressionQuality(100);
            $im->sharpenImage(0, 1, Imagick::CHANNEL_ALL);
        } else {
            
        }

        $Res = $im->writeImage($fileName);
        if ($Res) {
            $exe_image = '/usr/bin/s3cmd put --acl-public --add-header=\'Cache-Control:no-cache\' -c /var/www/ItsHot/bucket/.s3cfg ' . $fileName . ' s3://itshot/catalog/product/' . $dir . '/' . $imgExistingPath . '/' . $imgFileName;
            shell_exec($exe_image);
        }
    }
}

function disbleImages($productId, $sku)
{
    global $conn, $write, $tablePrefix;
    $sql2 = "select value_id,value from " . $tablePrefix . "catalog_product_entity_media_gallery where entity_id =" . $productId;
    $mediaSql = $conn->fetchAll($sql2);

    $finalImagePath = '';
    $ismain = 0;
    foreach ($mediaSql as $rowIm) {
        $imageName = $rowIm['value'];
        $valueId = $rowIm['value_id'];
        $imageRoot = 'https://media.itshot.com/catalog/product';
        $finalImagePath = $imageRoot . $imageName;
        list($Imgwidth, $Imgheight) = getimagesize($finalImagePath);
        if (($Imgwidth <= 999 || $Imgheight <= 999) && ($Imgwidth >= 800 || $Imgheight >= 800)) {
            $chk_image_query = "SELECT entity_id,mg.value_id,mgv.label FROM " . $tablePrefix . "catalog_product_entity_media_gallery mg, " . $tablePrefix . "catalog_product_entity_media_gallery_value mgv where mg.entity_id=$productId and mg.value_id=mgv.value_id and mg.value_id='" . $valueId . "' and disabled=0";
            $chk_image_query_res = $conn->fetchRow($chk_image_query);

            $selectMainValueMain = "select value_id from " . $tablePrefix . "catalog_product_entity_varchar where attribute_id = 85 and entity_id='" . $productId . "' and value='" . $imageName . "' ";
            $selectMainValueMainRes = $conn->fetchRow($selectMainValueMain);
            $mainImageValueId = $selectMainValueMainRes['value_id'];
            if ($mainImageValueId != '') {
                $ismain = 1;
            }

            if ($chk_image_query_res['value_id'] > 0 && $ismain != 1) {
                $label = $chk_image_query_res['label'];
                $disable_image_query = "UPDATE " . $tablePrefix . "catalog_product_entity_media_gallery_value mgv set mgv.disabled=1 where mgv.value_id=" . $chk_image_query_res['value_id'];
                $write->query($disable_image_query);
                $disableSku = $sku . "=>" . $label;
            }
        }
    }
    return $disableSku;
}

function scaleImage($image_width, $image_height, $max_width, $max_height)
{
    $scaleImg = array();
    $old_width = $image_width;
    $old_height = $image_height;
    $scale = min($max_width / $old_width, $max_height / $old_height);
    $new_width = ceil($scale * $old_width);
    $new_height = ceil($scale * $old_height);
    $scaleImg = array("w" => $new_width, "h" => $new_height);
    return $scaleImg;
}

function copyInAnotherFolder($imageName)
{
    global $rootDir, $directory, $backupDirectory;
    if (file_exists($rootDir . "adempierstock/imagescript/" . $backupDirectory . $imageName)) {
        $exImage = explode(".", $imageName);
        $sku = $exImage[0];
        $exImage = $exImage[1];
        rename($rootDir . "adempierstock/imagescript/" . $backupDirectory . $imageName, $rootDir . "adempierstock/imagescript/" . $backupDirectory . $sku . "-" . date('d-m-Y') . "." . $exImage);
        $result++;

        if (copy($rootDir . "adempierstock/imagescript/" . $directory . $imageName, $rootDir . "adempierstock/imagescript/" . $backupDirectory . $imageName)) {
            unlink($rootDir . "adempierstock/imagescript/" . $directory . $imageName);
        }
    } else {
        if (copy($rootDir . "adempierstock/imagescript/" . $directory . $imageName, $rootDir . "adempierstock/imagescript/" . $backupDirectory . $imageName)) {
            unlink($rootDir . "adempierstock/imagescript/" . $directory . $imageName);
        }
    }
}

function getoldimage($label, $pid)
{
    global $conn, $tablePrefix;
    if ($label == "main") {
        $query = "SELECT value FROM " . $tablePrefix . "catalog_product_entity_varchar pev WHERE entity_id='" . $pid . "' AND attribute_id=85";
    } else {
        $query = "SELECT mgallery.value FROM `" . $tablePrefix . "catalog_product_entity_media_gallery` as mgallery, " . $tablePrefix . "catalog_product_entity_media_gallery_value as mgalleryvalue where entity_id =  '" . $pid . "' and mgallery.value_id = mgalleryvalue.value_id  and mgalleryvalue.disabled = 0 and store_id = 0 and label='" . $label . "'";
    }
    $imagelist = $conn->fetchAll($query);
    if (isset($imagelist[0]['value']) && $imagelist[0]['value'] != "") {
        return $imagelist[0]['value'];
    } else {
        return "no_image";
    }
}

function updateimagelabel_position($pid, $label, $position, $image, $sku, $ismain = 0, $filename)
{
    global $conn, $write, $tablePrefix;
    $gallerySQL = "SELECT value_id, value FROM " . $tablePrefix . "catalog_product_entity_media_gallery WHERE value ='" . $image . "' and  entity_id=" . $pid;
    $gallerySQLRes = $conn->fetchRow($gallerySQL);

    if ($gallerySQLRes["value_id"] > 0) {
        $valueId = $gallerySQLRes["value_id"];
        $selectValue = "select count(*) as count from " . $tablePrefix . "catalog_product_entity_media_gallery_value where value_id = '" . $valueId . "'";
        $selectValueRes = $conn->fetchRow($selectValue);
        $recordCount = $selectValueRes['count'];

        if ($recordCount > 0) {
            $updateSQLLabel = "UPDATE " . $tablePrefix . "catalog_product_entity_media_gallery_value SET label = '" . $label . "' , position = '" . $position . "' WHERE value_id = '" . $valueId . "'";
        } else {
            $updateSQLLabel = "INSERT INTO " . $tablePrefix . "catalog_product_entity_media_gallery_value(value_id,label,position) VALUES('" . $valueId . "','" . $label . "','" . $position . "' )";
        }
        $write->query($updateSQLLabel);
    } else {
        $insertSQL = "INSERT INTO " . $tablePrefix . "catalog_product_entity_media_gallery(attribute_id,entity_id,value) values(88,'" . $pid . "','" . $image . "')";
        $write->query($insertSQL);
        $lastInsertId = $write->lastInsertId();
        $insertSQLLabel = "INSERT INTO " . $tablePrefix . "catalog_product_entity_media_gallery_value(value_id,label,position) values('" . $lastInsertId . "','" . $label . "','" . $position . "' )";
        $write->query($insertSQLLabel);
    }


    $disableimage = 0;
    if (($label == "wh" || $label == "ye" || $label == "ro" || $label == "bl" || $label == "tt" ) && $ismain == 1) {
        $label = 'main';
        $disableimage = 1;
    } else if ($label == "backwh" || $label == "backye" || $label == "backro" || $label == "backbl" || $label == "backtt") {
        $label = 'back';
        $disableimage = 1;
    } else if ($label == "bodwh" || $label == "bodye" || $label == "bodro" || $label == "bodbl" || $label == "bodtt") {
        $label = 'bod';
        $disableimage = 1;
    } else if ($label == "boxwh" || $label == "boxye" || $label == "boxro" || $label == "boxbl" || $label == "boxtt") {
        $label = 'box';
        $disableimage = 1;
    }
    if ($disableimage) {
        $chk_image_query = "SELECT entity_id,mg.value_id FROM " . $tablePrefix . "catalog_product_entity_media_gallery mg, " . $tablePrefix . "catalog_product_entity_media_gallery_value mgv where mg.entity_id=$pid and mg.value_id=mgv.value_id and mgv.label='" . $label . "' and disabled=0";
        $chk_image_query_res = $conn->fetchRow($chk_image_query);
        if ($chk_image_query_res['value_id'] > 0) {
            $disable_image_query = "UPDATE " . $tablePrefix . "catalog_product_entity_media_gallery_value mgv set mgv.disabled=1 where mgv.value_id=" . $chk_image_query_res['value_id'];
            $write->query($disable_image_query);
        }
    }

    if ($label == 'main') {
        $selectMainValue74 = "select count(*) as count from " . $tablePrefix . "catalog_product_entity_varchar where attribute_id = 85 and entity_id='" . $pid . "' ";
        $selectMainValueRes74 = $conn->fetchRow($selectMainValue74);
        $recordMainCount74 = $selectMainValueRes74['count'];
        if ($recordMainCount74 > 0) {
            $updateMainImageSql74 = "update " . $tablePrefix . "catalog_product_entity_varchar set value = '" . $image . "'  where entity_id='" . $pid . "' and attribute_id = 85 ";
            $write->query($updateMainImageSql74);
        } else {
            $insertMainSql74 = "insert into " . $tablePrefix . "catalog_product_entity_varchar(entity_type_id, attribute_id, entity_id, value) values ('4', '85','" . $pid . "','" . $image . "' ) ";
            $write->query($insertMainSql74);
        }

        $selectMainValue75 = "select count(*) as count from " . $tablePrefix . "catalog_product_entity_varchar where attribute_id = 86 and entity_id='" . $pid . "' ";
        $selectMainValueRes75 = $conn->fetchRow($selectMainValue75);
        $recordMainCount75 = $selectMainValueRes75['count'];
        if ($recordMainCount75 > 0) {
            $updateMainImageSql75 = "update " . $tablePrefix . "catalog_product_entity_varchar set value = '" . $image . "'  where entity_id='" . $pid . "' and attribute_id = 86 ";
            $write->query($updateMainImageSql75);
        } else {
            $insertMainSql75 = "insert into " . $tablePrefix . "catalog_product_entity_varchar(entity_type_id,attribute_id, entity_id, value) values ('4', '86','" . $pid . "','" . $image . "' ) ";
            $write->query($insertMainSql75);
        }

        $selectMainValue76 = "select count(*) as count from " . $tablePrefix . "catalog_product_entity_varchar where attribute_id = 87 and entity_id='" . $pid . "' ";
        $selectMainValueRes76 = $conn->fetchRow($selectMainValue76);
        $recordMainCount76 = $selectMainValueRes76['count'];
        if ($recordMainCount76 > 0) {
            $updateMainImageSql76 = "update " . $tablePrefix . "catalog_product_entity_varchar set value = '" . $image . "'  where entity_id='" . $pid . "' and attribute_id = 87 ";
            $write->query($updateMainImageSql76);
        } else {
            $insertMainSql76 = "insert into " . $tablePrefix . "catalog_product_entity_varchar(entity_type_id,  attribute_id, entity_id, value) values ('4', '87','" . $pid . "','" . $image . "' ) ";
            $write->query($insertMainSql76);
        }
    }
}
