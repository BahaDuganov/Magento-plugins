<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Simi\SimimageworxGraphQl\Model\Resolver;

// use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;

class MageworxProductCanonical implements ResolverInterface
{
    // public function __construct(
    // ) {
    // }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): array {

        $url = '';
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $canonicalProduct = $objectManager->get('MageWorx\SeoBase\Model\Canonical\Product');
        $registry = $objectManager->get('\Magento\Framework\Registry');
        if ($registry->registry('product')) {
            $product = $registry->registry('product');
            if ($canonicalProduct) {
                $canonicalProduct->setEntity($product);
                $url = $canonicalProduct->getCanonicalUrl();
                // $pwaBaseUrl = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')
                //     ->getValue('simiconnector/general/pwa_studio_url');
                // if ($pwaBaseUrl) {
                //     $store   = $context->getExtensionAttributes()->getStore();
                //     $baseUrl = $store->getBaseUrl();
                //     $url = str_replace($baseUrl, '', $url);
                //     $url = $pwaBaseUrl . $url;
                // }
            }
        }

        return [
            'url' => $url
        ];
    }
}