<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Simi\SimimageworxGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Simi\SimimageworxGraphQl\Helper\Data;

/**
 * StoreConfig page field resolver, used for GraphQL request processing.
 */
class MageWorxConfig implements ResolverInterface
{
	public $simiObjectManager;

	/**
	 * @var Simi\SimimageworxGraphQl\Helper\Data
	 */
	private $helper;

    /**
     * @param StoreConfigDataProvider $storeConfigsDataProvider
     */
    public function __construct(
		Data $helper,
		\Magento\Framework\ObjectManagerInterface $simiObjectManager
    )
    {
		$this->simiObjectManager = $simiObjectManager;
		$this->helper            = $helper;
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
    )
    {
        return $this->getSEOconfig($args);
    }


    /**
     * Get store config data
     *
     * @return array
     */
    public function getSEOconfig($args)
    {
        $additionalData = [];
		$mageworxHelperBase = $this->simiObjectManager->get('MageWorx\SeoBase\Helper\Data');
        if ($mageworxHelperBase) {
            // Canonical
            $canonical = [];
            if ($mageworxHelperBase->isCanonicalUrlEnabled()) {
                $canonical = [
                    'is_disable_by_robots' => $mageworxHelperBase->isDisableCanonicalByRobots(),
                    'ignore_pages' => $mageworxHelperBase->getCanonicalIgnorePages(),
                    'product_url_type' => $mageworxHelperBase->getProductCanonicalUrlType(),
                    'slash_home_page' => $mageworxHelperBase->getTrailingSlashForHomePage(),
                    'trailing_slash' => $mageworxHelperBase->getTrailingSlash(),
                    'canonical_for_ln' => $mageworxHelperBase->getCanonicalTypeForLayeredPages(),
                    'canonical_for_ln_multiple' => $mageworxHelperBase->getCanonicalTypeForLayeredPagesWithMultipleSelection(),
                ];
            }
            $additionalData['mageworx_seo'] = [
                'base' => [
                    'robots' => [
                        'noindex_pages' => $mageworxHelperBase->getNoindexPages(),
                        'noindex_additional_pages' => $mageworxHelperBase->getNoindexUserPages(),
                        'noindex_nofollow_additional_pages' => $mageworxHelperBase->getNoindexNofollowUserPages(),
                        'count_filters_for_noindex' => $mageworxHelperBase->getCountFiltersForNoindex(),
                        'attribute_combinations' => $mageworxHelperBase->getAttributeRobotsSettings(),
                        'noindex_follow_for_ln_multiple' => $mageworxHelperBase->isUseNoindexIfFilterMultipleValues(),
                        'default_category_ln_pages' => $mageworxHelperBase->getCategoryLnRobots(),
                    ],
                    'canonical' => $canonical,
                ],
                'markup' => [
                    'product' => [],
                    'category' => [],
                ]
            ];
            // Seo Markup
            $mageworxHelperProduct = $this->simiObjectManager->get('Simi\SimimageworxGraphQl\Helper\MageWorxProduct');
            if ($mageworxHelperProduct) {
                // Product
                $additionalData['mageworx_seo']['markup']['product'] = [
                    'is_specific_product' => $mageworxHelperProduct->isRsEnabledForSpecificProduct(),
                    'rs_enabled' => $mageworxHelperProduct->isRsEnabled(),
                    'og_enabled' => $mageworxHelperProduct->isOgEnabled(),
                    'tw_enabled' => $mageworxHelperProduct->isTwEnabled(),
                    'tw_username' => $mageworxHelperProduct->getTwUsername(),
                    'best_rating' => $mageworxHelperProduct->getBestRating(),
                    'add_reviews' => $mageworxHelperProduct->isReviewsEnabled(),
                    'use_multiple_offer' => $mageworxHelperProduct->useMultipleOffer(),
                    'crop_html_in_description' => $mageworxHelperProduct->getIsCropHtmlInDescription(),
                    'sku_enabled' => $mageworxHelperProduct->isSkuEnabled(),
                    'sku_code' => $mageworxHelperProduct->getSkuCode(),
                    'category_enabled' => $mageworxHelperProduct->isCategoryEnabled(),
                    'category_deepest' => $mageworxHelperProduct->isCategoryDeepest(),
                    'color_enabled' => $mageworxHelperProduct->isColorEnabled(),
                    'color_code' => $mageworxHelperProduct->getColorCode(),
                    'manufacturer_enabled' => $mageworxHelperProduct->isManufacturerEnabled(),
                    'manufacturer_code' => $mageworxHelperProduct->getManufacturerCode(),
                    'brand_enabled' => $mageworxHelperProduct->isBrandEnabled(),
                    'brand_code' => $mageworxHelperProduct->getBrandCode(),
                    'model_enabled' => $mageworxHelperProduct->isModelEnabled(),
                    'model_code' => $mageworxHelperProduct->getModelCode(),
                    'gtin_enabled' => $mageworxHelperProduct->isGtinEnabled(),
                    'gtin_code' => $mageworxHelperProduct->getGtinCode(),
                    'weight_enabled' => $mageworxHelperProduct->isWeightEnabled(),
                    'special_price_functionality' => $mageworxHelperProduct->isUseSpecialPriceFunctionality(),
                    'price_valid_until_default_value' => $mageworxHelperProduct->getPriceValidUntilDefaultValue(),
                    'condition_enabled' => $mageworxHelperProduct->isConditionEnabled(),
                    'condition_code' => $mageworxHelperProduct->getConditionCode(),
                    'condition_value_new' => $mageworxHelperProduct->getConditionValueForNew(),
                    'condition_value_used' => $mageworxHelperProduct->getConditionValueForUsed(),
                    'condition_value_damaged' => $mageworxHelperProduct->getConditionValueForDamaged(),
                    'condition_value_refurbished' => $mageworxHelperProduct->getConditionValueForRefurbished(),
                    'condition_value_default' => $mageworxHelperProduct->getConditionDefaultValue(),
                ];
            }
            // Category
            $mageworxHelperCategory = $this->simiObjectManager->get('MageWorx\SeoMarkup\Helper\Category');
            if ($mageworxHelperCategory) {
                $additionalData['mageworx_seo']['markup']['category'] = [
                    'rs_enabled' => $mageworxHelperCategory->isRsEnabled(),
                    'og_enabled' => $mageworxHelperCategory->isOgEnabled(),
                    'tw_enabled' => $mageworxHelperCategory->isTwEnabled(),
                    'tw_username' => $mageworxHelperCategory->getTwUsername(),
                ];
            }
            // SMS Page
            $mageworxHelperPage = $this->simiObjectManager->get('MageWorx\SeoMarkup\Helper\Page');
            if ($mageworxHelperPage) {
                $additionalData['mageworx_seo']['markup']['page'] = [
                    'og_enabled' => $mageworxHelperPage->isOgEnabled(),
                    'tw_enabled' => $mageworxHelperPage->isTwEnabled(),
                    'tw_username' => $mageworxHelperPage->getTwUsername(),
                ];
            }
            // Website
            $mageworxHelperWebsite = $this->simiObjectManager->get('MageWorx\SeoMarkup\Helper\Website');
            if ($mageworxHelperWebsite) {
                $additionalData['mageworx_seo']['markup']['website'] = [
                    'rs_enabled' => $mageworxHelperWebsite->isRsEnabled(),
                    'og_enabled' => $mageworxHelperWebsite->isOgEnabled(),
                    'tw_enabled' => $mageworxHelperWebsite->isTwEnabled(),
                    'tw_username' => $mageworxHelperWebsite->getTwUsername(),
                ];
            }
            // Seller
            $mageworxHelperSeller = $this->simiObjectManager->get('MageWorx\SeoMarkup\Helper\Seller');
            if ($mageworxHelperSeller && $mageworxHelperSeller->isRsEnabled()) {
                $image = '';
                if (!is_null($mageworxHelperSeller->getImage())) {
                    $image = $this->helper->storeManager->getStore()->getBaseUrl('media') . 'seller_image' . '/' . $mageworxHelperSeller->getImage();
                }
                $additionalData['mageworx_seo']['markup']['seller'] = [
                    'seller_type' => $mageworxHelperSeller->getType(),
                    'show_on_pages' => $mageworxHelperSeller->getPageType(),
                    'name' => $mageworxHelperSeller->getName(),
                    'image' => $image,
                    'description' => $mageworxHelperSeller->getDescription(),
                    'phone' => $mageworxHelperSeller->getPhone(),
                    'fax' => $mageworxHelperSeller->getFax(),
                    'email' => $mageworxHelperSeller->getEmail(),
                    'location' => $mageworxHelperSeller->getLocation(),
                    'region' => $mageworxHelperSeller->getRegionAddress(),
                    'street' => $mageworxHelperSeller->getStreetAddress(),
                    'post_code' => $mageworxHelperSeller->getPostCode(),
                    'price_range' => $mageworxHelperSeller->getPriceRange(),
                ];
            }
            // SEO Extended Templates
            $mageworxHelperXTemplates = $this->simiObjectManager->get('MageWorx\SeoXTemplates\Helper\Data');
            if ($mageworxHelperXTemplates) {
                $additionalData['mageworx_seo']['xtemplates'] = [
                    'product_seo_name' => $mageworxHelperXTemplates->isUseProductSeoName(),
                    'category_seo_name' => $mageworxHelperXTemplates->isUseCategorySeoName(),
                    'crop_meta_title' => $mageworxHelperXTemplates->isCropMetaTitle(),
                    'max_title_length' => $mageworxHelperXTemplates->getMaxLengthMetaTitle(),
                    'crop_meta_description' => $mageworxHelperXTemplates->isCropMetaDescription(),
                    'max_description_length' => $mageworxHelperXTemplates->getMaxLengthMetaDescription()
                ];
            }
        }
        return isset($additionalData['mageworx_seo']) ? json_encode($additionalData['mageworx_seo']) : '';
    }
}
