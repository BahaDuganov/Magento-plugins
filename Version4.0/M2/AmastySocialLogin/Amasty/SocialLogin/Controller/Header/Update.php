<?php

declare(strict_types=1);

namespace Amasty\SocialLogin\Controller\Header;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Controller\Result\JsonFactory;

class Update extends \Magento\Framework\App\Action\Action
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    private $pageFactory;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        \Magento\Framework\View\Result\PageFactory $pageFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->pageFactory = $pageFactory;
    }

    /**
     * @return ResponseInterface|Json|Redirect|ResultInterface
     */
    public function execute()
    {
        if ($this->getRequest()->isAjax()) {
            $resultPage = $this->resultJsonFactory->create();
            $resultPage->setHttpResponseCode(200);
            $resultPage->setData([
                'content'  => $this->getHeaderLinksContent()
            ]);
            return $resultPage;
        } else {
            /** @var Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('/');
            return $resultRedirect;
        }
    }

    private function getHeaderLinksContent(): string
    {
        $page = $this->pageFactory->create(false, ['isIsolated' => true]);
        $page->addHandle('cms_index_index');
        $html = '';
        $header = $page->getLayout()->getBlock('header.links');
        if ($header) {
            $html = $header->toHtml();
        }

        return $html;
    }
}
