<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Controller\Payment;

use Magento\Customer\Api\Data\GroupInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Message\ManagerInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Model\Service\OrderHandlerService;
use CheckoutCom\Magento2\Model\Service\PaymentTokenService;
use CheckoutCom\Magento2\Model\Ui\ConfigProvider;
use CheckoutCom\Magento2\Helper\Watchdog;
use CheckoutCom\Magento2\Helper\Tools;

class PlaceOrder extends Action {

    /**
     * @var PaymentTokenService
     */
    protected $paymentTokenService;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var OrderHandlerService
     */
    protected $orderHandlerService;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Tools
     */
    protected $tools;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var PaymentTokenManagementInterface
     */
    protected $paymentTokenManagement;

    /**
     * @var Watchdog
     */
    protected $watchdog;

    /**
     * @var Array
     */
    protected $params;

    /**
     * PlaceOrder constructor.
     */
    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        Config $config,
        Tools $tools,
        ManagerInterface $messageManager,
        PaymentTokenManagementInterface $paymentTokenManagement,
        OrderHandlerService $orderHandlerService,
        PaymentTokenService $paymentTokenService,
        Watchdog $watchdog
    ) {
        parent::__construct($context);

        $this->checkoutSession        = $checkoutSession;
        $this->customerSession        = $customerSession;
        $this->config                 = $config;
        $this->tools                  = $tools;
        $this->messageManager         = $messageManager;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->orderHandlerService    = $orderHandlerService;
        $this->paymentTokenService    = $paymentTokenService;
        $this->watchdog               = $watchdog; 

        // Get the request parameters
        $this->params = $this->getRequest()->getParams();
    }

    /**
     * Handles the controller method.
     */
    public function execute() {
        if ($this->requestIsValid()) {
            // Get the charge response
            $response = json_decode($this->sendChargeRequest());

            // Logging
            $this->watchdog->bark($response);

            // Check for 3DS redirection
            if ($this->config->isVerify3DSecure() && isset($response->redirectUrl)) {
                $redirectUrl = filter_var($response->redirectUrl, FILTER_VALIDATE_URL);
                return $this->resultRedirectFactory->create()->setUrl($redirectUrl);
            }

            // Process the response
            else if ($this->tools->chargeIsSuccess($response)) {
                // Place the order
                $orderId = $this->orderHandlerService->placeOrder($response);

                // If the order has been placed successfully
                if ($orderId > 0) {
                    return $this->resultRedirectFactory->create()->setPath('checkout/onepage/success');
                }
                else {
                    $this->messageManager->addErrorMessage(__('The order could not be created. Please contact the site administrator or try again.'));
                }
            }
            else {
                $this->messageManager->addErrorMessage(__('The transaction could not be processed.'));
            }
        }
        else {
            $this->messageManager->addErrorMessage(__('The request is invalid.'));
        }

        return $this->resultRedirectFactory->create()->setPath('checkout/cart');
    }

    /**
     * Checks if the request is valid.
     */
    private function requestIsValid() {
        // Default output result
        $result = false;

        if (isset($this->params['public-hash'])) {
            $this->params['cko-card-token'] = $this->getSavedCardToken();

            return true;
        }
        else if ($this->config->isHostedIntegration()) {
            $result = isset($this->params['cko-public-key'])
            && isset($this->params['cko-card-token'])
            && isset($this->params['cko-payment-token'])
            && isset($this->params['cko-context-id'])
            && $this->tools->publicKeyIsValid($this->params['cko-public-key']);
        }
        else if ($this->config->isEmbeddedIntegration()) {
            $result = isset($this->params['cko-card-token']);
        }

        return $result;
    }

    /**
     * Get a saved card.
     */
    private function getSavedCardToken() {
        // Get the customer id (currently logged in user)
        $customerId = $this->customerSession->getCustomer()->getId();

        // Get the cards list
        $cardList = $this->paymentTokenManagement->getListByCustomerId($customerId);

        // Loop through the cards
        foreach ($cardList as $card) {
            if ($card->getPublicHash() == $this->params['public-hash'] 
                && (int) $card->getIsActive() == 1
                && (int) $card->getIsVisible() == 1
            ) {

                return $card->getGatewayToken();
            }
        }

        return false;
    }

    /**
     * Send a token charge request.
     */
    private function sendChargeRequest() {
        // Get the quote
        $quote = $this->checkoutSession->getQuote();

        // Get the track id
        $trackId = $quote->reserveOrderId()->save()->getReservedOrderId();

        // get the token charge response
        $response = $this->paymentTokenService->sendChargeRequest($this->params['cko-card-token'], $quote, $trackId);

        return $response;
    }
}