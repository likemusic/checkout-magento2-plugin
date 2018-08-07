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
use Magento\Framework\Exception\LocalizedException;
use CheckoutCom\Magento2\Model\Ui\ConfigProvider;
use CheckoutCom\Magento2\Helper\Tools;

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
     * TransactionHandlerService constructor.
     * @param BuilderInterface $transactionBuilder
     * @param Tools $tools
     */
    public function __construct(BuilderInterface $transactionBuilder, Tools $tools) {
        $this->transactionBuilder = $transactionBuilder;
        $this->tools     = $tools;
    }

    public function createTransaction($order, $paymentData, $mode = null) {
        // Prepare the transaction mode
        $transactionMode = ($mode == 'authorization' || !$mode) ? Transaction::TYPE_AUTH : Transaction::TYPE_CAPTURE;

        // Prepare the payment object
        $payment = $order->getPayment();
        $payment->setMethod($this->tools->modmeta['tag']);
        $payment->setLastTransId($paymentData['transactionReference']);
        $payment->setIsTransactionClosed(0);
        $payment->setParentTransactionId(null);
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
}
