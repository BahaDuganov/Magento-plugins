<?php

declare(strict_types=1);

namespace Simi\SimiStripeIntegrationGraphQl\Model;

use Magento\QuoteGraphQl\Model\Cart\Payment\AdditionalDataProviderInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

class SimiStripeIntegrationDataProvider implements AdditionalDataProviderInterface
{
    public function getData(array $data): array
    {
        $paymentData = $data;
        $additionalData = [];
        if (isset($paymentData['simi_stripe_integration_cc_stripejs_token']))
            $additionalData['cc_stripejs_token'] = $paymentData['simi_stripe_integration_cc_stripejs_token'];
        if (isset($paymentData['simi_stripe_integration_cc_saved']))
            $additionalData['cc_saved'] = $paymentData['simi_stripe_integration_cc_saved'];
        if (isset($paymentData['simi_stripe_integration_cc_save']))
            $additionalData['cc_save'] = $paymentData['simi_stripe_integration_cc_save'];
        return $additionalData;
    }
}
