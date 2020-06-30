<?php
namespace Dadolun\CategoryImport\Model\Config;

use \Magento\Framework\Config\FileResolverInterface;
use \Dadolun\CategoryImport\Model\Config\Converter;
use \Dadolun\CategoryImport\Model\Config\SchemaLocator;
use \Magento\Framework\Config\ValidationStateInterface;

/**
 * Class Reader
 * @package Dadolun\CategoryImport\Model\Config
 */
class Reader extends \Magento\Framework\Config\Reader\Filesystem
{

    protected $_idAttributes = [
        '/additional' => 'name',
        '/additional/column' => 'csv_name'
    ];

    /**
     * Reader constructor.
     * @param FileResolverInterface $fileResolver
     * @param \Dadolun\CategoryImport\Model\Config\Converter $converter
     * @param \Dadolun\CategoryImport\Model\Config\SchemaLocator $schemaLocator
     * @param ValidationStateInterface $validationState
     * @param string $fileName
     * @param array $idAttributes
     * @param string $domDocumentClass
     * @param string $defaultScope
     */
    public function __construct(
        FileResolverInterface $fileResolver,
        Converter $converter,
        SchemaLocator $schemaLocator,
        ValidationStateInterface $validationState,
        $fileName = 'category_import.xml',
        $idAttributes = [],
        $domDocumentClass = \Magento\Framework\Config\Dom::class,
        $defaultScope = 'global'
    ) {
        parent::__construct(
            $fileResolver,
            $converter,
            $schemaLocator,
            $validationState,
            $fileName,
            $idAttributes,
            $domDocumentClass,
            $defaultScope
        );
    }
}
