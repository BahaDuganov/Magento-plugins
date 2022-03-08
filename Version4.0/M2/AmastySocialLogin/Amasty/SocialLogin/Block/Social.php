<?php

declare(strict_types=1);

namespace Amasty\SocialLogin\Block;

use Amasty\SocialLogin\Model\ConfigData;
use Amasty\SocialLogin\Model\SocialData;
use Amasty\SocialLogin\Model\Source\LoginPosition;
use Magento\Framework\App\Http\Context;
use Magento\Framework\View\Element\Template;

class Social extends Template
{
    public const POSITION_KEY = 'position';

    /**
     * Default cache lifetime - 1 day
     */
    protected const DEFAULT_LIFETIME = 86400;

    /**
     * Default template.
     *
     * @var string
     */
    protected $_template = 'Amasty_SocialLogin::social.phtml';

    /**
     * @var SocialData
     */
    private $socialData;

    /**
     * @var ConfigData
     */
    private $configData;

    /**
     * @var Context
     */
    private $httpContext;

    public function __construct(
        Template\Context $context,
        SocialData $socialData,
        ConfigData $configData,
        Context $httpContext,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->socialData = $socialData;
        $this->configData = $configData;
        $this->httpContext = $httpContext;
    }

    /**
     * Additional Cache Key parts.
     *
     * @return array
     */
    public function getCacheKeyInfo()
    {
        $cacheKeyInfo = parent::getCacheKeyInfo();

        if ($this->isLoginSensitive()) {
            $cacheKeyInfo['logged'] = $this->isLoggedIn();
        }
        $cacheKeyInfo['position'] = $this->getPosition();

        return $cacheKeyInfo;
    }

    /**
     * Is customer logged in.
     *
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        return (bool) $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->configData->getConfigValue('general/enabled')) {
            return '';
        }

        if ($this->isLoginSensitive() && $this->isLoggedIn()) {
            return '';
        }

        $position = $this->getPosition();
        $enabled = $this->getEnabledBlockPositions();
        if (!\in_array($position, $enabled, true)) {
            return '';
        }

        return parent::_toHtml();
    }

    /**
     * Current block login position.
     *
     * @return string
     */
    public function getPosition(): string
    {
        if (!$this->hasData(self::POSITION_KEY)) {
            $name = $this->getNameInLayout();
            $position = str_replace('amsociallogin-social-', '', $name);

            $this->setData(self::POSITION_KEY, $position);
        }

        return $this->getData(self::POSITION_KEY);
    }

    /**
     * Is block related to logged-out users.
     * If returns true then block should render only for guests.
     *
     * @return bool
     */
    public function isLoginSensitive(): bool
    {
        return (bool) $this->getData('is_login_sensitive');
    }

    /**
     * @return array
     */
    private function getEnabledBlockPositions(): array
    {
        return $this->configData->getLoginPosition();
    }

    /**
     * @return array
     */
    public function getEnabledSocials()
    {
        return $this->socialData->getEnabledSocials();
    }

    /**
     * @return bool
     */
    public function isPopupEnabled()
    {
        return $this->configData->isPopupEnabled();
    }

    /**
     * @return string
     */
    public function getButtonShapeClass()
    {
        return $this->configData->getButtonShapeClass();
    }

    /**
     * @return bool
     */
    public function getButtonLabelState()
    {
        return $this->configData->getButtonLabelState();
    }

    /**
     * @return string
     */
    public function getPositionTitle()
    {
        return $this->configData->getPositionTitle();
    }

    /**
     * Get block cache life time
     *
     * @return int|bool|null
     */
    protected function getCacheLifetime()
    {
        if (!$this->hasData('cache_lifetime')) {
            return static::DEFAULT_LIFETIME;
        }

        return parent::getCacheLifetime();
    }
}
