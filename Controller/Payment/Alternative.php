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
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Checkout\Model\Cart;
use Magento\Framework\Controller\Result\JsonFactory;
use CheckoutCom\Magento2\Gateway\Http\TransferFactory;
use CheckoutCom\Magento2\Model\Ui\ConfigProvider;
use CheckoutCom\Magento2\Helper\Watchdog;
use CheckoutCom\Magento2\Gateway\Config\Config as GatewayConfig;
use CheckoutCom\Magento2\Model\Service\PaymentTokenService;

class Alternative extends AbstractAction {

    /**
     * @var GatewayConfig
     */
    protected $gatewayConfig;

    /**
     * @var TransferFactory
     */
    protected $transferFactory;

    /**
     * @var PaymentTokenService
     */
    protected $paymentTokenService;

    /**
     * @var Cart
     */
    protected $cart;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Watchdog
     */
    protected $watchdog;

    /**
     * OrderService constructor.
     */
    public function __construct(
        Context $context,
        TransferFactory $transferFactory,
        GatewayConfig $gatewayConfig,
        PaymentTokenService $paymentTokenService,
        Cart $cart,
        JsonFactory $resultJsonFactory,
        Watchdog $watchdog
    ) {
         parent::__construct($context, $gatewayConfig);

        $this->transferFactory       = $transferFactory;
        $this->gatewayConfig         = $gatewayConfig;
        $this->paymentTokenService   = $paymentTokenService;
        $this->cart                  = $cart;
        $this->resultJsonFactory     = $resultJsonFactory;
        $this->watchdog              = $watchdog;
    }

    /**
     * Runs the service.
     */
    public function execute() {

        // Prepare the charge parameters
        $lpChargeUrl = 'charges/localpayment';
        $lppId    = $this->getRequest()->getParam('lppId');
        $issuerId = $this->getRequest()->getParam('issuerId');
        $userData = (($issuerId) && !empty($issuerId) && $issuerId !== 'null') ? ['issuerId' => $issuerId] : [];

        // Prepare the charge payload
        $payload = [
            'email' => $this->cart->getQuote()->getCustomerEmail(),
            'localPayment' => (object)[
                'lppId' => $lppId,
                'userData' => (object) $userData
            ],
            'paymentToken' => $this->paymentTokenService->getToken()
        ];

        try {
            // Send the request
            $transfer = $this->transferFactory->create($payload);
            $response = $this->getHttpClient($lpChargeUrl, $transfer)->request();

            // Check the response
            $result   = json_decode($response->getBody(), true);
            if (isset($result['localPayment']['paymentUrl'])) {
                return $this->resultJsonFactory->create()->setData([
                    'redirectUrl' => $result['localPayment']['paymentUrl']
                ]);
            }
        }
        catch (Zend_Http_Client_Exception $e) {
            throw new ClientException(__($e->getMessage()));
        }

        return $this->resultJsonFactory->create()->setData([
            'redirectUrl' => null
        ]);
    }

    /**
     * Returns prepared HTTP client.
     *
     * @param string $endpoint
     * @param TransferInterface $transfer
     * @return ZendClient
     * @throws \Exception
     */
    private function getHttpClient($endpoint, TransferInterface $transfer) {
        $client = new ZendClient($this->gatewayConfig->getApiUrl() . $endpoint);
        $client->setMethod('POST');
        $client->setRawData( json_encode( $transfer->getBody()) ) ;
        $client->setHeaders($transfer->getHeaders());
        
        return $client;
    }

}
