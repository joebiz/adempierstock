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

    public function selectProductUrlKey($productId)
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
        $select->where('attribute_id=' . self::ATTR_ID_URL_KEY . ' AND entity_id= ?', $productId);
        return $this->getConnection()->fetchOne($select);
    }

    public function getResource()
    {
        return Mage::getSingleton('core/resource');
    }

    public function getConnection()
    {
        return $this->getResource()->getConnection('core_read');
    }

}
