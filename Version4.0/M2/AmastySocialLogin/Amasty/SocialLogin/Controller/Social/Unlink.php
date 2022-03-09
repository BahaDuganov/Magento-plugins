<?php

declare(strict_types=1);

namespace Amasty\SocialLogin\Controller\Social;

use Amasty\SocialLogin\Model\Unlink as UnlinkModel;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;

class Unlink extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var UnlinkModel
     */
    private $unlink;

    public function __construct(
        Context $context,
        Session $customerSession,
        UnlinkModel $unlink
    ) {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->unlink = $unlink;
    }

    public function execute()
    {
        $customerId = (int) $this->customerSession->getCustomerId();
        $type = $this->getRequest()->getParam('type');
        $result = $this->unlink->execute($type, $customerId);

        if ($result['isSuccess']) {
            $this->messageManager->addSuccessMessage($result['message']);
        } else {
            $this->messageManager->addErrorMessage($result['message']);
        }

        $this->_redirect('amsociallogin/social/accounts');
    }

    /**
     * Retrieve customer session object
     *
     * @return Session
     */
    protected function _getSession()
    {
        return $this->customerSession;
    }

    /**
     * Check customer authentication
     *
     * @param RequestInterface $request
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function dispatch(RequestInterface $request)
    {
        if (!$this->_getSession()->authenticate()) {
            $this->_actionFlag->set('', 'no-dispatch', true);
        }
        return parent::dispatch($request);
    }
}
