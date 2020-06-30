<?php

namespace Dadolun\CategoryImport\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\File\Csv as FileCsv;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File as FileSystemIo;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class CategoryImport
 * @package Dadolun\CategoryImport\Helper
 */
class CategoryImport extends AbstractHelper
{
    const IMPORT_CATEGORY_FILE_PATH_CONFIG = 'dadolun/category_import/csv_path';
    const CATEGORY_IMPORT_DELIMITER = 'dadolun/category_import/csv_delimiter';
    const CATEGORY_CODE_ATTRIBUTE_CODE = 'category_code';
    const IMPORT_CATEGORY_DONE_PATH_CONFIG = 'dadolun/category_import/done_csv_path';
    const IMPORT_CATEGORY_ERROR_PATH_CONFIG = 'dadolun/category_import/error_csv_path';
    const MOVED_FILE_NAME = 'categories.csv';
    const SKIP_FIRST_ROW = true;

    /**
     * @var FileCsv
     */
    private $fileCsv;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var FileSystemIo
     */
    private $filesystemIo;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * CategoryImport constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param FileCsv $fileCsv
     * @param DirectoryList $directoryList
     * @param FileSystemIo $filesystemIo
     * @param Context $context
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        FileCsv $fileCsv,
        DirectoryList $directoryList,
        FileSystemIo $filesystemIo,
        Context $context
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->fileCsv = $fileCsv;
        $this->directoryList = $directoryList;
        $this->filesystemIo = $filesystemIo;
        parent::__construct($context);
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getCsvData()
    {
        $path = $this->scopeConfig->getValue(
            self::IMPORT_CATEGORY_FILE_PATH_CONFIG,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $file = $this->directoryList->getRoot() . '/' . $path;

        if ($file === false || !file_exists($file)) {
            throw new LocalizedException(__('File %1 does not exist!', $file));
        }

        $this->fileCsv->setDelimiter($this->getCsvDelimiter());
        $data = $this->fileCsv->getData($file);

        if ($this->skipFirstCsvRow()) {
            array_shift($data);
        }
        return $data;
    }

    /**
     * @param $errors
     * @return bool|string
     */
    public function moveCategoriesFileAfterImport($errors)
    {
        $error = false;
        $file = $this->scopeConfig->getValue(
            self::IMPORT_CATEGORY_FILE_PATH_CONFIG,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (empty($errors)) {
            $path = $this->scopeConfig->getValue(
                self::IMPORT_CATEGORY_DONE_PATH_CONFIG,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        } else {
            $path = $this->scopeConfig->getValue(
                self::IMPORT_CATEGORY_ERROR_PATH_CONFIG,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }
        if (!file_exists($this->directoryList->getRoot() . '/' . $path)) {
            $result = $this->filesystemIo->mkdir($this->directoryList->getRoot() . '/' . $path, 0775);
            if ($result === false) {
                $error = __('Error creating categories import destination directories, please create done and error folders (see configs)');
            }
        }
        $path .= date('Ymd-His-') . self::MOVED_FILE_NAME;
        if ($error === false) {
            $result = $this->filesystemIo->mv($file, $path);
            if ($result === false) {
                $error = __('Error moving categories import file, check done and error folders permissions (see configs)');
            }
        }
        return $error;
    }

    /**
     * @return string
     */
    public function getCsvDelimiter()
    {
        return $this->scopeConfig->getValue(
            self::CATEGORY_IMPORT_DELIMITER,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return bool
     */
    public function skipFirstCsvRow()
    {
        return self::SKIP_FIRST_ROW;
    }
}
