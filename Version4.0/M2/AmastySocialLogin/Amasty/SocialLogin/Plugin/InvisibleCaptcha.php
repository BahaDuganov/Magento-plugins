<?php

namespace Amasty\SocialLogin\Plugin;


use Amasty\InvisibleCaptcha\Model\Captcha;

class InvisibleCaptcha
{
    /**
     * @param Captcha $subject
     * @param string $result
     *
     * @return string
     */
    public function afterGetSelectors(Captcha $subject, $result)
    {
        $result .= ', .am-login-popup .form';

        return $result;
    }
}
