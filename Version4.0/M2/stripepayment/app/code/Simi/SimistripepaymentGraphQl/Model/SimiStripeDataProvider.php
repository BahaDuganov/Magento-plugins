<?php

declare(strict_types=1);

namespace Simi\SimistripepaymentGraphQl\Model;

use Magento\QuoteGraphQl\Model\Cart\Payment\AdditionalDataProviderInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

class SimiStripeDataProvider implements AdditionalDataProviderInterface
{
    public function getData(array $data): array
    {
        $paymentData = $data;
        $additionalData = [];
        if (isset($paymentData['simi_stripe_cc_token']))
            $additionalData['cc_token'] = $paymentData['simi_stripe_cc_token'];

        if (isset($paymentData['simi_stripe_cc_exp_month']))
            $additionalData['cc_exp_month'] = $paymentData['simi_stripe_cc_exp_month'];

        if (isset($paymentData['simi_stripe_cc_last4']))
            $additionalData['cc_last4'] = $paymentData['simi_stripe_cc_last4'];

        if (isset($paymentData['simi_stripe_cc_type']))
            $additionalData['cc_type'] = $paymentData['simi_stripe_cc_type'];

        if (isset($paymentData['simi_stripe_three_d_client_secret']))
            $additionalData['three_d_client_secret'] = $paymentData['simi_stripe_three_d_client_secret'];

        if (isset($paymentData['simi_stripe_three_d_src']))
            $additionalData['three_d_src'] = $paymentData['simi_stripe_three_d_src'];
            
        return $additionalData;
    }
}
