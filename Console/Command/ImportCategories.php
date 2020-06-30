<?php

namespace Dadolun\CategoryImport\Console\Command;

use Magento\Store\Model\StoreManagerInterface;
use Dadolun\CategoryImport\Model\Importer\Category as CategoryImporter;
use Dadolun\CategoryImport\Helper\CategoryImport as CategoryImportHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ImportCategories
 * @package Dadolun\CategoryImport\Console\Command
 */
class ImportCategories extends Command
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
     * @var array
     */
    private $errors;

    /**
     * ImportCategories constructor.
     * @param StoreManagerInterface $storeManager
     * @param CategoryImporter $categoryImporter
     * @param CategoryImportHelper $categoryImportHelper
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        CategoryImporter $categoryImporter,
        CategoryImportHelper $categoryImportHelper
    )
    {
        $this->storeManager = $storeManager;
        $this->storeManager->setCurrentStore('admin');
        $this->categoryImporter = $categoryImporter;
        $this->categoryImportHelper = $categoryImportHelper;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('dadolun:import:categories')
            ->setDescription(__('Run category importer script'));

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $data = $this->categoryImportHelper->getCsvData();
            foreach ($data as $row) {
                $this->categoryImporter->addOrUpdateCategory($row);
            }

            $this->errors = $this->categoryImporter->getErrors();

            if (!empty($this->errors)) {
                $output->writeln(__('There was %1 errors:', count($this->errors)));
                foreach ($this->errors as $error) {
                    $output->writeln($error);
                }
            } else {
                $output->writeln(__('Import completed successfully!'));
            }
            $fileSystemError = $this->categoryImportHelper->moveCategoriesFileAfterImport($this->errors);
            if ($fileSystemError !== false) {
                $output->writeln(__('There was an error moving imported file: %1', $fileSystemError));
            }
            return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $output->writeln($e->getTraceAsString());
            }
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }
    }
}
