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

use DomainException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\OrderFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory;
use CheckoutCom\Magento2\Model\Adapter\CallbackEventAdapter;
use CheckoutCom\Magento2\Model\Adapter\ChargeAmountAdapter;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Model\Service\StoreCardService;
use CheckoutCom\Magento2\Model\Service\OrderHandlerService;
use CheckoutCom\Magento2\Model\Service\TransactionHandlerService;
use CheckoutCom\Magento2\Model\Service\InvoiceHandlerService;

class WebhookCallbackService {

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var Config
     */
    protected $Config;

    /**
     * @var StoreCardService
     */
    protected $storeCardService;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var OrderSender
     */
    protected $orderSender;

    /**
     * @var OrderHandlerService
     */
    protected $orderService;

    /**
     * @var TransactionHandlerService
     */
    protected $transactionService;

    /**
     * @var InvoiceHandlerService
     */
    protected $invoiceService;

    /**
     * @var CollectionFactory
     */
    protected $quoteCollectionFactory;

    /**
     * CallbackService constructor.
     */
    public function __construct(
        OrderFactory $orderFactory,
        OrderRepositoryInterface $orderRepository,
        Config $config,
        StoreCardService $storeCardService,
        CustomerFactory $customerFactory,
        StoreManagerInterface $storeManager,
        OrderSender $orderSender,
        OrderHandlerService $orderService,
        TransactionHandlerService $transactionService,    
        InvoiceHandlerService $invoiceService,
        CollectionFactory $quoteCollectionFactory
    ) {
        $this->orderFactory            = $orderFactory;
        $this->orderRepository         = $orderRepository;
        $this->config                  = $config;
        $this->storeCardService        = $storeCardService;
        $this->customerFactory         = $customerFactory;
        $this->storeManager            = $storeManager;
        $this->orderSender             = $orderSender;
        $this->orderService            = $orderService;
        $this->transactionService      = $transactionService;
        $this->invoiceService          = $invoiceService;
        $this->quoteCollectionFactory  = $quoteCollectionFactory;
    }

    /**
     * Runs the service.
     *
     * @throws DomainException
     * @throws LocalizedException
     */
    public function run($response) {
        // Set the gateway response
        $this->gatewayResponse = $response;

        // Extract the response info
        $eventName    = $this->getEventName();
        $amount         = $this->getAmount();

        // Get the order and payment information
        $order          = $this->getAssociatedOrder();
        $payment        = $order->getPayment();

        // Get override comments setting from config
        $overrideComments = $this->config->overrideOrderComments();

        // Process the payment
        if ($payment instanceof Payment) {
            // Test the command name
            if ($eventName == 'charge.refunded' || $eventName == 'charge.voided') {
                //$this->orderService->cancelTransactionFromRemote($order);
            }
            
            // Perform authorize complementary actions
            else if ($eventName == 'charge.succeeded') {
                if (count($this->transactionService->getAuthorizedTransactions($order)) == 0) {
                    // Update order status
                    $order->setStatus($this->config->getOrderStatusAuthorized());

                    // Send the email
                    $this->orderSender->send($order);
                    $order->setEmailSent(1);

                    // Comments override
                    if ($overrideComments) {
                        // Delete comments history
                        foreach ($order->getAllStatusHistory() as $orderComment) {
                            $orderComment->delete();
                        } 
                    }

                    // Add authorization comment
                    $order = $this->addAuthorizationComment($order);

                    // Create the authorization transaction
                    $order = $this->transactionService->createTransaction(
                        $order,
                        array('transactionReference' => $this->gatewayResponse['message']['id']),
                        'authorization'
                    );
                }
            }

            // Perform capture complementary actions
            else if ($eventName == 'charge.captured') {
                if (count($this->transactionService->getCapturedTransactions($order)) == 0) {
                    // Update order status
                    $order->setStatus($this->config->getOrderStatusCaptured());

                    // Generate invoice if needed
                    if ($this->config->getAutoGenerateInvoice() === true) {
                        // Prepare the amount
                        $amount = ChargeAmountAdapter::getStoreAmountOfCurrency(
                            $this->gatewayResponse['message']['value'],
                            $this->gatewayResponse['message']['currency']
                        );

                        // Create the invoice
                        $invoice = $this->invoiceService->processInvoice($order, $amount);
                    }

                    // Add capture comment
                    $order = $this->addCaptureComment($order);

                    // Create the capture transaction
                    $order = $this->transactionService->createTransaction(
                        $order,
                        array('transactionReference' => $this->gatewayResponse['message']['id']),
                        'capture'
                    );
                } 
            }

            // Save the order
            $this->orderRepository->save($order);
        }
    }

    private function addAuthorizationComment($order) {
        // Create new comment
        $newComment  = '';
        $newComment .= __('Authorized amount of') . ' ';
        $newComment .= ChargeAmountAdapter::getStoreAmountOfCurrency(
            $this->gatewayResponse['message']['value'], 
            $this->gatewayResponse['message']['currency']
        );
        $newComment .= ' ' . $this->gatewayResponse['message']['currency'];
        $newComment .= ' ' . __('Transaction ID') . ':' . ' ';
        $newComment .= $this->gatewayResponse['message']['id'];

        // Add the new comment
        $order->addStatusToHistory($order->getStatus(), $newComment, $notify = true);

        return $order;
    }

    private function addCaptureComment($order) {
        // Create new comment
        $newComment  = '';
        $newComment .= __('Captured amount of') . ' ';
        $newComment .= ChargeAmountAdapter::getStoreAmountOfCurrency(
            $this->gatewayResponse['message']['value'], 
            $this->gatewayResponse['message']['currency']
        );
        $newComment .= ' ' . $this->gatewayResponse['message']['currency'];
        $newComment .= ' ' . __('Transaction ID') . ':' . ' ';
        $newComment .= $this->gatewayResponse['message']['id'];

        // Add the new comment
        $order->addStatusToHistory($order->getStatus(), $newComment, $notify = true);

        return $order;
    }

    /**
     * Returns the order instance.
     *
     * @return \Magento\Sales\Model\Order
     * @throws DomainException
     */
    private function getAssociatedOrder() {
        // Prepare variables
        $trackId    = $this->gatewayResponse['message']['trackId'];
        $order      = $this->orderFactory->create()->loadByIncrementId($trackId);

        // If the order doesn't exist yet, create from quote
        /*if ($order->isEmpty()) {
        
            // Get the quote from track id
            $quoteCollection = $this->quoteCollectionFactory->create()
            ->addFieldToFilter('reserved_order_id', $trackId);
            
            // Create the new order from quote
            if (count($quoteCollection) == 1) {
                $orderId = $this->orderService->createNewOrder($quoteCollection[0]);
                $order   = $this->orderFactory->create()->loadByAttribute('order_id', $orderId);
            }
        }*/
        
        return !$order->isEmpty() ? $order : null;
    }

    /**
     * Returns the command name.
     *
     * @return null|string
     */
    private function getEventName() {
        return $this->gatewayResponse['eventType'];
    }

    /**
     * Returns the amount for the store.
     *
     * @return float
     */
    private function getAmount() {
        return ChargeAmountAdapter::getStoreAmountOfCurrency(
            $this->gatewayResponse['message']['value'],
            $this->gatewayResponse['message']['currency']
        );
    }
}