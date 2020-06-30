<?php

namespace Dadolun\CategoryImport\Api;

/**
 * Interface CategoryImportInterface
 * @package Dadolun\CategoryImport\Api
 */
interface CategoryImportInterface
{
    /**
     * @param array $data
     * @return mixed
     */
    public function addOrUpdateCategory(array $data);

    /**
     * @return array
     */
    public function getErrors();

    /**
     * @param \Magento\Framework\DataObject $category
     * @param array $data
     * @return mixed
     */
    public function manageAdditionalCategoryData(\Magento\Framework\DataObject $category, array $data);

}
