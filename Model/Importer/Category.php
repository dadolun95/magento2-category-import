<?php

namespace Dadolun\CategoryImport\Model\Importer;

use Dadolun\CategoryImport\Helper\CategoryImport;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Api\CategoryRepositoryInterface as CategoryRepository;
use Dadolun\CategoryImport\Model\Config\Reader;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Category
 * @package Dadolun\CategoryImport\Model\Importer
 */
class Category implements \Dadolun\CategoryImport\Api\CategoryImportInterface
{

    const ROOT_CATEGORY_ID = 2;
    const MAIN_CATEGORIES_LEVEL = 2;

    const DEFAULT_ADDITIONAL_COLUMNS_INDEX = 'main';

    /**
     * CSV Header columns
     */
    const CSV_HEADER_CODE_STRING = CategoryImport::CATEGORY_CODE_ATTRIBUTE_CODE;
    const CSV_HEADER_PATH_STRING = 'path';
    const CSV_HEADER_SORT_STRING = 'sort_order';
    const CSV_HEADER_ENABLED_STRING = 'is_active';

    /**
     * @var CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var CategoryCollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    protected $configReader;

    /**
     * CSV Header Mapping
     * @var array
     */
    protected $headersMap = [
        self::CSV_HEADER_CODE_STRING,
        self::CSV_HEADER_PATH_STRING,
        self::CSV_HEADER_SORT_STRING,
        self::CSV_HEADER_ENABLED_STRING
    ];

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * Category constructor.
     * @param CategoryFactory $categoryFactory
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param CategoryRepository $categoryRepository
     */
    public function __construct(
        CategoryFactory $categoryFactory,
        CategoryCollectionFactory $categoryCollectionFactory,
        CategoryRepository $categoryRepository,
        Reader $configReader
    )
    {
        $this->categoryFactory = $categoryFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryRepository = $categoryRepository;
        $this->configReader = $configReader;
        $this->compileHeaderWithAdditinalColumns();
    }

    /**
     * Load category by iStore uid
     * @param $data
     * @return bool|\Magento\Framework\DataObject
     * @throws LocalizedException
     */
    protected function getCategoryByCode($data)
    {
        $categoryCollection = $this->categoryCollectionFactory
            ->create()
            ->addAttributeToFilter(self::CSV_HEADER_CODE_STRING, $data[array_search(self::CSV_HEADER_CODE_STRING, $this->headersMap)])
            ->setPageSize(1);

        if ($categoryCollection->getSize()) {
            return $categoryCollection->getFirstItem();
        }
        return false;
    }

    /**
     * Get last parentID reading the entire category path
     * @param $categoryPathArray
     * @return bool
     * @throws LocalizedException
     */
    protected function getParentCategoryIdByPath($categoryPathArray)
    {
        $mainCategoryParent = false;
        $rootCategoryCollection = $this->categoryCollectionFactory
            ->create()
            ->addAttributeToFilter('name', $categoryPathArray[0])
            ->addAttributeToFilter('level', self::MAIN_CATEGORIES_LEVEL)
            ->setPageSize(1);

        end($categoryPathArray);
        $lastParentName = prev($categoryPathArray);

        if ($rootCategoryCollection->getSize()) {
            $mainCategoryParent = $rootCategoryCollection->getFirstItem();;
        }

        if ($mainCategoryParent === false) {
            return false;
        }

        if (count($categoryPathArray) > 2) {
            array_shift($categoryPathArray);
            return $this->getLastParentId(
                $mainCategoryParent,
                $categoryPathArray,
                $lastParentName
            );
        }
        return $mainCategoryParent->getId();
    }

