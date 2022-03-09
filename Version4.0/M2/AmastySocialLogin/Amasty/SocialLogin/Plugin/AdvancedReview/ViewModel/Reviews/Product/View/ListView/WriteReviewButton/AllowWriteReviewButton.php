<?php

declare(strict_types=1);

namespace Amasty\SocialLogin\Plugin\AdvancedReview\ViewModel\Reviews\Product\View\ListView\WriteReviewButton;

use Amasty\AdvancedReview\ViewModel\Reviews\Product\View\ListView\WriteReviewButton as WriteReviewButton;
use Magento\Customer\Model\SessionFactory;
use Magento\Framework\UrlInterface;

class AllowWriteReviewButton
{
    /**
     * @var SessionFactory
     */
    private $sessionFactory;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var bool
     */
    private $shouldSaveUrl = false;

    public function __construct(
        SessionFactory $sessionFactory,
        UrlInterface $urlBuilder
    ) {
        $this->sessionFactory = $sessionFactory;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param WriteReviewButton $subject
     * @param bool $result
     * @return bool
     */
    public function afterIsCanRender($subject, bool $result): bool
    {
        $this->shouldSaveUrl = $result;
        return true;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param WriteReviewButton $subject
     * @param bool $result
     * @return bool
     */
    public function afterGetButtonUrl($subject, string $result): string
    {
        return ($this->shouldSaveUrl)
            ? $result
            : $this->urlBuilder->getUrl('customer/account/login');
    }

    /**
     * @return \Magento\Customer\Model\Session
     */
    private function getCustomerSession()
    {
        return $this->sessionFactory->create();
    }
}
