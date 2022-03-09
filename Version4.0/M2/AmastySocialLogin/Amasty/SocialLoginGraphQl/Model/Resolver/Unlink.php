<?php

declare(strict_types=1);

namespace Amasty\SocialLoginGraphQl\Model\Resolver;

use Amasty\SocialLogin\Model\Unlink as UnlinkModel;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class Unlink implements ResolverInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var UnlinkModel
     */
    private $unlinkModel;

    public function __construct(
        UnlinkModel $unlinkModel,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->unlinkModel = $unlinkModel;
    }

    /**
     * @param Field $field
     * @param \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array|\Magento\Framework\GraphQl\Query\Resolver\Value|mixed
     * @throws \Exception
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        try {
            if (isset($args['type'])) {
                $result = $this->unlinkModel->execute($args['type'], $context->getUserId());
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new GraphQlNoSuchEntityException(__('Wrong parameter.'));
        }

        return $result;
    }
}
