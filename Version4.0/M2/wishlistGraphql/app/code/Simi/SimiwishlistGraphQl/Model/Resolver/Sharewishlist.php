<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Simi\SimiwishlistGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Wishlist\Model\Item;
use Magento\Wishlist\Model\Wishlist;
use Magento\Wishlist\Model\WishlistFactory;
use Magento\Wishlist\Model\ResourceModel\Wishlist as WishlistResourceModel;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Escaper;
use Magento\Framework\View\Result\Layout as ResultLayout;

/**
 * Fetches the Wishlist data according to the GraphQL schema
 */
class Sharewishlist implements ResolverInterface
{
    /**
     * @var Item
     */
    private $wishlistItem;

    /**
     * @var WishlistResourceModel
     */
    private $wishlistResource;

    /**
     * @var WishlistFactory
     */
    private $wishlistFactory;

    protected $_transportBuilder;
    protected $scopeConfig;
    protected $storeManager;
    protected $customerFactory;
    /**
     * @var \Magento\Customer\Helper\View
     */
    protected $_customerHelperView;
    protected $layoutInterface;
    protected $_wishlistConfig;
    protected $escaper;
    protected $resultFactory;
    /**
     * @param Item $wishlistItem
     */
    public function __construct(
        WishlistResourceModel $wishlistResource,
        WishlistFactory $wishlistFactory,
        Item $wishlistItem,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        CustomerFactory $customerFactory,
        \Magento\Customer\Helper\View $customerHelperView,
        \Magento\Framework\View\LayoutInterface $layoutInterface,
        Escaper $escaper,
        \Magento\Wishlist\Model\Config $_wishlistConfig,
        ResultFactory $resultFactory
    )
    {
        $this->wishlistResource = $wishlistResource;
        $this->wishlistFactory = $wishlistFactory;
        $this->wishlistItem = $wishlistItem;
        $this->_transportBuilder = $transportBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->customerFactory = $customerFactory;
        $this->_customerHelperView = $customerHelperView;
        $this->layoutInterface = $layoutInterface;
        $this->_wishlistConfig = $_wishlistConfig;
        $this->escaper = $escaper;
        $this->resultFactory = $resultFactory;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null) {
        if (!isset($args['emails']) || empty($args['emails'])) {
            throw new GraphQlInputException(__('Required email is missing'));
        }
        $emails = $args['emails'];
        $emails = empty($emails) ? $emails : explode(',', $emails);

        $customerId = $context->getUserId();
        if (!$customerId && 0 === $customerId) {
            throw new GraphQlAuthorizationException(__('The current user cannot perform operations on wishlist'));
        }
        $customer = $this->customerFactory->create()->load($customerId);
        $customerName = $customer->getFirstname() . ' ' . $customer->getLastname();
        $wishlist = $this->wishlistFactory->create();
        $this->wishlistResource->load($wishlist, $customerId, 'customer_id');

        $sharingLimit = $this->_wishlistConfig->getSharingEmailLimit();
        $textLimit = $this->_wishlistConfig->getSharingTextLimit();
        $emailsLeft = $sharingLimit - $wishlist->getShared();

        $error = false;
        $message = '';

        if (isset($args['message'])) {

            $message = (string)$args['message'];
            if (strlen($message) > $textLimit) {
                $error = __('Message length must not exceed %1 symbols', $textLimit);
            } else {
                $message = nl2br($this->escaper->escapeHtml($message));
                if (empty($emails)) {
                    $error = __('Please enter an email address.');
                } else {
                    if (count($emails) > $emailsLeft) {
                        $error = __('Maximum of %1 emails can be sent.', $emailsLeft);
                    } else {
                        foreach ($emails as $index => $email) {
                            $email = trim($email);
                            if (!\Zend_Validate::is($email, \Magento\Framework\Validator\EmailAddress::class)) {
                                $error = __('Please enter a valid email address.');
                                break;
                            }
                            $emails[$index] = $email;
                        }
                    }
                }
            }
        }

        if ($error) {
            throw new GraphQlInputException($error);
        }
        $sharingCode = $wishlist->getSharingCode();
        $sent = 0;

        $resultLayout = $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
        $resultLayout->addHandle('wishlist_email_items');

        // $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        // $objectManager->create('\Magento\Customer\Model\Session')->setCustomerAsLoggedIn($customer);

        try {
            $itemsHtml = $this->getWishlistItems($resultLayout, $wishlist);
            foreach ($emails as $email) {
                $transport = $this->_transportBuilder->setTemplateIdentifier(
                    $this->scopeConfig->getValue(
                        'wishlist/email/email_template',
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                    )
                )->setTemplateOptions(
                    [
                        'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                        'store' => $this->storeManager->getStore()->getStoreId(),
                    ]
                )->setTemplateVars(
                    [
                        'customer' => $customer,
                        'customerName' => $customerName,
                        'salable' => $wishlist->isSalable() ? 'yes' : '',
                        'items' => $itemsHtml,
                        'viewOnSiteLink' => $this->scopeConfig->getValue( 'simiconnector/general/pwa_studio_url', 
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE) . 'shared_wishlist.html?sharingCode=' . $sharingCode,
                        'message' => $message,
                        'store' => $this->storeManager->getStore(),
                    ]
                )->setFrom(
                    $this->scopeConfig->getValue(
                        'wishlist/email/email_identity',
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                    )
                )->addTo(
                    $email
                )->getTransport();

                $transport->sendMessage();

                $sent++;
            }
        } catch (\Exception $e) {
            $wishlist->setShared($wishlist->getShared() + $sent);
            $wishlist->save();
            throw $e;
        }

        $wishlist->setShared($wishlist->getShared() + $sent);
        $wishlist->save();


        return [
            'status' => true
        ];
    }

    protected function getWishlistItems($resultLayout, $wishlist)
    {
        return $resultLayout->getLayout()
            ->createBlock('Simi\SimiwishlistGraphQl\Block\Share\Email\Items')
            ->setWishlist($wishlist)
            ->setTemplate('Simi_SimiwishlistGraphQl::email/items.phtml')
            ->toHtml();
    }
}
