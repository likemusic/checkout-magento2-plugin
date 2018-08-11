<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Model\Service;

use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Customer\Model\Session as CustomerSession;
use CheckoutCom\Magento2\Model\Factory\VaultTokenFactory;
class StoreCardService {

    /**
     * @var VaultTokenFactory
     */
    protected $vaultTokenFactory;

    /**
     * @var PaymentTokenRepositoryInterface
     */
    protected $paymentTokenRepository;

    /**
     * @var PaymentTokenManagementInterface
     */
    protected $paymentTokenManagement;

    /**
     * @var string
     */
    protected $customerEmail;

    /**
     * @var int
     */
    protected $customerId;

    /**
     * @var string
     */
    protected $cardToken;

    /**
     * @var array
     */
    protected $cardData;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;
    
    /**
     * @var CustomerSession
     */
    protected $customerSession;
    
    /**
     * StoreCardService constructor.
     */
    public function __construct(
        VaultTokenFactory $vaultTokenFactory,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        PaymentTokenManagementInterface $paymentTokenManagement,
        ManagerInterface $messageManager,
        CustomerSession $customerSession
    ) {
        $this->vaultTokenFactory        = $vaultTokenFactory;
        $this->paymentTokenRepository   = $paymentTokenRepository;
        $this->paymentTokenManagement   = $paymentTokenManagement;
        $this->customerSession          = $customerSession;
        $this->messageManager           = $messageManager;
    }

    public function saveCard($response, $ckoCardToken) {
        $this->setResponse($response)
        ->setCardToken($ckoCardToken)
        ->setCustomerId()
        ->setCustomerEmail()
        ->setCardData()
        ->save();
    }

    /**
     * Sets the gateway response.
     *
     * @param object $response
     * @return StoreCardService
     */
    public function setResponse($response) {
        $this->authorizedResponse = $response;

        return $this;
    }

    /**
     * Sets the customer ID.
     *
     * @param int $customerId
     * @return StoreCardService
     */
    public function setCustomerId($id = null) {
        $this->customerId = (int) $id > 0 ? $id : $this->customerSession->getCustomer()->getId();

        return $this;
    }

    /**
     * Sets the customer email address.
     *
     * @param string $customerEmail
     * @return StoreCardService
     */
    public function setCustomerEmail($email = null) {
        $this->customerEmail = ($email) ? $email : $this->customerSession->getCustomer()->getEmail();

        return $this;
    }

    /**
     * Sets the card token.
     *
     * @param string $cardToken
     * @return StoreCardService
     */
    public function setCardToken($cardToken) {
        $this->cardToken = $cardToken;

        return $this;
    }


    /**
     * Sets the card data.
     *
     * @return StoreCardService
     */
    public function setCardData() {
        // Prepare the card data to save
        $cardData = $this->authorizedResponse->card;
        unset($cardData->customerId);
        unset($cardData->billingDetails);
        unset($cardData->bin);
        unset($cardData->fingerprint);
        unset($cardData->cvvCheck);
        unset($cardData->name);
        unset($cardData->avsCheck);

        // Assign the card data
        $this->cardData = $cardData;

        return $this;
    }

    /**
     * Saves the credit card in the repository.
     *
     * @throws LocalizedException
     * @throws ApiClientException
     * @throws ClientException
     * @throws \Exception
     */
    public function save() {
        // Create the payment token from response
        $paymentToken = $this->vaultTokenFactory->create((array)$this->cardData, $this->customerId);
        $foundPaymentToken  = $this->foundExistingPaymentToken($paymentToken);

        // Check if card exists
        if ($foundPaymentToken) {
            if ((int) $foundPaymentToken->getIsActive() == 1) {
                $this->messageManager->addNoticeMessage(__('This card has been stored already.'));
            }

            // Activate or reactivate the card
            $foundPaymentToken->setIsActive(true);
            $foundPaymentToken->setIsVisible(true);
            $this->paymentTokenRepository->save($foundPaymentToken);
        }

        // Otherwise save the card
        else {
            $gatewayToken = $this->authorizedResponse->card->id;
            $paymentToken->setGatewayToken($gatewayToken);
            $paymentToken->setIsVisible(true);
            $this->paymentTokenRepository->save($paymentToken);
        }

        $this->messageManager->addSuccessMessage(__('The card has been saved successfully.'));
    }

    /**
     * Returns the payment token instance if exists.
     *
     * @param PaymentTokenInterface $paymentToken
     * @return PaymentTokenInterface|null
     */
    private function foundExistingPaymentToken(PaymentTokenInterface $paymentToken) {
        return $this->paymentTokenManagement->getByPublicHash(
            $paymentToken->getPublicHash(),
            $paymentToken->getCustomerId()
        );
    }

}
