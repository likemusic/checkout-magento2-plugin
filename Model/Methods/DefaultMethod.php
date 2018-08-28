<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * MIT License
 */

namespace CheckoutCom\Magento2\Model\Methods;

use Magento\Framework\DataObject;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Sales\Model\Order\Payment\Transaction;
use CheckoutCom\Magento2\Model\Ui\ConfigProvider;

class DefaultMethod extends AbstractMethod {

    protected $_code = ConfigProvider::CODE;
    protected $_isInitializeNeeded = true;
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCancel = true;
    protected $_canCapturePartial = true;
    protected $_canVoid = true;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canAuthorizeVault = true;
    protected $_canCaptureVault = true;
    protected $backendAuthSession;
    protected $transactionService;
    protected $hubService;
    protected $cart;
    protected $urlBuilder;
    protected $_objectManager;
    protected $invoiceSender;
    protected $transactionFactory;
    protected $customerSession;
    protected $checkoutSession;
    protected $checkoutData;
    protected $quoteRepository;
    protected $quoteManagement;
    protected $orderSender;
    protected $sessionQuote;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        \CheckoutCom\Magento2\Model\Service\TransactionHandlerService $transactionService,
        \CheckoutCom\Magento2\Model\Service\HubHandlerService $hubService,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Checkout\Helper\Data $checkoutData,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Api\CartManagementInterface $quoteManagement,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->urlBuilder = $urlBuilder;
        $this->backendAuthSession = $backendAuthSession;
        $this->cart = $cart;
        $this->_objectManager = $objectManager;
        $this->invoiceSender = $invoiceSender;
        $this->transactionFactory = $transactionFactory;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->checkoutData = $checkoutData;
        $this->quoteRepository = $quoteRepository;
        $this->quoteManagement = $quoteManagement;
        $this->orderSender = $orderSender;
        $this->sessionQuote = $sessionQuote;
        $this->transactionService = $transactionService;
        $this->hubService = $hubService;
    }

    /**
     * Check whether method is available
     *
     * @param \Magento\Quote\Api\Data\CartInterface|\Magento\Quote\Model\Quote|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null) {
        return parent::isAvailable($quote) && null !== $quote;
    }

    /**
     * Check whether method is enabled in config
     *
     * @param \Magento\Quote\Model\Quote|null $quote
     * @return bool
     */
    public function isAvailableInConfig($quote = null) {
        return parent::isAvailable($quote);
    }

    /**
     * Capture a transaction
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        // Initial check
        if (!$this->canCapture() && $this->backendAuthSession->isLoggedIn()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The capture action is not available for this order.'));
        }

        // Get the order
        $order = $payment->getOrder();

        // Get the transactions
        $transactions = $this->transactionService->getTransactions($order);

        // Process the transactions to capture
        foreach ($transactions as $transaction) {
            if ($transaction->getTxnType() == Transaction::TYPE_AUTH) {
                // Perform the remote action
                $success = $this->hubService->captureRemoteTransaction(
                    $transaction,
                    $order->getGrandTotal(),
                    $payment
                );
                
                // Process the result
                if (!$success) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('The transaction could not be captured.')); 
                }
            }
        }

        return $this;
    }

    /**
     * Void a transaction
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment) {
        // Initial check
        if (!$this->canVoid() || !$this->backendAuthSession->isLoggedIn()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The void action is not available for this order.'));
        }

        // Get the order
        $order = $payment->getOrder();

        // Get the transactions
        $transactions = $this->transactionService->getTransactions($order);

        // Process the transactions to void
        foreach ($transactions as $transaction) {
            if ($transaction->getTxnType() == Transaction::TYPE_AUTH) {
                // Perform the remote action
                $success = $this->hubService->voidRemoteTransaction(
                    $transaction,
                    $order->getGrandTotal(),
                    $payment
                );
                
                // Process the result
                if (!$success) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('The transaction could not be voided.')); 
                }
            }
        }

        return $this;
    }

    /**
     * Refund a transaction
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount) {
        // Initial check
        if (!$this->canRefund() || !$this->backendAuthSession->isLoggedIn()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The refund action is not available for this order.'));
        }

        // Get the order
        $order = $payment->getOrder();

        // Get the transactions
        $transactions = $this->transactionService->getTransactions($order);

        // Process the transactions to refund
        foreach ($transactions as $transaction) {
            if ($transaction->getTxnType() == Transaction::TYPE_CAPTURE) {
                // Perform the remote action
                $success = $this->hubService->refundRemoteTransaction(
                    $transaction,
                    $amount,
                    $payment
                );       

                // Process the result
                if (!$success) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('The transaction could not be refunded.')); 
                }
            }
        }

        return $this;
    }
}