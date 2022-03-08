<?php

declare(strict_types=1);

namespace Amasty\SocialLogin\Block;

use Amasty\SocialLogin\Model\Source\LoginPosition;
use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Framework\View\Element\BlockFactory;

/**
 * Add Social Login JS configuration.
 */
class LayoutProcessor implements LayoutProcessorInterface
{
    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @var BlockFactory
     */
    private $blockFactory;

    public function __construct(ArrayManager $arrayManager, BlockFactory $blockFactory)
    {
        $this->arrayManager = $arrayManager;
        $this->blockFactory = $blockFactory;
    }

    /**
     * @param array $jsLayout
     *
     * @return array
     */
    public function process($jsLayout): array
    {
        if ($uiComponentConfig = $this->getUiComponentConfig()) {
            $jsLayout = $this->arrayManager->set(
                'components/checkout/children/steps/children/shipping-step/children/shippingAddress/children/am-social',
                $jsLayout,
                $uiComponentConfig
            );
        }

        return $jsLayout;
    }

    /**
     * Ui component configuration for Social Login bar.
     *
     * @return array|null
     */
    private function getUiComponentConfig(): ?array
    {
        $socialBlock = $this->blockFactory->createBlock(
            Social::class,
            [
                'data' => [
                    'position' => LoginPosition::CHECKOUT,
                    'cache_lifetime' => 86400,
                    'is_login_sensitive' => true,
                ]
            ]
        );

        $html = $socialBlock->toHtml();
        if (!$html) {
            return null;
        }

        return [
            'component' => 'Magento_Ui/js/form/components/html',
            'additionalClasses' => [
                'amsl-socials-checkout' => true
            ],
            'displayArea' => 'customer-email',
            'sortOrder' => 20,
            'config' => [
                'content' => $html
            ]
        ];
    }
}
