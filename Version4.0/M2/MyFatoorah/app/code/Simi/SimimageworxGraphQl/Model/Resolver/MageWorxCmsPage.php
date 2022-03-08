<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Simi\SimimageworxGraphQl\Model\Resolver;

use Magento\Cms\Api\PageRepositoryInterface;
use Magento\CmsGraphQl\Model\Resolver\DataProvider\Page as PageDataProvider;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * CMS page field resolver, used for GraphQL request processing
 */
class MageWorxCmsPage implements ResolverInterface
{
    /**
     * @var PageDataProvider
     */
    private $pageDataProvider;

    private $pageRepository;

    /**
     *
     * @param PageDataProvider $pageDataProvider
     */
    public function __construct(
        PageDataProvider $pageDataProvider,
        PageRepositoryInterface $pageRepository
    ) {
        $this->pageDataProvider = $pageDataProvider;
        $this->pageRepository = $pageRepository;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        try {
            if (isset($value['page_id'])) {
                $page = $this->pageRepository->getById((int)$value['page_id']);
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $canonicalPage = $objectManager->get('MageWorx\SeoBase\Model\Canonical\Page');
                if ($canonicalPage) {
                    $url = $canonicalPage->setEntity($page)->getCanonicalUrl();
                    // $pwaBaseUrl = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')
                    //     ->getValue('simiconnector/general/pwa_studio_url');
                    // if ($pwaBaseUrl) {
                    //     $baseUrl   = $context->getExtensionAttributes()->getStore()->getBaseUrl();
                    //     $url = str_replace($baseUrl, '', $url);
                    //     $url = $pwaBaseUrl . $url;
                    // }
                    return ['url' => $url];
                }
            }
        } catch (NoSuchEntityException $e) {
        }
        return ['url' => ''];
    }
}