    /**
     * @param $parentCategory
     * @param $categoryPathArray
     * @param $parentCategoryName
     * @return bool
     */
    public function getLastParentId($parentCategory, $categoryPathArray, $parentCategoryName)
    {
        $currentCheckName = $categoryPathArray[0];
        foreach ($parentCategory->getChildrenCategories() as $category) {
            $tmpCategoryPathArray = $categoryPathArray;
            if ($category->getName() === $currentCheckName) {
                if (count($categoryPathArray) > 2) {
                    array_shift($tmpCategoryPathArray);
                    $lastParentId = $this->getLastParentId(
                        $category,
                        $tmpCategoryPathArray,
                        $parentCategoryName
                    );
                    if ($lastParentId !== false) {
                        return $lastParentId;
                    }
                } else {
                    if ($currentCheckName === $parentCategoryName) {
                        return $category->getId();
                    } else {
                        return false;
                    }
                }
            }
        }
        return false;
    }

    /**
     * @param array $data
     * @return bool
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function addOrUpdateCategory(array $data)
    {
        $category = $this->getCategoryByCode($data);

        if ($category === false) {
            $category = $this->categoryFactory->create();
        }

        $categoryPath = $data[array_search(self::CSV_HEADER_PATH_STRING, $this->headersMap)];

        if (is_string($categoryPath)) {
            $categoryPathArray = explode(',', $categoryPath);
        } else {
            $this->errors[] = __(
                'ERROR (RECORD SKIPPED): Category "%1" has no path!',
                $data[array_search(self::CSV_HEADER_CODE_STRING, $this->headersMap)]
            );
            return false;
        }
        $categoryName = trim(end($categoryPathArray));

        // Always check path field exploded by comma value in order to decide if current category has a parent
        if (count($categoryPathArray) > 1) {
            $categoryParentName = prev($categoryPathArray);
        } else {
            $categoryParentName = null;
        }

        $category->setName($categoryName);
        $category->setData(
            CategoryImport::CATEGORY_CODE_ATTRIBUTE_CODE,
            $data[array_search(self::CSV_HEADER_CODE_STRING, $this->headersMap)]
        );

        $isActive = (($data[array_search(self::CSV_HEADER_ENABLED_STRING, $this->headersMap)]) ? true : false);

        $category->setIsActive($isActive);
        $category->setPosition($data[array_search(self::CSV_HEADER_SORT_STRING, $this->headersMap)]);
        $category->setIncludeInMenu(true);
        $category->setIsAnchor(true);

        $this->manageAdditionalCategoryData($category, $data);

        if (is_null($categoryParentName)) {
            $category->setParentId(self::ROOT_CATEGORY_ID);
        } else {
            $parentId = $this->getParentCategoryIdByPath($categoryPathArray);
            if ($parentId !== false) {
                $category->setParentId($parentId);
            } else {
                $this->errors[] = __(
                    'ERROR (RECORD SKIPPED): Category "%1" does not have existing parent category!',
                    $data[array_search(self::CSV_HEADER_CODE_STRING, $this->headersMap)]
                );
                return false;
            }
        }

        try {
            $this->categoryRepository->save($category);
        } catch (\Exception $e) {
            $this->errors[] = __(
                'ERROR (RECORD SKIPPED): Category "%1" error saving category!',
                $data[array_search(self::CSV_HEADER_CODE_STRING, $this->headersMap)]
            );
        }
        return true;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Add category_import.xml additional columns to header
     */
    private function compileHeaderWithAdditinalColumns() {
        $additionalConfigs = $this->configReader->read();
        foreach ($additionalConfigs as $name => $columns) {
            foreach($columns as $index => $column) {
                $this->headersMap[] = $column['csv_name'];
            }
        }
    }

    /**
     * @param \Magento\Framework\DataObject $category
     * @param array $data
     * @return mixed|void
     */
    public function manageAdditionalCategoryData(\Magento\Framework\DataObject $category, array $data) {
        $additionalConfigs = $this->configReader->read();
        foreach ($additionalConfigs as $name => $columns) {
            if ($name === self::DEFAULT_ADDITIONAL_COLUMNS_INDEX) {
                foreach($columns as $index => $column) {
                    $category->setData(
                        $column['attribute_name'],
                        $data[array_search($column['csv_name'], $this->headersMap)]
                    );
                }
            }
        }
    }
}
