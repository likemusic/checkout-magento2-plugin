<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * License GNU/GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace CheckoutCom\Magento2\Controller\Adminhtml\Payment;

use Magento\Customer\Api\Data\GroupInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Message\ManagerInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Backend\Model\Auth\Session as BackendSession;
use Magento\Framework\Controller\Result\JsonFactory;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Model\Service\OrderHandlerService;
use CheckoutCom\Magento2\Model\Service\PaymentTokenService;
use CheckoutCom\Magento2\Model\Ui\ConfigProvider;
use CheckoutCom\Magento2\Helper\Watchdog;
use CheckoutCom\Magento2\Helper\Tools;
use CheckoutCom\Magento2\Model\Service\StoreCardService;

class PlaceOrderAjax extends Action {

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
     * @var CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var StoreCardService
     */
    protected $storeCardService;

    /**
     * @var BackendSession
     */
    protected $backendAuthSession;
    
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

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
        Watchdog $watchdog,
        CookieManagerInterface $cookieManager,
        StoreCardService $storeCardService,
        BackendSession $backendAuthSession,
        JsonFactory $resultJsonFactory
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
        $this->cookieManager          = $cookieManager;
        $this->storeCardService       = $storeCardService;
        $this->backendAuthSession     = $backendAuthSession;
        $this->resultJsonFactory      = $resultJsonFactory;

        // Get the request parameters
        $this->params = $this->getRequest()->getParams();
    }

    /**
     * Handles the controller method.
     */
    public function execute() {
        // Prepare the response container
        $response = [];

        // Process the request
        if ($this->requestIsValid()) {
            // Get the charge response
            $response = $this->sendChargeRequest();
            //$response = ['success' => $this->params['cko-card-token']];

            // Process the response
            if ($this->tools->chargeIsSuccess($response)) {
                $response = ['success' => 'yes it works'];
            }
        }

        return $this->resultJsonFactory->create()->setData($response);
    }

    /**
     * Checks if the request is valid.
     */
    private function requestIsValid() {
        return $this->backendAuthSession->isLoggedIn() 
        && isset($this->params['cko-card-token'])
        && $this->getRequest()->isAjax();
    }

    /**
     * Send a token charge request.
     */
    private function sendChargeRequest() {
        // Prepare the charge data
        $data = [];
        $data['email'] = 'test@test.com'; // todo - get from edit form
        $data['value'] = 1000; // todo - get from edit form
        $data['currency'] = 'USD'; // todo - get from edit form

        // get the token charge response
        $response = $this->paymentTokenService->sendChargeRequest($this->params['cko-card-token'], false, false, $data);

        return $response;
    }
}