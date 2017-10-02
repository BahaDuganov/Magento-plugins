<?php

namespace Simi\Simistorelocator\Controller\Adminhtml\Store;

use Magento\Framework\Controller\ResultFactory;

class Index extends \Simi\Simistorelocator\Controller\Adminhtml\Store {

    /**
     * Index action.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute() {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $this->initPage($resultPage);
        $resultPage->getConfig()->getTitle()->prepend(__('Manage Store'));

        return $resultPage;
    }

}
