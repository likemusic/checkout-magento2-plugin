<?php
/**
 * Checkout.com Magento 2 Payment module (https://www.checkout.com)
 *
 * Copyright (c) 2017 Checkout.com (https://www.checkout.com)
 * Author: David Fiaty | integration@checkout.com
 *
 * MIT License
 */

namespace CheckoutCom\Magento2\Model\Service;

use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Sales\Model\Order\Payment\Transaction\Repository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use CheckoutCom\Magento2\Model\Ui\ConfigProvider;
use CheckoutCom\Magento2\Helper\Tools;
use CheckoutCom\Magento2\Gateway\Config\Config;

class TransactionHandlerService {

    const THREE_D_SECURED = 'three_d_secure';

    /**
     * @var BuilderInterface
     */
    protected $transactionBuilder;

    /**
     * @var Tools
     */
    protected $tools;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Repository
     */
    private $transactionRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * TransactionHandlerService constructor.
     */
    public function __construct(
        BuilderInterface $transactionBuilder,
        Tools $tools,
        Config $config,
        Repository $transactionRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder
    ) {
        $this->transactionBuilder    = $transactionBuilder;
        $this->tools                 = $tools;
        $this->config                = $config;
        $this->transactionRepository = $transactionRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder         = $filterBuilder;
    }

    /**
     * Create a transaction for an order.
     */
    public function createTransaction($order, $paymentData, $mode = null, $parentTransactionId = null) {
        // Prepare the transaction mode
        $transactionMode = !$mode ? Transaction::TYPE_AUTH : $mode;

        // Prepare the payment object
        $payment = $order->getPayment();
        $payment->setMethod($this->tools->modmeta['tag']);

        // Handle the transaction states
        if ($transactionMode == Transaction::TYPE_CAPTURE || $transactionMode == Transaction::TYPE_REFUND) {
            $payment->setIsTransactionClosed(1);
            $this->closeAuthorizedTransactions($order);
        }
        else {
            $payment->setIsTransactionClosed(0);
        }
        
        // Set the transaction ids
        $payment->setLastTransId($paymentData['transactionReference']);
        $payment->setParentTransactionId($parentTransactionId);
        $payment->setTransactionId($paymentData['transactionReference']);

        // Formatted price
        $formatedPrice = $order->getBaseCurrency()->formatTxt($order->getGrandTotal());

        // Prepare transaction
        $transaction = $this->transactionBuilder->setPayment($payment)
        ->setOrder($order)
        ->setTransactionId($paymentData['transactionReference'])
        ->setFailSafe(true)
        ->build($transactionMode);

        // Save payment, transaction and order
        $transaction->save();
        $payment->save();

        return $order;
    }

    /**
     * Close all authorized transactions for an order.
     */
    public function closeAuthorizedTransactions($order) {
        // Get the list of transactions
        $transactions = $this->getTransactions($order);

        // Update the auth transaction status if exists
        if (count($transactions) > 0) {
            foreach ($transactions as $transaction) {
                if ($transaction->getTxnType() == 'authorization' && (int) $transaction->getIsClosed() == 0) {
                    $transaction->close();
                }
            }
        }
    }

    /**
     * Get all authorized transactions for an order.
     */
    public function getAuthorizedTransactions($order) {
        // Get the list of transactions
        $transactions = $this->getTransactions($order);

        // Find authorized transactions
        if (count($transactions) > 0) {
            $result = [];
            foreach ($transactions as $transaction) {
                if ($transaction->getTxnType() == 'authorization') {
                    $result[] = $transaction;
                }
            }

            return $result;
        }

        return [];
    }

    /**
     * Get all captured transactions for an order.
     */
    public function getCapturedTransactions($order) {
        // Get the list of transactions
        $transactions = $this->getTransactions($order);

        // Find captured transactions
        if (count($transactions) > 0) {
            $result = [];
            foreach ($transactions as $transaction) {
                if ($transaction->getTxnType() == 'capture') {
                    $result[] = $transaction;
                }
            }

            return $result;
        }

        return [];
    }

    /**
     * Get all transactions for an order.
     */
    public function getTransactions($order) {
        // Payment filter
        $filters[] = $this->filterBuilder->setField('payment_id')
        ->setValue($order->getPayment()->getId())
        ->create();

        // Order filter
        $filters[] = $this->filterBuilder->setField('order_id')
        ->setValue($order->getId())
        ->create();

        // Build the search criteria
        $searchCriteria = $this->searchCriteriaBuilder->addFilters($filters)
        ->create();
        
        return $this->transactionRepository->getList($searchCriteria)->getItems();
    }
}
