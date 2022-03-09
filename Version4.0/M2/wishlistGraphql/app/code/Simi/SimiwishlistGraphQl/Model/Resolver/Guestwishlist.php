<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Simi\SimiwishlistGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Wishlist\Model\Wishlist;
use Magento\Wishlist\Model\WishlistFactory;
use Magento\Customer\Model\CustomerFactory;

/**
 * Fetches customer wishlist data
 */
class Guestwishlist implements ResolverInterface
{
    /**
     * @var WishlistFactory
     */
    private $wishlistFactory;
    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @param WishlistFactory $wishlistFactory
     */
    public function __construct(
        WishlistFactory $wishlistFactory,
        CustomerFactory $customerFactory
    )
    {
        $this->wishlistFactory = $wishlistFactory;
        $this->customerFactory = $customerFactory;
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
        if (!isset($args['sharing_code']) || empty($args['sharing_code'])) {
            throw new GraphQlInputException(__('Required sharing_code is missing'));
        }

        $wishlist = $this->wishlistFactory->create();
        $wishlist->loadByCode($args['sharing_code']);
        $customerModel = $this->customerFactory->create()->load($wishlist->getCustomerId());
        return [
            'wishlist_data' => [
                'id' => (string)$wishlist->getId(),
                'sharing_code' => $wishlist->getSharingCode(),
                'updated_at' => $wishlist->getUpdatedAt(),
                'items_count' => $wishlist->getItemsCount(),
                'model' => $wishlist,
            ],
            'user_name' => $customerModel->getFirstname()
        ];
    }
}
