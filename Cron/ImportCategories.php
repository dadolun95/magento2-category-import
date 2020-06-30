<?php

namespace Dadolun\CategoryImport\Cron;

use Magento\Store\Model\StoreManagerInterface;
use Dadolun\CategoryImport\Model\Importer\Category as CategoryImporter;
use Dadolun\CategoryImport\Helper\CategoryImport as CategoryImportHelper;
use Psr\Log\LoggerInterface as PsrLoggerInterface;

/**
 * Class ImportCategories
 * @package Dadolun\CategoryImport\Cron
 */
class ImportCategories
{

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CategoryImporter
     */
    protected $categoryImporter;

    /**
     * @var CategoryImportHelper
     */
    protected $categoryImportHelper;

    /**
     * @var PsrLoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    private $errors;

    /**
     * ImportCategories constructor.
     * @param StoreManagerInterface $storeManager
     * @param CategoryImporter $categoryImporter
     * @param CategoryImportHelper $categoryImportHelper
     * @param PsrLoggerInterface $logger
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        CategoryImporter $categoryImporter,
        CategoryImportHelper $categoryImportHelper,
        PsrLoggerInterface $logger
    )
    {
        $this->storeManager = $storeManager;
        $this->storeManager->setCurrentStore('admin');
        $this->categoryImporter = $categoryImporter;
        $this->categoryImportHelper = $categoryImportHelper;
        $this->logger = $logger;
    }

    public function execute()
    {
        $this->logger->info(__('Start import categories by cron'));
        try {
            $data = $this->categoryImportHelper->getCsvData();
            foreach ($data as $row) {
                $this->categoryImporter->addOrUpdateCategory($row);
            }
            $this->errors = $this->categoryImporter->getErrors();
            if (!empty($this->errors)) {
                $this->logger->error(__('There was %1 errors:', count($this->errors)));
                foreach ($this->errors as $error) {
                    $this->logger->error($error);
                }
            } else {
                $this->logger->info(__('Import completed successfully!'));
            }
            $fileSystemError = $this->categoryImportHelper->moveCategoriesFileAfterImport($this->errors);
            if ($fileSystemError !== false) {
                $this->logger->info(__('There was an error moving imported file: %1', $fileSystemError));
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
