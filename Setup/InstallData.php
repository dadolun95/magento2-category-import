<?php

namespace Dadolun\CategoryImport\Setup;

use Dadolun\CategoryImport\Helper\CategoryImport;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;

/**
 * Class InstallData
 * @package Dadolun\CategoryImport\Setup
 */
class InstallData implements InstallDataInterface
{

    const CATEGORY_ATTRIBUTES = [
        CategoryImport::CATEGORY_CODE_ATTRIBUTE_CODE => 'Category identifier'
    ];

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * InstallData constructor.
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory
    )
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->addCategoryIdentifierAttribute($setup);
    }

    /**
     * @param $setup
     */
    private function addCategoryIdentifierAttribute($setup)
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        $attributes = self::CATEGORY_ATTRIBUTES;
        foreach ($attributes as $attributeCode => $attributeLabel) {
            $attributeData = $this->getCategoryAttributeData($attributeCode, $attributeLabel);

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Category::ENTITY,
                $attributeCode,
                $attributeData
            );
        }
    }

    /**
     * @param $attributeCode
     * @param $attributeLabel
     * @return array
     */
    private function getCategoryAttributeData($attributeCode, $attributeLabel)
    {
        return [
            'type' => 'varchar',
            'label' => $attributeLabel,
            'input' => 'text',
            'source' => '',
            'visible' => true,
            'default' => null,
            'required' => true,
            'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
            'user_defined' => false,
            'group' => '',
            'backend' => ''
        ];
    }
}
