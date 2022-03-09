<?php

namespace Amasty\SocialLogin\Controller\Logout;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PhpCookieManager;
use Magento\Framework\Controller\Result\JsonFactory;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $session;

    /**
     * @var PhpCookieManager
     */
    private $cookieMetadataManager;

    /**
     * @var CookieMetadataFactory
     */
    private $cookieMetadataFactory;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    public function __construct(
        Context $context,
        \Magento\Customer\Model\Session $session,
        CookieMetadataFactory $cookieMetadataFactory,
        PhpCookieManager $cookieMetadataManager,
        JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->session = $session;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->cookieMetadataManager = $cookieMetadataManager;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * @return ResponseInterface|Json|Redirect|ResultInterface
     */
    public function execute()
    {
        $lastCustomerId = $this->session->getId();
        $this->session->logout()->setBeforeAuthUrl($this->_redirect->getRefererUrl())
            ->setLastCustomerId($lastCustomerId);
        if ($this->cookieMetadataManager->getCookie('mage-cache-sessid')) {
            $metadata = $this->cookieMetadataFactory->createCookieMetadata();
            $metadata->setPath('/');
            $this->cookieMetadataManager->deleteCookie('mage-cache-sessid', $metadata);
        }

        if ($this->getRequest()->isAjax()) {
            $resultPage = $this->resultJsonFactory->create();
            $resultPage->setHttpResponseCode(200);

            $resultPage->setData([
                'message' =>  __('You are now logged out.')
            ]);

            return $resultPage;
        } else {
            /** @var Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('*/*/logoutSuccess');
            return $resultRedirect;
        }
    }
}
