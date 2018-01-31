<?php
/**
 * Created by PhpStorm.
 * User: scott
 * Date: 1/29/18
 * Time: 9:58 PM
 */

namespace Simi\Simipwa\Model;

use Magento\CatalogUrlRewrite\Model\CategoryUrlRewriteGenerator;
use Magento\Sitemap\Model\ResourceModel\Catalog\Category;
use Magento\Framework\DataObject;

class Catmap extends Category
{
    public function getCollection($storeId)
    {
        $categories = [];

        /* @var $store \Magento\Store\Model\Store */
        $store = $this->_storeManager->getStore($storeId);

        if (!$store) {
            return false;
        }

        $connection = $this->getConnection();

        $this->_select = $connection->select()->from(
            $this->getMainTable()
        )->where(
            $this->getIdFieldName() . '=?',
            $store->getRootCategoryId()
        );
        $categoryRow = $connection->fetchRow($this->_select);

        if (!$categoryRow) {
            return false;
        }

        $this->_select = $connection->select()->from(
            ['e' => $this->getMainTable()],
            [$this->getIdFieldName(), 'updated_at', 'children_count']
        )
            // ->join(
            // 	['cv'=>'catalog_category_entity_varchar'],
            // 	'e.entity_id = cv.entity_id',
            // 	'value as category_name'
            // )->join(
            // 	['att'=>'eav_attribute'],
            // 	'att.attribute_id = cv.attribute_id',
            // 	''
            // )->join(
            // 	['aty'=>'eav_entity_type'],
            // 	'aty.entity_type_id = att.entity_type_id',
            // 	''
            // )
            ->joinLeft(
                ['url_rewrite' => $this->getTable('url_rewrite')],
                'e.entity_id = url_rewrite.entity_id AND url_rewrite.is_autogenerated = 1'
                . $connection->quoteInto(' AND url_rewrite.store_id = ?', $store->getId())
                . $connection->quoteInto(' AND url_rewrite.entity_type = ?', CategoryUrlRewriteGenerator::ENTITY_TYPE),
                ['url' => 'request_path']
            )
            // ->where("aty.`entity_model` = 'catalog/category' and att.`attribute_code` = 'name'")
            ->where(
                'e.path LIKE ?',
                $categoryRow['path'] . '/%'
            );

        $this->_addFilter($storeId, 'is_active', 1);

        $query = $connection->query($this->_select);
        while ($row = $query->fetch()) {
            $category = $this->_prepareCategory($row);
            $categories[$category->getId()] = $category;
        }

        return $categories;
    }

    protected function _prepareCategory(array $categoryRow)
    {
        $category = new DataObject();
        $category->setId($categoryRow[$this->getIdFieldName()]);
        $categoryUrl = !empty($categoryRow['url']) ? $categoryRow['url'] : 'catalog/category/view/id/' .
            $category->getId();
        $category->setUrl($categoryUrl);
        $category->setUpdatedAt($categoryRow['updated_at']);
        $category->setChild($categoryRow['children_count']);
        // $category->setCategoryName($categoryRow['category_name']);
        return $category;
    }

}