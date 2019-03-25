<?php

include 'functions.php';

$respones = array(
    'errno' => 0,
    'msg' => 'Success'
);
$filename = isset($_GET['filename']) ? $_GET['filename'] : '';
if ($filename) {
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
            if ($urlKey == '') {
                $urlSQL = "SELECT value FROM " . $tablePrefix . "catalog_product_entity_varchar WHERE attribute_id=97 AND entity_id=" . $productId;
                $urlSQLRes = $conn->fetchRow($urlSQL);
                $urlKey = $urlSQLRes["value"];
            }

            $ismain = 0;
            if (strstr($appendStr, "main")) {
                $appendStr = str_replace("main", "", $appendStr);
                $ismain = 1;
            }

            if ($appendStr == '') {
                $appendStr = 'main';
            }

            $oldImgName = $urlKey . "_" . $appendStr . "." . $fileExtension;
            $newImgName = $urlKey . "_" . $appendStr . "." . $fileExtension;
            $label = $appendStr;
            $position = array_search($appendStr, $labels) + 1;
            $urlKeyWithType = $urlKey . $appendStr;
            $ademiperUrlKey = $urlKey . "_" . $position;

            $p_imgarray = array('main' => 0, 'back' => 0, 'bod' => 0, 'box' => 0);
            $issuewithimage = 0;
            $oldimage = getoldimage($appendStr, $productId);
            $imgname = basename($oldimage);
            $imgpath = str_replace($imgname, '', $oldimage);
            $imgExistingPath = getExistingImagePath($productId, $imgname);

            if (file_exists($rootDir . "media/catalog/product" . $oldimage)) {
                unlink($rootDir . "media/catalog/product" . $oldimage);
                if (copy($rootDir . "adempierstock/imagescript/images_to_update/" . $filename, $rootDir . "media/catalog/product" . $oldimage)) {
                    updateimagelabel_position($productId, $label, $position, $oldimage, $sku, $ismain, $filename);
                    resize($imgname, $imgExistingPath);
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
                }
            } else {
                if (copy($rootDir . "adempierstock/imagescript/images_to_update/" . $filename, $rootDir . "media/catalog/product/{$imgExistingPath}/" . $newImgName)) {
                    updateimagelabel_position($productId, $label, $position, "/{$imgExistingPath}/" . $newImgName, $sku, $ismain, $filename);
                    resize($newImgName, $imgExistingPath);
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
                }
            }
        }
    }
}

header('Content-Type: application/json');
echo json_encode($respones);

