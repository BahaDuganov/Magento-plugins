<?php

declare(strict_types=1);

namespace Amasty\SocialLogin\Controller\Social;

use Amasty\SocialLogin\Model\Login as LoginModel;
use Magento\Customer\Model\Account\Redirect as AccountRedirect;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

class Login extends \Magento\Customer\Controller\AbstractAccount
{
    /**
     * @var LoginModel
     */
    private $login;

    /**
     * @var RawFactory
     */
    private $rawFactory;

    /**
     * @var AccountRedirect
     */
    private $accountRedirect;

    public function __construct(
        AccountRedirect $accountRedirect,
        Context $context,
        RawFactory $rawFactory,
        LoginModel $login
    ) {
        parent::__construct($context);
        $this->login = $login;
        $this->rawFactory = $rawFactory;
        $this->accountRedirect = $accountRedirect;
    }

    /**
     * @return ResponseInterface|\Magento\Framework\Controller\Result\Forward|Raw|Redirect|ResultInterface
     */
    public function execute()
    {
        $result = $this->login->execute($this->getRequest()->getParams());

        return $this->prepareResult($result);
    }

    /**
     * @param array $resultData
     * @return \Magento\Framework\Controller\Result\Forward|Raw|Redirect
     */
    private function prepareResult(array $resultData)
    {
        if ($resultData['isAjax']) {
            $result = $this->getRaw($resultData['responseData']);
        } else {
            $this->showMessages($resultData['isSuccess'], $resultData['messages']);

            if ($resultData['redirectTo']) {
                $result = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                $result = $result->setUrl($resultData['redirectTo']);
            } else {
                $result = $this->accountRedirect->getRedirect();
            }
        }

        return $result;
    }

    private function getRaw(string $responseData): Raw
    {
        $resultRaw = $this->rawFactory->create();
        $resultRaw->setHeader(
            'Cache-Control',
            'no-store, no-cache, must-revalidate, max-age=0',
            true
        );

        return $resultRaw->setContents(
            '<script>window.opener.postMessage(' . $responseData . ', "*");window.close();</script>'
        );
    }

    private function showMessages(bool $isSuccess, array $messages): void
    {
        foreach ($messages as $message) {
            if ($isSuccess) {
                $this->messageManager->addSuccessMessage($message);
            } else {
                $this->messageManager->addErrorMessage($message);
            }
        }
    }
}
