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

    public function createTransaction($order, $paymentData, $mode = null) {
        // Prepare the transaction mode
        $transactionMode = ($mode == 'authorization' || !$mode) ? Transaction::TYPE_AUTH : Transaction::TYPE_CAPTURE;

        // Prepare the payment object
        $payment = $order->getPayment();
        $payment->setMethod($this->tools->modmeta['tag']);

        // Handle the transaction states
        if ($mode == 'capture') {
            $payment->setIsTransactionClosed(1);
            $this->closeAuthorizedTransactions($order);
        }
        else {
            $payment->setIsTransactionClosed(0);
        }
        
        // Set the transaction ids
        $payment->setLastTransId($paymentData['transactionReference']);
        $payment->setParentTransactionId(null);
        $payment->setTransactionId($paymentData['transactionReference']);

        // Formatted price
        $formatedPrice = $order->getBaseCurrency()->formatTxt($order->getGrandTotal());

        // Prepare transaction
        $transaction = $this->transactionBuilder->setPayment($payment)
        ->setOrder($order)
        ->setTransactionId($paymentData['transactionReference'])
        ->setAdditionalInformation([Transaction::RAW_DETAILS => (array) $paymentData])
        ->setFailSafe(true)
        ->build($transactionMode);

        // Save payment, transaction and order
        $transaction->save();
        $payment->save();

        return $order;
    }

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
