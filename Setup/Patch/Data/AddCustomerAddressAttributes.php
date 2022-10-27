<?php
/**
 *
 *          ..::..
 *     ..::::::::::::..
 *   ::'''''':''::'''''::
 *   ::..  ..:  :  ....::
 *   ::::  :::  :  :   ::
 *   ::::  :::  :  ''' ::
 *   ::::..:::..::.....::
 *     ''::::::::::::''
 *          ''::''
 *
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to support@tig.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact support@tig.nl for more information.
 *
 * @copyright   Copyright (c) Total Internet Group B.V. https://tig.nl/copyright
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
namespace TIG\RoutiGo\Setup\Patch\Data;

use Magento\Customer\Setup\CustomerSetup;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddCustomerAddressAttributes implements DataPatchInterface
{
    /** @var array */
    private $customerAttributes = [
        'routigo_timeframes_fee' => [
            'data' => [
                'label' => 'Timeframes fee',
                'visible_on_front' => true
            ],
            'formdata' => [
                'used_in_forms' => ['adminhtml_customer_address', 'customer_address_edit', 'customer_register_address'],
                'sort_order' => 190
            ]
        ],
        'routigo_delivery_date' => [
            'data' => [
                'label' => 'Delivery date',
                'visible_on_front' => true
            ],
            'formdata' => [
                'used_in_forms' => ['adminhtml_customer_address', 'customer_address_edit', 'customer_register_address'],
                'sort_order' => 200
            ]
        ],
    ];

    /** @var ModuleDataSetupInterface */
    private $moduleDataSetup;

    /** @var CustomerSetupFactory */
    private $customerSetupFactory;

    /** @var SetFactory */
    private $attributeSetFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CustomerSetupFactory     $customerSetupFactory
     * @param SetFactory               $attributeSetFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CustomerSetupFactory     $customerSetupFactory,
        SetFactory               $attributeSetFactory
    ) {
        $this->moduleDataSetup      = $moduleDataSetup;
        $this->customerSetupFactory = $customerSetupFactory;
        $this->attributeSetFactory  = $attributeSetFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $this->addAttribute();

        $this->moduleDataSetup->endSetup();
    }

    /**
     * @throws LocalizedException
     * @throws \Zend_Validate_Exception
     */
    private function addAttribute()
    {
        /** @var CustomerSetup $customerSetup */
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavConfig     = $customerSetup->getEavConfig();

        foreach ($this->customerAttributes as $attributeCode => $data) {
            if ($customerSetup->getAttribute('customer_address', $attributeCode)) {
                continue;
            }

            $newData = $this->getAttributeData($data['data']);
            $customerSetup->addAttribute('customer_address', $attributeCode, $newData);

            $newFormData  = $this->getAttributeFormData($eavConfig, $data['formdata']);
            $newAttribute = $eavConfig->getAttribute('customer_address', $attributeCode);
            $newAttribute->addData($newFormData);
            $newAttribute->save();
        }
    }

    /**
     * @param array $customData
     *
     * @return array
     */
    private function getAttributeData($customData)
    {
        $defaultData = [
            'type'                  => 'varchar',
            'input'                 => 'text',
            'required'              => false,
            'visible'               => true,
            'user_defined'          => true,
            'unique'                => false,
            'system'                => 0,
            'sort_order'            => 1,
            'is_used_in_grid'       => false,
            'is_visible_in_grid'    => false,
            'is_filterable_in_grid' => false,
            'is_searchable_in_grid' => false
        ];

        $newData = array_merge($defaultData, $customData);

        return $newData;
    }

    /**
     * @param EavConfig $eavConfig
     * @param array     $customData
     *
     * @return array
     * @throws LocalizedException
     */
    private function getAttributeFormData(EavConfig $eavConfig, $customData)
    {
        $customerEntity = $eavConfig->getEntityType('customer_address');
        $defaultSetId   = $customerEntity->getDefaultAttributeSetId();

        $attributeSet   = $this->attributeSetFactory->create();
        $defaultGroupId = $attributeSet->getDefaultGroupId($defaultSetId);

        $defaultData = [
            'attribute_set_id' => $defaultSetId,
            'attribute_group_id' => $defaultGroupId,
            'used_in_forms' => []
        ];

        $newData = array_merge($defaultData, $customData);

        return $newData;
    }

    /**
     * {@inheritDoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getAliases()
    {
        return [];
    }
}
