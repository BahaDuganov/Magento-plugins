<?php

declare(strict_types=1);

namespace Amasty\SocialLoginGraphQl\Model\Resolver;

use Amasty\SocialLogin\Model\SocialData;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Psr\Log\LoggerInterface;

class SocialButtons implements ResolverInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SocialData
     */
    private $socialData;

    public function __construct(
        LoggerInterface $logger,
        SocialData $socialData
    ) {
        $this->logger = $logger;
        $this->socialData = $socialData;
    }

    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        try {
            $result = $this->socialData->getEnabledSocials();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new GraphQlNoSuchEntityException(__('Something went wrong. Please review the error log.'));
        }

        return $result;
    }
}
