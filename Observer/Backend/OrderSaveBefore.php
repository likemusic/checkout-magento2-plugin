<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * MIT License
 */

namespace CheckoutCom\Magento2\Observer\Backend;

use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Event\ObserverInterface; 
use Magento\Framework\Event\Observer;
use Magento\Framework\App\Request\Http;
use CheckoutCom\Magento2\Model\Service\PaymentTokenService;
use CheckoutCom\Magento2\Helper\Tools;

class OrderSaveBefore implements ObserverInterface { 
 
    /**
     * @var Session
     */
    protected $backendAuthSession;

    /**
     * @var Http
     */
    protected $request;

    /**
     * @var PaymentTokenService
     */
    protected $paymentTokenService;

    /**
     * @var Tools
     */
    protected $tools;

    /**
     * OrderSaveBefore constructor.
     */
    public function __construct(
        Session $backendAuthSession,
        Http $request,
        PaymentTokenService $paymentTokenService,
        Tools $tools
    ) {
        $this->backendAuthSession    = $backendAuthSession;
        $this->request               = $request;
        $this->paymentTokenService   = $paymentTokenService;
        $this->tools                 = $tools;

        // Get the request parameters
        $this->params = $this->request->getParams();
    }
 
    /**
     * Observer execute function.
     */
    public function execute(Observer $observer) { 
        if ($this->backendAuthSession->isLoggedIn()) {
            // Get the order
            $order = $observer->getEvent()->getOrder();

            // Send the charge
            $response = json_decode($this->sendChargeRequest());

            // Process the response
            if ($this->tools->chargeIsSuccess($response)) {
                // Todo - Store the response in session for order processing
            }
            else {
                throw new \Magento\Framework\Exception\LocalizedException(__('The transaction could not be processed.'));
            }
        }
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