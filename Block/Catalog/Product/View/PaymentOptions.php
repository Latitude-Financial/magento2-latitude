<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/**
 * Payment Options rendering block
 * Class PaymentOptions
 * @package LatitudeNew\Payment\Block\Catalog\Product\View\PaymentOptions
 */
namespace LatitudeNew\Payment\Block\Catalog\Product\View;

use \Magento\Catalog\Block\Product\Context;

/**
 * Payment Options block
 */
class PaymentOptions extends \Magento\Framework\View\Element\Template
{
    /**
     * Core registry
     * 
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     *Product model
     * 
     * @var \Magento\Catalog\Model\Product
     */
    protected $product;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \LatitudeNew\Payment\Helper\Data
     */
    protected $helper;

    /**
     * @var int
     */
    protected const INSTALLMENT_NO = 10;

    /**
     * PaymentOptions constructor.
     * 
     * @param Context $context
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \LatitudeNew\Payment\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \LatitudeNew\Payment\Helper\Data $helper,
        array $data = []
    ) {
        $this->coreRegistry  = $context->getRegistry();
        $this->priceCurrency = $priceCurrency;
        $this->helper  = $helper;
        parent::__construct(
            $context,
            $data
        );
    }

    /**
     * Get current product info
     * 
     * @return mixed
     */
    public function getCurrentProduct()
    {
        if ($this->product == null) {
            $this->product = $this->coreRegistry->registry('current_product');
        }
        return $this->product;
    }

    /**
     * Gets Installment amount for current product
     * 
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAmount()
    {
        $amountPerInstallment ='';
        $totalAmount = $this->getCurrentProduct()->getFinalPrice();
        $InstallmentNo = $this->helper->getConfigData('installment_no');
        
        if ($InstallmentNo) {
            $curInstallment = $InstallmentNo;
        }

        if ($curInstallment) {
            $amountPerInstallment = $totalAmount;
        }

        return $amountPerInstallment;
    }

    /**
     * Whether to show snippet on PDP
     * 
     * @throws \Magento\Framework\Exception\LocalizedException
     * @param string $methodCode
     * @return bool
     */
    public function showOnPDP($methodCode = null)
    {
        $lpayEnabled = $this->helper->isLatitudepayEnabled();
        $gpayEnabled = $this->helper->isGenoapayEnabled();

        if ($methodCode) {
            if ($methodCode === 'latitude' && !$this->helper->isLCEnabled()) {
                return 0;
            }

            if (($methodCode === 'latitudepay' || $methodCode === 'genoapay') && !$lpayEnabled && !$gpayEnabled) {
                return 0;
            }

            return $this->helper->getConfigData('show_on_pdp', null, $methodCode);
        }

        if (!$lpayEnabled && !$gpayEnabled) {
            return 0;
        }
        
        //based on whatever's active (depending on store's currency setting)
        return $this->helper->getConfigData('show_on_pdp');
    }

    /**
     * Retrieve Snippet Image
     * 
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Magento\Framework\Phrase
     */
    public function getSnippetImage()
    {
        /* @noinspection PhpUndefinedMethodInspection */
        $param = [
            'amount' => $this->getAmount(),
            'services' => $this->helper->getLatitudepayPaymentServices(),
            'terms' => $this->helper->getLatitudepayPaymentTerms(),
            'style' => 'default'
        ];
        return $this->helper->getSnippetImageUrl() . '?' . http_build_query($param);
    }

    /**
     * Retrieve util js
     * 
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Magento\Framework\Phrase
     */
    public function getUtilJs()
    {
        /* @noinspection PhpUndefinedMethodInspection */
        return $this->helper->getUtilJs();
    }

    /**
     * Get price formated for LC widget
     */
    protected function getPriceForLC()
    {
        $product = $this->getCurrentProduct();

        if ($product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            return round((float)$product->getFinalPrice(), 2);
        }

        return round((float)$product->getPrice(), 2);
    }

    /**
     * Get options for LC widget
     * 
     * @param string $page
     */
    public function getLCOptions($page)
    {
        $product = $this->getCurrentProduct();

        return json_encode([
            "merchantId" => $this->helper->getConfigData('merchant_id', null, 'latitude'),
            "currency" => $this->helper->getStoreCurrency(),
            "container" =>"latitude-banner-container",
            "containerClass" => "",
            "page" => $page,
            "layout" => $this->helper->getConfigData('layout', null, 'latitude'),
            "paymentOption" => $this->helper->getConfigData('plan_type', null, 'latitude'),
            "promotionMonths" => $this->helper->getConfigData('plan_period', null, 'latitude'),
            "minAmount" => $this->helper->getConfigData('minimum_amount', null, 'latitude'),
            "product" => [
                "id" => $product->getId() ? $product->getId() : '',
                "name" =>  $product->getName() ? $product->getName() : '',
                "category" =>  $product->getCategory() ? $product->getCategory() : '',
                "price" => $this->getPriceForLC(),
                "sku" =>  $product->getSku() ? $product->getSku() : '',
            ]
        ]);
    }

    /**
     * Retrieve LC's merchant ID
     */
    public function getLCMerchantID()
    {
        return $this->helper->getConfigData('merchant_id', null, 'latitude');
    }

    /**
     * Retrieve LC's base API url
     */
    public function getLCHost()
    {
        $isTest = (boolean)($this->helper->getConfigData('test_mode', null, 'latitude') === '1');
        return $isTest ?
            'https://develop.checkout.test.merchant-services-np.lfscnp.com'
            :
            'https://checkout.latitudefinancial.com';
    }

    /**
     * Override from \Magento\Framework\View\Element\Template
     */
    public function _toHtml()
    {
        $_product = $this->getCurrentProduct();

        if (!$_product) {
            return '';
        }

        $paymentEnabled = $this->helper->isLatitudepayEnabled() || $this->helper->isGenoapayEnabled() || $this->helper->isLCEnabled();

        if ($_product->isAvailable() && $_product->isSaleable() && $paymentEnabled) {
            return parent::_toHtml();
        }
        
        return '';
    }
}