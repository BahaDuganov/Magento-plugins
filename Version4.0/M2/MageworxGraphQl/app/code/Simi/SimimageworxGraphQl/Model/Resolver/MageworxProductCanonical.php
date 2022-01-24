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
        if (!isset($value['model']))
            return [];
        $product = $value['model'];
        $url = '';
        $extraField = 'no';
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $canonicalProduct = $objectManager->get('MageWorx\SeoBase\Model\Canonical\Product');
        if ($product) {
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
            //need to fetch again to get below attributes, for details api so not very affecting
            $product = $objectManager->create('Magento\Catalog\Model\Product')->load($product->getId());
            $extraField = [];
            $extraField['pre_order'] = $product->getData('pre_order');
            $extraField['is_salable'] = $product->getData('is_salable');
            $extraField['color'] = $product->getData('color');
            $extraField['manufacturer'] = $product->getData('manufacturer');
            $extraField['gtin14'] = $product->getData('gtin14');
            $extraField['brand'] = $product->getData('brand');
            $extraField['model'] = $product->getData('model');
            $extraField = json_encode($extraField);
        }

        return [
            'url' => $url,
            'extraData' => $extraField
        ];
    }
}
