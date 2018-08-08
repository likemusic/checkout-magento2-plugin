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

use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Model\Service\PaymentTokenService;
use CheckoutCom\Magento2\Helper\Tools;

class Fail extends Action {

    /**
     * @var PaymentTokenService
     */
    protected $paymentTokenService;

    /**
     * @var Tools
     */
    protected $tools;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    public function __construct(
        Context $context,
        PaymentTokenService $paymentTokenService,
        Tools $tools,
        ManagerInterface $messageManager
        ) {
        parent::__construct($context);

        $this->paymentTokenService = $paymentTokenService;
        $this->tools               = $tools;
        $this->messageManager      = $messageManager;

        // Get the request parameters
        $this->params = $this->getRequest()->getParams();
    }

    /**
     * Handles the controller method.
     */
    public function execute() {
        if ($this->requestIsValid()) {
            // Verify the token and get the payment response
            $response = json_decode($this->paymentTokenService->verifyToken($this->params['cko-payment-token']));

            // Test the result
            if (!$this->tools->chargeIsSuccess($response)) {
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