<?php

/**
 * Connector data helper
 */

namespace Simi\SimimageworxGraphQl\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;
use Mirasvit\SearchAutocomplete\Model\ConfigProvider as MirasvitSearchConfig;
use Magento\Store\Model\StoreManagerInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    const XML_PATH_COMMON_TWITTER_USERNAME = 'mageworx_seo/markup/common/tw_username';

    public $serializer;
    public $collectionPromotion;
    public $dateTime;
    public $catalogCollectionFactory;
    public $storeManager;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\ObjectManagerInterface $simiObjectManager,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $catalogCollectionFactory
    ) {
        $this->serializer = $serializer;
        $this->dateTime = $dateTime;
        $this->catalogCollectionFactory = $catalogCollectionFactory->create();
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }


    public function getStoreConfig($path)
    {
        return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }


    public function getCommonTwUsername($storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_COMMON_TWITTER_USERNAME,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
