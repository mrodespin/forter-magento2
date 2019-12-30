<?php

namespace Forter\Forter\Model\ActionsHandler;

use Forter\Forter\Model\AbstractApi;
use Forter\Forter\Model\Config as ForterConfig;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\CreditmemoService;

/**
 * Class Decline
 * @package Forter\Forter\Model\ActionsHandler
 */
class Decline
{
    /**
      * @var AbstractApi
      */
    private $abstractApi;
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;
    /**
     * @var Order
     */
    private $order;
    /**
     * @var ForterConfig
     */
    private $forterConfig;
    /**
     * @var CreditmemoFactory
     */
    private $creditmemoFactory;
    /**
     * @var CreditmemoService
     */
    private $creditmemoService;
    /**
     * @var Invoice
     */
    private $invoice;

    /**
     * Decline constructor.
     * @param Order $order
     * @param CreditmemoFactory $creditmemoFactory
     * @param ForterConfig $forterConfig
     * @param CheckoutSession $checkoutSession
     * @param Invoice $invoice
     * @param CreditmemoService $creditmemoService
     */
    public function __construct(
        AbstractApi $abstractApi,
        Order $order,
        CreditmemoFactory $creditmemoFactory,
        ForterConfig $forterConfig,
        CheckoutSession $checkoutSession,
        Invoice $invoice,
        CreditmemoService $creditmemoService,
        OrderManagementInterface $orderManagement
    ) {
        $this->abstractApi = $abstractApi;
        $this->orderManagement = $orderManagement;
        $this->checkoutSession = $checkoutSession;
        $this->order = $order;
        $this->forterConfig = $forterConfig;
        $this->creditmemoFactory = $creditmemoFactory;
        $this->creditmemoService = $creditmemoService;
        $this->invoice = $invoice;
    }

    /**
     * @return $this
     */
    public function handlePreTransactionDescision()
    {
        $forterDecision = $this->forterConfig->getDeclinePre();
        if ($forterDecision == '1') {
            throw new PaymentException(__($this->forterConfig->getPreThanksMsg()));
        } elseif ($forterDecision == '2') {
            $this->checkoutSession->destroy();
        }

        return $this;
    }

    /**
     * @param $order
     * @return $this|bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function handlePostTransactionDescision($order)
    {
        try {
            if ($order->canCancel()) {
                $this->cancelOrder($order);
            }

            if ($order->canCreditmemo()) {
                $this->createCreditMemo($order);
            }

            $state = $order->getState();
            if ($state != 'closed' &&  $state != 'canceled' && $state != 'complete') {
                $result = $this->holdOrder($order);
            }

            return $this;
        } catch (Exception $e) {
            $this->abstractApi->reportToForterOnCatch($e);
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @param $order
     * @return bool
     */
    private function cancelOrder($order)
    {
        $order->cancel()->save();
        if ($order->isCanceled()) {
            $order->addCommentToOrder($order, 'Order Cancelled');
            return true;
        }

        $order->addCommentToOrder($order, 'Order Cancellation attempt failed');
        return false;
    }

    /**
     * @param $order
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function createCreditMemo($order)
    {
        $invoices = $order->getInvoiceCollection();
        foreach ($invoices as $invoice) {
            $invoiceincrementid = $invoice->getIncrementId();
            $invoiceobj = $this->invoice->loadByIncrementId($invoiceincrementid);
            $creditmemo = $this->creditmemoFactory->createByOrder($order);

            if ($invoiceobj || isset($invoiceobj)) {
                $creditmemo->setInvoice($invoiceobj);
                $this->creditmemoService->refund($creditmemo);
                $totalRefunded = $order->getTotalRefunded();
            }

            if ($totalRefunded > 0) {
                $order->addCommentToOrder($order, $totalRefunded . ' Refunded');
                return true;
            }
        }

        $order->addCommentToOrder($order, 'Order Refund attempt failed');
        return false;
    }

    /**
     * @param $order
     * @return bool
     */
    public function holdOrder($order)
    {
        $order->hold()->save();
        $order->addStatusHistoryComment("Order Has been holded")
          ->setIsCustomerNotified(false)
          ->setEntityName('order')
          ->save();
        return true;
    }

    /**
     * @param $order
     * @param $message
     */
    private function addCommentToOrder($order, $message)
    {
        $order->addStatusHistoryComment('Forter:' . $message)
          ->setIsCustomerNotified(false)
          ->setEntityName('order')
          ->save();
    }

    private function markOrderPaymentReview($order)
    {
        $orderState = Order::STATE_PAYMENT_REVIEW;
        $order->setState($orderState)->setStatus(Order::STATE_PAYMENT_REVIEW);
        $order->setStatus('Suspected Fraud');
        $order->save();
        $this->addCommentToOrder($order, 'Order Has been marked for Payment Review');
    }
}
