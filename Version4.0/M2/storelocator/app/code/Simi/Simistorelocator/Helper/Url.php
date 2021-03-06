<?php

namespace Simi\Simistorelocator\Helper;

class Url extends \Magento\Framework\App\Helper\AbstractHelper {

    /**
     * @var \Magento\Backend\Block\Widget\Grid\Column\Renderer\Options\Converter
     */
    public $converter;

    /**
     * @var \Simi\Simistorelocator\Model\Factory
     */
    public $factory;

    /**
     * @var \Simi\Simistorelocator\Model\StoreFactory
     */
    public $storeFactory;

    /**
     * @var \Magento\Backend\Model\Session
     */
    public $backendSession;

    /**
     * @var array
     */
    public $sessionData = null;

    /**
     * @var \Magento\Backend\Helper\Js
     */
    public $backendHelperJs;

    /**
     * @var \Simi\Simistorelocator\Model\ResourceModel\Store\CollectionFactory
     *
     */
    public $storeCollectionFactory;

    /**
     * @var \Magento\Framework\Filesystem
     */
    public $filesystem;

    /**
     * Store manager
     *
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var \Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollectionFactory
     */
    public $urlRewriteCollectionFactory;

    /**
     * Block constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
    \Magento\Framework\App\Helper\Context $context, \Simi\Simistorelocator\Model\Factory $factory, \Magento\Backend\Block\Widget\Grid\Column\Renderer\Options\Converter $converter, \Magento\Backend\Helper\Js $backendHelperJs, \Magento\Framework\Filesystem $filesystem, \Magento\Backend\Model\Session $backendSession, \Magento\Store\Model\StoreManagerInterface $storeManager, \Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollectionFactory $urlRewriteCollectionFactory, \Simi\Simistorelocator\Model\ResourceModel\Store\CollectionFactory $storeCollectionFactory, \Simi\Simistorelocator\Model\StoreFactory $storeFactory
    ) {
        parent::__construct($context);
        $this->factory = $factory;
        $this->converter = $converter;
        $this->backendHelperJs = $backendHelperJs;
        $this->filesystem = $filesystem;
        $this->backendSession = $backendSession;
        $this->storeManager = $storeManager;
        $this->urlRewriteCollectionFactory = $urlRewriteCollectionFactory;
        $this->storeCollectionFactory = $storeCollectionFactory;
        $this->storeFactory = $storeFactory;
    }

    public function getResponseBody($url) {
        if (ini_get('allow_url_fopen') != 1) {
            @ini_set('allow_url_fopen', '1');
        }

        if (ini_get('allow_url_fopen') != 1) {
            $ch = curl_init();
            if (preg_match('/^https:/i', $url)) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            }
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_FAILONERROR, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            $contents = curl_exec($ch);
            curl_close($ch);
        } else {
            $contents = file_get_contents($url);
        }

        return $contents;
    }

    public function getStoreViewUrl($storeName, $id) {
        $allStores = $this->storeManager->getStores();
        $storelocator = $this->storeFactory->create()->load($id);
        $url_suffix = $this->scopeConfig->getValue(
                'catalog/seo/product_url_suffix', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeManager->getStore()->getStoreId()
        );

        foreach ($allStores as $_eachStoreId => $val) {

            $request_path = 'simistorelocator/' . $storeName . '.' . $url_suffix;


            $rewrite = $this->urlRewriteCollectionFactory->create()
                    ->addFieldToFilter('id_path', $storeName)
                    ->addFieldToFilter('store_id', $_eachStoreId)
                    ->getFirstItem();

            $request_path1 = $rewrite->getRequestPath();

            if ($storelocator->getUrlIdPath() != $storeName) {
                $storeName = $storelocator->getUrlIdPath();
                $storelocator->save();
                $request_path = 'simistorelocator/' . $storeName . $url_suffix;
            }
        }
        return Mage::getUrl($request_path1, array("_secure" => true));
    }

    public function characterSpecial($character) {
        if ('"libiconv"' == ICONV_IMPL) {
            $character = iconv('UTF-8', 'ascii//ignore//translit', $character);
        }
        $input = array("??", " ", "??", "??", "???", "???", "??", "??", "???", "???", "???", "???", "???", "??", "???", "???"
            , "???", "???", "???", "??", "??", "???", "???", "???", "??", "???", "???", "???", "???", "???", "??", "??", "???", "???", "??",
            "??", "??", "???", "???", "??", "??", "???", "???", "???", "???", "???", "??"
            , "???", "???", "???", "???", "???",
            "??", "??", "???", "???", "??", "??", "???", "???", "???", "???", "???",
            "???", "??", "???", "???", "???",
            "??",
            "??", "??", "???", "???", "??", "??", "???", "???", "???", "???", "???", "??"
            , "???", "???", "???", "???", "???",
            "??", "??", "???", "???", "???", "??", "???", "???", "???", "???", "???",
            "??", "??", "???", "???", "??",
            "??", "??", "???", "???", "??", "??", "???", "???", "???", "???", "???", "??"
            , "???", "???", "???", "???", "???",
            "??", "??", "???", "???", "??", "??", "???", "???", "???", "???", "???",
            "???", "??", "???", "???", "???",
            "??", "??", "??", "??", '.', '-', "'", "[??-??]", "??", "??", "[??-??]", "/[??-??]/", "/??/", "/??/", "/[??-????]/", "/??/", "/[??-??]/", "/[??-??]/", "/[??-??]/", "/??/", "/??/", "/[??-??]/", "/[??-??]/", "/??/", "/??/", "/[??-????]/", "/??/", "/[??-??]/", "/[??-??]/", "?");
        $output = array("n", "-", "a", "a", "a", "a", "a", "a", "a", "a", "a", "a", "a"
            , "a", "a", "a", "a", "a", "a",
            "e", "e", "e", "e", "e", "e", "e", "e", "e", "e", "e",
            "i", "i", "i", "i", "i",
            "o", "o", "o", "o", "o", "o", "o", "o", "o", "o", "o", "o"
            , "o", "o", "o", "o", "o",
            "u", "u", "u", "u", "u", "u", "u", "u", "u", "u", "u",
            "y", "y", "y", "y", "y",
            "d",
            "A", "A", "A", "A", "A", "A", "A", "A", "A", "A", "A", "A"
            , "A", "A", "A", "A", "A",
            "E", "E", "E", "E", "E", "E", "E", "E", "E", "E", "E",
            "I", "I", "I", "I", "I",
            "O", "O", "O", "O", "O", "O", "O", "O", "O", "O", "O", "O"
            , "O", "O", "O", "O", "O",
            "U", "U", "U", "U", "U", "U", "U", "U", "U", "U", "U",
            "Y", "Y", "Y", "Y", "Y",
            "D", "e", "u", "a", '-', '-', "", "A", "AE", "C", "E", "I", "D", "N", "O", "X", "U", "Y", "a", "ae", "c", "e", "i", "d", "n", "o", "x", "u", "y", "");

        return str_replace($input, $output, $character);
    }

}
