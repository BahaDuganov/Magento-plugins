<?php

declare(strict_types=1);

namespace Amasty\SocialLogin\Model;

use Amasty\SocialLogin\Model\Config\GdprSocialLogin;
use Amasty\SocialLogin\Model\Repository\SocialRepository;
use Hybridauth\Storage\Session;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface as CustomerData;
use Magento\Customer\Model\AuthenticationInterface;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\SecurityViolationException;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PhpCookieManager;
use Magento\Framework\UrlInterface;
use Magento\Framework\Validator\Exception as ValidatorException;
use Magento\Integration\Model\Oauth\TokenFactory as TokenModelFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Login
{
    const AMSOCIAL_LOGIN_PARAMS = 'amsocial_login_params';

    const PWA_TOKEN_PARAM = 'generateToken';

    const PWA_TOKEN = 'token';

    const REFERER = 'referer_url';

    private $messages = [
        'success' => [],
        'error'   => []
    ];

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $session;

    /**
     * @var Social
     */
    private $socialModel;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var PhpCookieManager
     */
    private $cookieManager;

    /**
     * @var CookieMetadataFactory
     */
    private $cookieMetadataFactory;

    /**
     * @var SocialRepository
     */
    private $socialRepository;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EncoderInterface
     */
    private $jsonEncoder;

    /**
     * @var AccountManagementInterface
     */
    private $accountManagement;

    /**
     * @var CustomerUrl
     */
    private $customerUrl;

    /**
     * @var AuthenticationInterface
     */
    private $authentication;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var SocialData
     */
    private $socialData;

    /**
     * @var array
     */
    private $redirectTo;

    /**
     * @var ConfigData
     */
    private $configData;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var TokenModelFactory
     */
    private $tokenFactory;

    /**
     * @var RedirectInterface
     */
    private $redirect;

    public function __construct(
        UrlInterface $url,
        \Magento\Customer\Model\Session $session,
        Social $socialModel,
        StoreManagerInterface $storeManager,
        PhpCookieManager $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        CustomerRepositoryInterface $customerRepository,
        SocialRepository $socialRepository,
        LoggerInterface $logger,
        EncoderInterface $jsonEncoder,
        AccountManagementInterface $accountManagement,
        CustomerUrl $customerUrl,
        AuthenticationInterface $authentication,
        CheckoutSession $checkoutSession,
        SocialData $socialData,
        ConfigData $configData,
        ManagerInterface $eventManager,
        TokenModelFactory $tokenFactory,
        RedirectInterface $redirect
    ) {
        $this->session = $session;
        $this->socialModel = $socialModel;
        $this->storeManager = $storeManager;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->socialRepository = $socialRepository;
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
        $this->jsonEncoder = $jsonEncoder;
        $this->accountManagement = $accountManagement;
        $this->customerUrl = $customerUrl;
        $this->authentication = $authentication;
        $this->checkoutSession = $checkoutSession;
        $this->socialData = $socialData;
        $this->configData = $configData;
        $this->url = $url;
        $this->eventManager = $eventManager;
        $this->tokenFactory = $tokenFactory;
        $this->redirect = $redirect;
    }

    public function execute(array $params): array
    {
        $storage = new Session();
        $type = $params['type'] ?? null;

        if (!$type) {
            $params = $this->prepareParams($storage, $params);
            $type = isset($params['type']) ? $params['type'] : null;
        } else {
            $params[self::REFERER] = $this->redirect->getRefererUrl();
        }

        $oldToken = $params[self::PWA_TOKEN] ?? null;

        if (!$type || !$this->socialData->isSocialEnabled($type)) {
            $this->addErrorMessage(__('Sorry. We cannot find social type. Please try again later.'));

            return $this->returnAction($params);
        }

        try {
            $storage->set(self::AMSOCIAL_LOGIN_PARAMS, $params);
            $userProfile = $this->socialModel->getUserProfile($type);
            if (!$userProfile->identifier) {
                $this->addErrorMessage(
                    __('Sorry. We cannot find your email. Please enter email in your %1 profile.', ucfirst($type))
                );

                return $this->returnAction($params);
            }

            $websiteId = $this->storeManager->getStore()->getWebsiteId();
            $customer = $this->socialRepository->getCustomerBySocial($userProfile->identifier, $type, $websiteId);
            if (($this->session->isLoggedIn() || $oldToken) && !isset($params[self::PWA_TOKEN_PARAM])) {
                if ($customer && (int) $customer->getId() !== $this->getCurrentCustomerId($oldToken)) {
                    $this->addErrorMessage(
                        __('Sorry. We cannot connect to this social profile. It is used for another site account.')
                    );

                    return $this->returnAction($params);
                } else {
                    $customer = $this->getCurrentCustomer($oldToken);
                    $user = $this->socialData->createUserData($userProfile, $type);
                    $this->socialRepository->createSocialAccount($user, $customer->getId(), $type);
                }
            }
            if (!$customer) {
                $this->session->setUserProfile($userProfile);
                $this->session->setType($type);
                if (!$userProfile->email) {
                    $this->addErrorMessage($this->getErrorMessage($type));
                    $this->createRedirectAfterError($this->url->getUrl('customer/account/create'));

                    return $this->returnAction($params);
                }
                $customer = $this->getCustomerProcess($userProfile, $type);
            }

            if ($customer && $this->authenticate($customer)) {
                if ($this->isNeedToken($storage)) {
                    $token = $this->tokenFactory->create()->createCustomerToken($customer->getId())->getToken();
                } else {
                    if (!$oldToken) {
                        $this->refresh($customer);
                    }
                }
                if ($userProfile && $userProfile->identifier) {
                    $this->session->setAmSocialIdentifier($userProfile->identifier);
                }
                $this->session->setUserProfile(null);
                $this->addSuccessMessage(
                    __('You have successfully logged in using your %1 account.', ucfirst($type))
                );
            }
        } catch (LocalizedException $e) {
            $this->addErrorMessage(__($e->getMessage()));
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $message = $e->getMessage();
            if (strpos($message, 'oauth') !== false) {
                $message = __('Please check Callback Url in social app configuration');
            } else {
                $message = __('An unspecified error occurred. Please try again later.');
            }

            $this->addErrorMessage($message);
        }
        $storage->clear();

        return $this->returnAction($params, $token ?? null);
    }

    private function getCurrentCustomer(?string $token): ?CustomerData
    {
        if ($token) {
            $id = $this->tokenFactory->create()->loadByToken($token)->getCustomerId();
            $customer = $this->customerRepository->getById($id);
        }

        return $customer ?? $this->session->getCustomer()->getDataModel();
    }

    private function getCurrentCustomerId(?string $token): ?int
    {
        if ($token) {
            $id = (int) $this->tokenFactory->create()->loadByToken($token)->getCustomerId();
        }

        return $id ?? (int) $this->session->getCustomerId();
    }

    private function prepareParams(Session $storage, array $params): array
    {
        $params = array_merge(
            $params ?: [],
            $storage->get(Login::AMSOCIAL_LOGIN_PARAMS) ?: []
        );
        if (isset($params['id_token'])) {
            $params['type'] = SocialData::APPLE;
            $params['isAjax'] = $this->configData->isPopupEnabled();
        }

        return $params;
    }

    private function getErrorMessage($type)
    {
        if ($type == SocialData::APPLE) {
            $message = __('Sorry. We cannot find your email. Please sign in, enter My Account ->
                        My Social Accounts tab and link your %1 Social Account.', ucfirst($type));
        } else {
            $message = __('We can`t get customer email from your social account.');
        }

        return $message;
    }

    private function authenticate(CustomerData $customer): bool
    {
        $customerId = $customer->getId();
        if ($this->authentication->isLocked($customerId)) {
            $this->addErrorMessage(__('The account is locked.'));
            return false;
        }

        $this->authentication->unlock($customerId);
        $this->eventManager->dispatch('customer_data_object_login', ['customer' => $customer]);

        return true;
    }

    private function returnAction(array $params, ?string $token = null): array
    {
        $isAjax = isset($params['isAjax']) ? (bool) $params['isAjax'] : false;
        $this->generateCheckoutRedirect($params);
        $this->updateLastCustomerId();

        $result['isAjax'] = $isAjax;
        if ($isAjax) {
            $result['responseData'] = $this->getResponseData($token);
        } else {
            $result['isSuccess'] = count($this->messages['error']) ? false : true;
            $result['messages'] = $result['isSuccess'] ? $this->messages['success'] : $this->messages['error'];
            $result['redirectTo'] = $this->redirectTo ? $this->redirectTo['url'] : null;
            if ($token) {
                $result[self::PWA_TOKEN] = $token;
            }
        }

        $this->clearMessages();

        return $result;
    }

    private function generateCheckoutRedirect(array $params): void
    {
        $redirectUrl = $params[self::REFERER] ?? '';
        $redirectUrlData = explode('/', $redirectUrl);
        $redirectUrlData = array_filter($redirectUrlData);
        if (end($redirectUrlData) === 'checkout') {
            $this->redirectTo = ['url' => $redirectUrl];
        }
    }

    private function updateLastCustomerId(): void
    {
        $lastCustomerId = $this->session->getLastCustomerId();
        if (isset($lastCustomerId)
            && $this->session->isLoggedIn()
            && $lastCustomerId != $this->session->getId()
        ) {
            $this->session->unsBeforeAuthUrl()
                ->setLastCustomerId($this->session->getId());
        }
    }

    private function getResponseData(?string $token = null): string
    {
        $resultType = count($this->messages['error']) ? 0 : 1;
        $messages = $resultType ? $this->messages['success'] : $this->messages['error'];
        $result = [
            'result'   => $resultType,
            'messages' => $messages
        ];
        if ($token) {
            $result['token'] = $token;
        }

        if ($this->redirectTo) {
            $result['redirect_data'] = $this->redirectTo;
        } else {
            $customUrl = ltrim($this->configData->getConfigValue('general/custom_url'), '\/');
            $result['redirect_data'] = [
                'url'      => $this->url->getBaseUrl() . $customUrl,
                'redirect' => $this->configData->getRedirectType()
            ];
        }

        return $this->jsonEncoder->encode($result);
    }

    /**
     * @param $userProfile
     * @param $type
     *
     * @return CustomerData|null
     */
    public function getCustomerProcess($userProfile, string $type): ?CustomerData
    {
        $user = $this->socialData->createUserData($userProfile, $type);

        return $this->getCustomerByUser($user, $type);
    }

    public function getCustomerByUser(array $user, string $type): ?CustomerData
    {
        try {
            $customer = $this->getCustomerByEmail(
                $user['email'],
                (int) $this->storeManager->getStore()->getWebsiteId()
            );
        } catch (NoSuchEntityException $exception) {
            $customer = null;
        }

        if (!$customer) {
            try {
                $this->validateGdprCheckboxes();
                $model = $this->socialRepository->createCustomer($user);
                $customer = $this->accountManagement->createAccount($model);

                $this->eventManager->dispatch(
                    'customer_register_success',
                    ['account_controller' => $this, 'customer' => $customer]
                );

                $confirmationStatus = $this->accountManagement->getConfirmationStatus($customer->getId());
                if ($confirmationStatus === AccountManagementInterface::ACCOUNT_CONFIRMATION_REQUIRED) {
                    $email = $this->customerUrl->getEmailConfirmationUrl($customer->getEmail());

                    $this->addErrorMessage(
                        __(
                            'You must confirm your account. Please check your email for the confirmation '
                            . 'link or <a href="%1">click here</a> for a new link.',
                            $email
                        )
                    );
                    return null;
                } else {
                    $this->addSuccessMessage(__('We have created new store account using your email address.'));
                }
            } catch (SecurityViolationException $e) {
                $this->addErrorMessage(__($e->getMessage()));
                return null;
            } catch (ValidatorException $e) {
                $this->createRedirectAfterError($this->url->getUrl('customer/account/create'));
                $this->addSuccessMessage(
                    __('The registration process is almost completed! Please kindly fill out a '
                        . 'few more fields to finish it.')
                );
                return null;
            } catch (\Exception $e) {
                $this->logger->critical($e);
                $this->addErrorMessage(__('An unspecified error occurred during creating new customer.'));
                return null;
            }
        }

        $this->socialRepository->createSocialAccount($user, $customer->getId(), $type);

        return $customer;
    }

    /**
     * @return bool
     * @throws ValidatorException
     */
    protected function validateGdprCheckboxes(): bool
    {
        $result = new \Magento\Framework\DataObject();
        $this->eventManager->dispatch(
            'amasty_gdpr_get_checkbox',
            [
                'scope' => GdprSocialLogin::GDPR_SOCIAL_LOGIN__FORM,
                'result' => $result
            ]
        );

        if ($checkboxes = $result->getData('checkboxes')) {
            foreach ($checkboxes as $checkbox) {
                if ($checkbox->getIsRequired()) {
                    throw new ValidatorException(
                        __('Please read and accept the privacy policy')
                    );
                }
            }
        }

        return true;
    }

    private function createRedirectAfterError(string $url)
    {
        $this->redirectTo = [
            'url' => $url,
            'redirect' => '1',
            'redirectWithError' => 1
        ];
    }

    private function refresh(CustomerData $customer): void
    {
        if ($customer && $customer->getId()) {
            $this->eventManager->dispatch('amsociallogin_customer_authenticated');
            $this->session->setCustomerDataAsLoggedIn($customer);
            $this->session->regenerateId();
            $this->checkoutSession->loadCustomerQuote();

            if ($this->cookieManager->getCookie('mage-cache-sessid')) {
                $metadata = $this->cookieMetadataFactory->createCookieMetadata();
                $metadata->setPath('/');
                $this->cookieManager->deleteCookie('mage-cache-sessid', $metadata);
            }
        }
    }

    private function isNeedToken(?Session $storage): bool
    {
        $params = $storage->get(Login::AMSOCIAL_LOGIN_PARAMS) ?: [];

        return isset($params[self::PWA_TOKEN_PARAM]);
    }

    public function getCustomerByEmail(string $email, int $websiteId): CustomerData
    {
        return $this->customerRepository->get($email, $websiteId);
    }

    private function addErrorMessage(Phrase $message): self
    {
        $this->messages['error'][] = $message;

        return $this;
    }

    private function addSuccessMessage(Phrase $message): self
    {
        $this->messages['success'][] = $message;

        return $this;
    }

    private function clearMessages(): self
    {
        $this->messages = [
            'success' => [],
            'error'   => []
        ];

        return $this;
    }
}
