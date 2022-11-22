<?php
namespace LatitudeNew\Payment\Model;

use Magento\Directory\Helper\Data as DirectoryHelper;

class Latitude extends \Magento\Payment\Model\Method\AbstractMethod
{
    public const MINUTE_DELAYED_ORDER = 75;

    /**
     * @var string
     */
    protected $_code = 'latitude';

    /**
     * @var boolean
     */
    protected $_isOffline = false;

    /**
     * @var boolean
     */
    protected $_isGateway = true;

    /**
     * @var boolean
     */
    protected $_canOrder = true;

    /**
     * @var boolean
     */
    protected $_canAuthorize = true;

    /**
     * @var boolean
     */
    protected $_canCapture = true;

    /**
     * @var boolean
     */
    protected $_canCapturePartial = true;

    /**
     * @var boolean
     */
    protected $_canVoid = true;

    /**
     * @var boolean
     */
    protected $_canRefund = true;

    /**
     * @var boolean
     */
    protected $_canUseForMultishipping = false;

    /**
     * @var boolean
     */
    protected $_isInitializeNeeded = true;

    /**
     * @var boolean
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * @var boolean
     */
    protected $_canUseInternal = false;

    /**
     * @var boolean
     */
    protected $_canFetchTransactionInfo = true;

    /**
     * @var boolean
     */
    protected $_canReviewPayment = true;

    /**
     * @var \LatitudeNew\Payment\Block\Form\Latitude
     */
    protected $_formBlockType = \LatitudeNew\Payment\Block\Form\Latitude::class;

    /**
     * @var \Magento\Payment\Block\Info\Instructions
     */
    protected $_infoBlockType = \Magento\Payment\Block\Info\Instructions::class;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \LatitudeNew\Payment\Model\Api
     */
    protected $latitudeApi;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Construct
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \LatitudeNew\Payment\Model\Api $latitudeApi
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @param DirectoryHelper $directory
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \LatitudeNew\Payment\Model\Api $latitudeApi,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        DirectoryHelper $directory = null
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
            $data,
            $directory
        );
        $this->latitudeApi = $latitudeApi;
        $this->storeManager = $storeManager;
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * Check if LC is available
     * 
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        //@TODO:
        //1. check if API crediential coorect.
        //2. check currency is correct again
        //3. check quote amount is correct.
        return $this->isActive() && $quote && ($quote->getGrandTotal() >= 20);
    }

    /**
     * Is active
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isActive($storeId = null)
    {
        return (bool)(int)$this->_scopeConfig->getValue(
            'payment/latitude/active',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        ) && (bool)(int)$this->getConfigData('active', $storeId);
    }

    /**
     * Refund (override from \Magento\Payment\Model\Method\AbstractMethod)
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $order = $payment->getOrder();
        $transId = $payment->getParentTransactionId();
        $gatewayReference = $payment->getAdditionalInformation('gateway_reference');

        if (!$transId) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Error issuing refund - no Transaction Id')
            );
        }

        if (!$gatewayReference) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Error issuing refund - no Gateway Reference')
            );
        }

        $grand_total = $order->getGrandTotal();
        $total_refunded = $order->getTotalRefunded();
        $refunded_amt = $grand_total - $total_refunded;

        if ($refunded_amt == 0) {
            $refundStatus = 'Full Refund';
        } elseif ($refunded_amt < 0) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __(
                    'Error issuing refund - Grandtotal: '.$grand_total.
                    ', Total Refunded: '.$total_refunded.
                    ', Amount: '.$amount
                )
            );
        } else {
            $refundStatus = 'Partial Refund';
        }

        $this->latitudeApi->refundLCOrder($order, $transId, $gatewayReference, $amount, $refundStatus);
        return $this;
    }

    /**
     * Capture (override from \Magento\Payment\Model\Method\AbstractMethod)
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $order = $payment->getOrder();
        $transId = $payment->getParentTransactionId();
        $gatewayReference = $payment->getAdditionalInformation('gateway_reference');

        if ($order->getStatus() !== \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT
            && $order->getStatus() !== \Magento\Sales\Model\Order::STATE_PROCESSING
            && $order->getStatus() !== 'pending_latitude_approval'
            ) {
            //in case status is not pending approval, processing (partially captured) or pending payment
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Invalid capture scenario - cannot capture order with status '.$order->getStatus())
            );
        }

        if (!$transId) {
            return $this; //in case instant
        }

        if (!$gatewayReference) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Error capturing payment - no Gateway Reference')
            );
        }

        $grand_total = round($order->getGrandTotal(),2);
        $total_invoiced = round($order->getTotalInvoiced(),2);
        $remaining_amt = $grand_total - $total_invoiced - round($amount,2);

        if (round($remaining_amt,2) == 0) {
            $reason = 'Full Capture';
        } elseif (round($remaining_amt,2) < 0) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __(
                    'Error issuing capture - Grandtotal: '.$grand_total.
                    ', Total Invoiced: '.$total_invoiced.
                    ', Amount: '.$amount.
                    ', Remaining: '.$remaining_amt
                )
            );
        } else {
            $reason = 'Partial Capture';
        }

        $this->latitudeApi->captureLCOrder($order, $transId, $gatewayReference, $amount, $reason);
        return $this;
    }

    /**
     * Void (override from \Magento\Payment\Model\Method\AbstractMethod)
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return $this
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        $order = $payment->getOrder();
        $transId = $payment->getParentTransactionId();
        $gatewayReference = $payment->getAdditionalInformation('gateway_reference');
        $grand_total = $order->getGrandTotal();

        if (!$transId) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Error voiding payment - no Transaction Id')
            );
        }

        if (!$gatewayReference) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Error voiding payment - no Gateway Reference')
            );
        }

        $this->latitudeApi->voidLCOrder($order, $transId, $gatewayReference, $grand_total);
        return $this;
    }
}
