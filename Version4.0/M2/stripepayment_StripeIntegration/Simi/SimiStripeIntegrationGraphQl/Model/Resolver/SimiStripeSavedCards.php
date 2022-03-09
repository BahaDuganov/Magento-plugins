<?php

declare(strict_types=1);

namespace Simi\SimiStripeIntegrationGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;

class SimiStripeSavedCards implements ResolverInterface
{
    /**
     * @var GetCustomer
     */
    protected $getCustomer;

    /**
     * @var StripeCustomer
     */
    protected $stripeCustomer;

    /**
     * @inheritdoc
     */
    public function __construct(
        GetCustomer $getCustomer,
        \StripeIntegration\Payments\Model\StripeCustomer $stripeCustomer
    ) {
        $this->getCustomer = $getCustomer;
        $this->stripeCustomer = $stripeCustomer;
    }

    public function resolve(
        $field,
        $context,
        $info,
        array $value = null,
        array $args = null
    ) {
        $customer = $this->getCustomer->execute($context);
        $stripeCustomer = $this->stripeCustomer;
        if (!$stripeCustomer->_stripeCustomer)
            $stripeCustomer->_stripeCustomer = $stripeCustomer->retrieveByStripeID($stripeCustomer->getStripeId());
        if (!$stripeCustomer->_stripeCustomer)
            return [];
        $cards = $stripeCustomer->listCards();
        $cardArray = [];
        foreach ($cards as $card) {
            $cardArray[] = array(
                'id' => $card->id,
                'brand' => $card->brand,
                'exp_month' => $card->exp_month,
                'exp_year' => $card->exp_year,
                'last4' => $card->last4,
                'three_d_secure_usage' => $card->three_d_secure_usage->supported,
            );
        }
        return $cardArray;
    }
}
