<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MyFatoorah\Myfatoorah\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use MyFatoorah\Myfatoorah\Gateway\Http\Client\ClientMock;

/**
 * Class ConfigProvider
 */
final class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'myfatoorah';

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'transactionResults' => [
                        ClientMock::SUCCESS => __('Success'),
                        ClientMock::FAILURE => __('Fraud')
                    ]
                ]
            ]
        ];
    }
}
