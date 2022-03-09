<?php

declare(strict_types=1);

namespace Amasty\SocialLogin\Model;

use Amasty\SocialLogin\Model\Repository\SocialRepository;
use Amasty\SocialLogin\Model\ResourceModel\Social\CollectionFactory;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\View\Result\PageFactory;

class Unlink
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var SocialRepository
     */
    private $socialRepository;

    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var MessageManagerInterface
     */
    private $messageManager;

    public function __construct(
        CollectionFactory $collectionFactory,
        SocialRepository $socialRepository,
        PageFactory $resultPageFactory,
        MessageManagerInterface $messageManager
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->socialRepository = $socialRepository;
        $this->resultPageFactory = $resultPageFactory;
        $this->messageManager = $messageManager;
    }

    public function execute(string $type, int $customerId): array
    {
        if ($customerId) {
            try {
                $collection = $this->collectionFactory->create()
                    ->addFieldToFilter('customer_id', $customerId)
                    ->addFieldToFilter('type', $type);

                if ($collection->count()) {
                    foreach ($collection as $item) {
                        $this->socialRepository->delete($item);
                    }

                    $result = ['isSuccess' => true, 'message' => __('Your account was successfully unlinked.')];
                }
            } catch (\Exception $exception) {
                $this->messageManager->addErrorMessage(
                    __('An unspecified error occurred. Please try again later.')
                );
            }
        }

        return $result ?? [
                'isSuccess' => false,
                'message' => __('Sorry. We can`t find information about your account.')
            ];
    }
}
