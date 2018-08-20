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

use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Framework\DB\Transaction;
use CheckoutCom\Magento2\Gateway\Config\Config;

class InvoiceHandlerService {

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var InvoiceService
     */
    protected $invoiceService;

    /**
     * @var InvoiceRepositoryInterface
     */
    protected $invoiceRepository;

    /**
     * @var Transaction
     */
    protected $transaction;

    /**
     * @var Order
     */
    protected $order;
 
    /**
     * @var Float
     */
    protected $amount;

    /**
     * InvoiceHandlerService constructor.
     * @param Config $config
     * @param InvoiceService $invoiceService
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @param Transaction $transaction
     */
    public function __construct(
        Config $config,
        InvoiceService $invoiceService,
        InvoiceRepositoryInterface $invoiceRepository,
        Transaction $transaction      
    ) {
        $this->config             = $config;
        $this->invoiceService     = $invoiceService;
        $this->invoiceRepository  = $invoiceRepository;
        $this->transaction        = $transaction;
    }

    public function processInvoice($order, $amount) {
        // Assign the required values
        $this->order = $order; 
        $this->amount = $amount; 

        // Trigger the invoice creation
        if ($this->shouldInvoice())  $this->createInvoice();
    }

    public function shouldInvoice() {
        return $this->order->canInvoice() 
        && ($this->config->getAutoGenerateInvoice());
    }

    public function createInvoice() {
        // Prepare the invoice
        $invoice = $this->invoiceService->prepareInvoice($this->order);
        $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
        $invoice->setState(Invoice::STATE_PAID);
        $invoice->setBaseGrandTotal($this->amount);
        $invoice->register();
        $invoice->getOrder()->setIsInProcess(true);
        $invoice->pay();

        // Create the transaction
        $transactionSave = $this->transaction
        ->addObject($invoice)
        ->addObject($this->order);
        $transactionSave->save();

        // Update the order total paid
        $this->order->setTotalPaid($this->order->getTotalPaid());
        $this->order->setBaseTotalPaid($this->order->getBaseTotalPaid());
        $this->order->save();

        // Save the invoice
        $this->invoiceRepository->save($invoice);
    }
}