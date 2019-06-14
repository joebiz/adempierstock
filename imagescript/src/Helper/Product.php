<?php

namespace Adempier\ImageScript\Helper;

class Product
{

    const ATTR_ID_URL_KEY = 97;
    const ATTR_ID_IMAGE = 85;

    public function getTableName($table)
    {
        return $this->getResource()->getTableName($table);
    }

    public function getProductTable()
    {
        return $this->getTableName('catalog_product_entity');
    }

    public function getProductMediaGalleryTable()
    {
        return $this->getTableName('catalog_product_entity_media_gallery');
    }

    public function getProductMediaGalleryValueTable()
    {
        return $this->getTableName('catalog_product_entity_media_gallery_value');
    }

    public function getUrlRewriteTable()
    {
        return $this->getTableName('core_url_rewrite');
    }

    public function getProductId($sku)
    {
        /* @var $select Varien_Db_Select */
        $select = $this->getConnection()->select();
        $select->from($this->getProductTable(), array('entity_id'));
        $select->where('sku = ?', $sku);
        return $this->getConnection()->fetchOne($select);
    }

    public function getProductGallery($productId)
    {
        $select = $this->getConnection()->select();
        $select->from(array('mgallery' => $this->getProductMediaGalleryTable()));
        $select->join(array('mgalleryvalue' => $this->getProductMediaGalleryValueTable()), 'mgallery.value_id = mgalleryvalue.value_id  AND mgalleryvalue.disabled = 0 AND mgalleryvalue.store_id = 0');

        $select->where('mgallery.entity_id = ?', $productId);
        return $this->getConnection()->fetchAll($select);
    }

    public function getProductUrlKey($productId)
    {
        $select = $this->getConnection()->select();
        $select->from($this->getUrlRewriteTable(), array('request_path'));
        $select->where('product_id = ?', $productId);
        $select->where('id_path = ?', 'product/' . $productId);
        $urlKey = $this->getConnection()->fetchOne($select);
        if ($urlKey) {
            return $urlKey;
        }
        $select = $this->getConnection()->select();
        $select->from($this->getTableName('catalog_product_entity_varchar'), array('value'));
        $select->where('attribute_id= ?', self::ATTR_ID_URL_KEY);
        $select->where('entity_id= ?', $productId);
        return $this->getConnection()->fetchOne($select);
    }

    public function getProductImage($productId, $label = 'main')
    {
        $image = $this->getProductImageByLabel($productId, $label);
        return $image ? $image : 'no_image';
    }

    public function getProductImageByLabel($productId, $label = 'main')
    {
        return $label == 'main' ? $this->getProductImageMain($productId) : $this->getProductImageFromGalleryByLabel($productId, $label);
    }

    public function getProductImageFromGalleryByLabel($productId, $label = 'main')
    {
        $select = $this->getConnection()->select();
        $select->from(array('mgallery' => $this->getProductMediaGalleryTable()), array('value'));
        $select->join(array('mgalleryvalue' => $this->getProductMediaGalleryValueTable()), 'mgallery.value_id = mgalleryvalue.value_id  and mgalleryvalue.disabled = 0 AND mgalleryvalue.store_id = 0', array());
        $select->where('mgalleryvalue.label = ?', $label);
        $select->where('mgallery.entity_id = ?', $productId);
        return $this->getConnection()->fetchOne($select);
    }

    public function getProductImageMain($productId)
    {
        $select = $this->getConnection()->select();
        $select->from($this->getTableName('catalog_product_entity_varchar'));
        $select->where('entity_id = ?', $productId);
        $select->where('attribute_id = ?', self::ATTR_ID_IMAGE);
        return $this->getConnection()->fetchOne($select);
    }

    public function getMediaImageUrl($imageName)
    {
        return 'https://media.itshot.com/catalog/product' . $imageName;
    }

    public function fetchRow($select)
    {
        return $this->getConnection()->fetchRow($select);
    }

    public function disbleImages($productId, $sku)
    {
        global $conn, $write, $tablePrefix;
        $result = $this->getConnection()->fetchAll(
                $this->getConnection()->select()
                        ->from($this->getProductMediaGalleryTable(), array('value_id', 'value'))
                        ->where('entity_id = ?', $productId)
        );

        $finalImagePath = '';
        $ismain = 0;
        foreach ($result as $rowIm) {
            $imageName = $rowIm['value'];
            $valueId = $rowIm['value_id'];
            list($Imgwidth, $Imgheight) = getimagesize($this->getMediaImageUrl($imageName));
            if ($Imgwidth <= 999 || $Imgheight <= 999) {
                $chk_image_query_res = $this->fetchRow(
                        $this->getConnection()->select()
                                ->from(array('mg' => $this->getProductMediaGalleryTable()), array('entity_id', 'value_id'))
                                ->join(array('mgv' => $this->getProductMediaGalleryValueTable()), 'mg.value_id=mgv.value_id AND mgv.disabled = 0', array('label'))
                                ->where('value_id = ?', $valueId)
                                ->where('entity_id = ?', $productId)
                );

                $mainImageValueId = $this->getConnection->fetchOne(
                        $this->getConnection()->select()
                                ->from($this->getTableName('catalog_product_entity_varchar'), array('value_id'))
                                ->where('attribute_id = ?', self::ATTR_ID_IMAGE)
                                ->where('entity_id = ?', $productId)
                                ->where('value = ? ', $imageName)
                );
                if ($mainImageValueId != '') {
                    $ismain = 1;
                }

                if ($chk_image_query_res['value_id'] > 0 && $ismain != 1) {
                    $label = $chk_image_query_res['label'];
                    $disable_image_query = "UPDATE " . $tablePrefix . "catalog_product_entity_media_gallery_value mgv set mgv.disabled=1 where mgv.value_id=" . $chk_image_query_res['value_id'];
                    $this->getWriteConnection()->query($disable_image_query);
                }
            }
        }
    }

    public function getResource()
    {
        return Mage::getSingleton('core/resource');
    }

    public function getConnection()
    {
        return $this->getResource()->getConnection('core_read');
    }

    public function getWriteConnection()
    {
        return $this->getResource()->getConnection('core_write');
    }

}
