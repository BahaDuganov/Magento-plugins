<?php

declare(strict_types=1);

namespace Amasty\SocialLogin\Controller\Ajax;

use Amasty\SocialLogin\ViewModel\AdvancedReview\RenderReviewForm;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class RenderReviewsForm extends \Magento\Framework\App\Action\Action
{
    /**
     * @var RenderReviewForm
     */
    private $renderReviewForm;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        RenderReviewForm $renderReviewForm
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->renderReviewForm = $renderReviewForm;
    }

    public function execute()
    {
        $result = [];

        try {
            $result['form'] = $this->renderReviewForm->execute();
        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
        }

        $resultJson = $this->resultJsonFactory->create();

        return $resultJson->setData($result);
    }
}
