<?php
namespace LatitudeNew\Payment\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddCustomAuthorizedStatus implements DataPatchInterface
{
   /**
    * @var \Magento\Framework\Setup\ModuleDataSetupInterface
    */
   private $moduleDataSetup;

   /**
    * @var \LatitudeNew\Payment\Helper\Data
    */
    private $helper;

   public function __construct(
       \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup,
       \LatitudeNew\Payment\Helper\Data $helper
   ) {
       $this->moduleDataSetup = $moduleDataSetup;
       $this->helper = $helper;
   }

   /**
    * {@inheritdoc}
    */
   public function apply()
   {
        $this->helper->log('*** LATITUDE ORDER STATUS PATCH - Adding Custom Status pending_latitude_capture ***');

        // Insert statuses
        // use insertOnDuplicate(), insertArray() etc here
        $this->moduleDataSetup->getConnection()->insertOnDuplicate(
            $this->moduleDataSetup->getTable('sales_order_status'),
            ['status' => 'pending_latitude_capture', 'label' => 'Pending Latitude Capture']
        );

        $this->helper->log('STATUS pending_latitude_capture successfully added to sales_order_status');
  
        $this->moduleDataSetup->getConnection()->insertOnDuplicate(
            $this->moduleDataSetup->getTable('sales_order_status_state'),
            [
                'status'     => 'pending_latitude_capture',
                'state'      => 'processing',
                'is_default' => 0,
                'visible_on_front' => 1
            ]
        );

        $this->helper->log('STATUS pending_latitude_capture successfully linked to STATE processing in sales_order_status_state');
   }

   /**
    * {@inheritdoc}
    */
   public static function getDependencies()
   {
       return [];
   }

   /**
    * {@inheritdoc}
    */
   public function getAliases()
   {
       return [];
   }
}