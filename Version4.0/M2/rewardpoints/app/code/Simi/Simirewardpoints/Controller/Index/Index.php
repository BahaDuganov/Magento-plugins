<?php

namespace Simi\Simirewardpoints\Controller\Index;

class Index extends \Simi\Simirewardpoints\Controller\AbstractAction
{

    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        return $this->resultPageFactory->create();
    }
}
