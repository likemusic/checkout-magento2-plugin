<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Controller\Account;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Message\ManagerInterface;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Model\Service\PaymentTokenService;
use CheckoutCom\Magento2\Helper\Watchdog;
use CheckoutCom\Magento2\Helper\Tools;
use CheckoutCom\Magento2\Model\Service\StoreCardService;

class SaveCard extends Action {

    /**
     * @var Array
     */
    protected $params;

    /**
     * @var PaymentTokenService
     */
    protected $paymentTokenService;

    /**
     * @var Watchdog
     */
    protected $watchdog;

    /**
     * @var Tools
     */
    protected $tools;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var StoreCardService
     */
    protected $storeCardService;

    /**
     * SaveCard constructor.
     */
    public function __construct(
        Context $context,
        PaymentTokenService $paymentTokenService,
        Watchdog $watchdog,
        Tools $tools,
        Config $config,
        ManagerInterface $messageManager,
        StoreCardService $storeCardService
    ) {
        parent::__construct($context);

        $this->paymentTokenService    = $paymentTokenService;
        $this->watchdog               = $watchdog; 
        $this->tools                  = $tools;
        $this->config                 = $config;
        $this->messageManager         = $messageManager;
        $this->storeCardService       = $storeCardService;

        // Get the request parameters
        $this->params = $this->getRequest()->getParams();
    }

    /**
     * Handles the controller method.
     */
    public function execute() {
        // Force login
        $this->tools->checkLoggedIn();

        // Process valid requests
        if ($this->requestIsValid()) {
            // Send the charge request and get the response
            $response = $this->sendChargeRequest();

            // Check for 3DS redirection
            if ($this->config->isVerify3DSecure() && isset($response->redirectUrl)) {
                $redirectUrl = filter_var($response->redirectUrl, FILTER_VALIDATE_URL);
                return $this->resultRedirectFactory->create()->setUrl($redirectUrl);
            }
            
            // Process the response
            if ($this->tools->chargeIsSuccess($response)) {
                // Store the card
                $this->storeCardService->saveCard($response, $this->params['cko-card-token']);

                // Redirect to the card list
                return $this->resultRedirectFactory->create()->setPath('vault/cards/listaction');
            }
            else {
                $this->messageManager->addErrorMessage(__('The card could not be authorized.'));
            }
        }
        else {
            $this->messageManager->addErrorMessage(__('The request is invalid.'));
        }

        return $this->resultRedirectFactory->create()->setPath($this->tools->modmeta['tag'] . '/account/addcard');
    } 

    /**
     * Checks if the request is valid.
     */
    private function requestIsValid() {
        return isset($this->params['cko-card-token']);
    }

    /**
     * Send a token charge request.
     */
    private function sendChargeRequest() {
        // get the token charge response
        $response = $this->paymentTokenService->sendChargeRequest($this->params['cko-card-token']);

        return $response;
    }
}