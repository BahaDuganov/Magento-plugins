<?php
namespace Simi\MyFatoorahGraphql\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MyFatoorah\MyFatoorahPaymentGateway\Helper\Data;

class MyFatoorahConfig implements ResolverInterface
{
    
    protected $helperData;

    public function __construct(
        Data $helperData
    )
    {
        $this->helperData = $helperData;
    }

    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $completeUrl = $this->helperData->getCheckoutUrl();
        // $canceledUrl = $this->helperData->getCanceledUrl();
        $output = [];
        $output['completeUrl'] = $completeUrl;
        // $output['canceledUrl'] = $canceledUrl;
        return $output ;
    }
}