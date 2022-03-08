<?php

declare(strict_types=1);

namespace Amasty\SocialLogin\ViewModel\AdvancedReview;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException as NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\Element\BlockFactory;
use Magento\Review\Block\Form as ReviewsForm;

class RenderReviewForm implements ArgumentInterface
{
    /**
     * @var BlockFactory
     */
    private $blockFactory;

    public function __construct(
        BlockFactory $blockFactory
    ) {
        $this->blockFactory = $blockFactory;
    }

    /**
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(): string
    {
        $component = ['components' => ['review-form' => ['component' => 'Magento_Review/js/view/review']]];
        /** @var ReviewsForm $block **/
        $block = $this->blockFactory->createBlock(
            ReviewsForm::class,
            ['data' => ['jsLayout' => $component]]
        );
        $block->setTemplate('Magento_Review::form.phtml');

        return $block->toHtml();
    }
}
