<?php
namespace LatitudeNew\Payment\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;

class UpdateOrderStatuses implements DataPatchInterface
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
        $this->helper->log('*** LATITUDE ORDER STATUS PATCH - Adding Custom Status pending_latitude_approval ***');

        // Insert statuses
        // use insertOnDuplicate(), insertArray() etc here
        $this->moduleDataSetup->getConnection()->insertOnDuplicate(
            $this->moduleDataSetup->getTable('sales_order_status'),
            ['status' => 'pending_latitude_approval', 'label' => 'Pending Latitude Approval']
        );

        $this->helper->log('STATUS pending_latitude_approval successfully added to sales_order_status');
  
        $this->moduleDataSetup->getConnection()->insertOnDuplicate(
            $this->moduleDataSetup->getTable('sales_order_status_state'),
            [
                'status'     => 'pending_latitude_approval',
                'state'      => 'new',
                'is_default' => 0,
            ]
        );

        $this->helper->log('STATUS pending_latitude_approval successfully linked to STATE new in sales_order_status_state');
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