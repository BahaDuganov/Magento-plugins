<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Simi\SimimageworxGraphQl\Model\Resolver\Product;

// use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;

class MageWorxCategoryPage implements ResolverInterface
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
        $canonicalCategory = $objectManager->get('MageWorx\SeoBase\Model\Canonical\Category');
        $category = $value['model'];
        if ($category && $canonicalCategory) {
            $canonicalCategory->setEntity($category);
            // $canonicalCategory->setFullActionName('pwa_graphql'); // To enable canonical
            $url = $canonicalCategory->getCanonicalUrl();
            // $pwaBaseUrl = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')
            //     ->getValue('simiconnector/general/pwa_studio_url');
            // if ($pwaBaseUrl) {
            //     $baseUrl = $this->storeManager->getStore()->getBaseUrl();
            //     $url = str_replace($baseUrl, '', $url);
            //     $url = $pwaBaseUrl . $url;
            // }
        }

        return [
            'url' => $url
        ];
    }
}