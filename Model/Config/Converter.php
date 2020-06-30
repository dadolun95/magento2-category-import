<?php

namespace Dadolun\CategoryImport\Model\Config;

/**
 * Class Converter
 * @package Dadolun\CategoryImport\Model\Config
 */
class Converter implements \Magento\Framework\Config\ConverterInterface
{
    /**
     * Converting data to array type
     *
     * @param mixed $source
     * @return array
     * @throws \InvalidArgumentException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function convert($source)
    {
        $output = [];
        if (!$source instanceof \DOMDocument) {
            return $output;
        }

        /** @var \DOMNodeList $sections*/
        $additionals = $source->getElementsByTagName('additional');

        /** @var \DOMElement $section */
        foreach ($additionals as $additional) {
            $columnsArray = [];
            $additionalNames = $additional->getAttribute('name');

            if (!$additionalNames) {
                throw new \InvalidArgumentException('Attribute "name" of "additional" does not exist');
            }

            $columns = $additional->getElementsByTagName('column');
            foreach($columns as $column){
                $columnsArray[$column->getAttribute('sort')] = [
                    'csv_name' => $column->getAttribute('csv_name'),
                    'attribute_name' => $column->getAttribute('attribute_name')
                ];
            }

            if($additionalNames !== 'main') {
                $additionalNames = 'custom';
            }
            $output[$additionalNames] = $columnsArray;
        }

        return $output;
    }
}
