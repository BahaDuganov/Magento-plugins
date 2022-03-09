<?php

namespace Amasty\SocialLogin\Controller\Social;

use Amasty\SocialLogin\Model\Login;
use Amasty\SocialLogin\Model\SocialData;
use Hybridauth\Exception\InvalidArgumentException;
use Hybridauth\Storage\Session;

class Callback extends \Magento\Framework\App\Action\Action
{
    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if ($this->checkRequest('hauth_start', false)
            && ($this->checkRequest('error_reason', 'user_denied')
                && $this->checkRequest('error', 'access_denied')
                && $this->checkRequest('error_code', '200')
                && $this->checkRequest('hauth_done', 'Facebook')
                || ($this->checkRequest('hauth_done', 'Twitter') && $this->checkRequest('denied'))
            )
        ) {
            return $this->_redirect('customer/account');
        }

        try {
            $storage = new Session();
            $requestParams = $this->getRequest()->getParams() ?: [];
            $socialParams = $storage->get(Login::AMSOCIAL_LOGIN_PARAMS) ?: [];
            $params = array_merge($requestParams, $socialParams);
            $socialPath = $this->_url->getUrl(
                SocialData::AMSOCIALLOGIN_SOCIAL_LOGIN_PATH,
                ['_query' => $params]
            );
            $this->_redirect($socialPath);
        } catch (InvalidArgumentException $e) {
            return $this->_redirect($this->_redirect->getRefererUrl());
        }
    }

    /**
     * @param string $key
     * @param bool $value
     * @return bool
     */
    public function checkRequest($key, $value = false)
    {
        $param = $this->getRequest()->getParam($key, false);
        if ($value) {
            return $param == $value;
        }

        return (bool)$param;
    }
}
