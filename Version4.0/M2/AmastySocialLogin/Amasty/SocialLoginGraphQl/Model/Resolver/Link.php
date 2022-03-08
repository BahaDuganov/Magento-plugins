<?php

declare(strict_types=1);

namespace Amasty\SocialLoginGraphQl\Model\Resolver;

use Amasty\SocialLogin\Model\Login as LoginModel;
use Magento\Customer\Model\Session;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class Link implements ResolverInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var LoginModel
     */
    private $loginModel;

    /**
     * @var Session
     */
    private $session;

    public function __construct(
        LoginModel $loginModel,
        Session $session,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->loginModel = $loginModel;
        $this->session = $session;
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
                $this->session->setCustomerId($context->getUserId());
                $result = $this->loginModel->execute(['type' => $args['type']]);
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new GraphQlNoSuchEntityException(__('Wrong parameter.'));
        }

        return $result['isSuccess'] ?? null;
    }
}
