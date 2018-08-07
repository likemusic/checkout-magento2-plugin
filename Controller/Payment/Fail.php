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
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Watchdog;
use CheckoutCom\Magento2\Model\Service\PaymentTokenService;

class Fail extends Action {

    public function __construct(Context $context) {
        parent::__construct($context);
    }

    /**
     * Handles the controller method.
     */
    public function execute() {
        if ($this->requestIsValid()) {
            $response = json_decode($this->paymentTokenService->verifyToken($this->params['cko-payment-token']));

            echo "<pre>";
            var_dump($response);
            echo "</pre>";
            exit();           
        }
        else {
            $this->messageManager->addErrorMessage(__('The request is invalid.'));
        }
    }

    /**
     * Checks if the request is valid.
     */
    private function requestIsValid() {
        return isset($this->params['cko-card-token']);
    }
}