<?php
namespace LatitudeNew\Payment\Block\Adminhtml\System\Config\Form;

/**
 * Version renderer for Latitude solution
 */
class Version extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var \LatitudeNew\Payment\Helper\Data
     */
    protected $helper;

    /**
     * Version constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \LatitudeNew\Payment\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \LatitudeNew\Payment\Helper\Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * Render element value
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $output = '<div style="background-color:#eee; margin:20px 0;padding:10px 15px; border:1px solid #ddd;">';
        $output .= __('Module version') . ': ' . $this->helper->getConfigData('version', null, 'latitudepay');
        $output .= "</div>";
        return '<div id="row_' . $element->getHtmlId() . '">' . $output . '</div>';
    }
}
