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

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Model\Service\PaymentTokenService;
use CheckoutCom\Magento2\Model\Service\OrderHandlerService;
use CheckoutCom\Magento2\Helper\Tools;
use CheckoutCom\Magento2\Helper\Watchdog;
use CheckoutCom\Magento2\Model\Service\StoreCardService;

class Verify extends Action {

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var PaymentTokenService
     */
    protected $paymentTokenService;

    /**
     * @var OrderHandlerService
     */
    protected $orderHandlerService;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var Tools
     */
    protected $tools;

    /**
     * @var Watchdog
     */
    protected $watchdog;

    /**
     * @var StoreCardService
     */
    protected $storeCardService;

    /**
     * @var CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var Array
     */
    protected $params = [];

    /**
     * Verify constructor.
     */
    public function __construct(
        Context $context, 
        Config $config,
        PaymentTokenService $paymentTokenService,
        OrderHandlerService $orderHandlerService,
        ManagerInterface $messageManager,
        Tools $tools,
        Watchdog $watchdog,
        StoreCardService $storeCardService,
        CookieManagerInterface $cookieManager
    ) 
    {
        parent::__construct($context);

        $this->config                   = $config;
        $this->paymentTokenService      = $paymentTokenService;
        $this->orderHandlerService      = $orderHandlerService;
        $this->messageManager           = $messageManager;
        $this->tools                    = $tools;
        $this->watchdog                 = $watchdog; 
        $this->storeCardService         = $storeCardService;
        $this->cookieManager            = $cookieManager;

        // Get the request parameters
        $this->params = $this->getRequest()->getParams();        
    }

    /**
     * Handles the controller method.
     */
    public function execute() {
        if ($this->requestIsValid()) {
            // Verify the payment token
            $response = json_decode($this->paymentTokenService->verifyToken($this->params['cko-payment-token']));

            // Logging
            $this->watchdog->bark($response);

            // Process the response
            if ($this->tools->chargeIsSuccess($response)) {
                // Handle the store card from account case
                if (isset($response->udf5) && $response->udf5 == 'isZeroDollarAuthorization') {
                    // Store the card
                    $this->storeCardService->saveCard($response, $response->card->id);

                    // Redirect to the cards list
                    return $this->resultRedirectFactory->create()->setPath('vault/cards/listaction');
                }
                // Place the order normally
                else {
                    // Handle the store card from payment method case
                    if ((bool) $this->cookieManager->getCookie('ckoSaveUserCard') === true) {
                        // Store the card
                        $this->storeCardService->saveCard($response, $response->card->id);

                        // Delete the cookie
                        $this->cookieManager->deleteCookie('ckoSaveUserCard');
                    }

                    $orderId = $this->orderHandlerService->placeOrderAfterAuth($response);
                    if ($orderId > 0) {
                        return $this->resultRedirectFactory->create()->setPath('checkout/onepage/success');
                    } else {
                        $this->messageManager->addErrorMessage(__('The order could not be created. Please contact the site administrator or try again.'));
                    }
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
        return isset($this->params['cko-payment-token']);
    }    
}