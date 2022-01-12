<?php

/**
 * Connector data helper
 */

namespace Simi\SimimageworxGraphQl\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;
use Mirasvit\SearchAutocomplete\Model\ConfigProvider as MirasvitSearchConfig;

class Data extends \Magento\Framework\App\Helper\AbstractHelper {
	private $serializer;
	protected $collectionPromotion;
	protected $dateTime;
	protected $catalogCollectionFactory;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\ObjectManagerInterface $simiObjectManager,
	    \Magento\Framework\Serialize\SerializerInterface $serializer,
		\Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
		\Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $catalogCollectionFactory
    ){
		$this->serializer = $serializer;
		$this->dateTime = $dateTime;
		$this->catalogCollectionFactory = $catalogCollectionFactory->create();
		parent::__construct($context);
	}


    public function getStoreConfig($path)
    {
        return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
}
