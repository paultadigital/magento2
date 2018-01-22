<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Framework\Setup\SchemaListenerDefinition;

use Magento\Framework\DB\Ddl\Table;

/**
 * Convert definition for all text types: text, mediumtext, longtext
 */
class TextBlobDefinition implements DefinitionConverterInterface
{
    /**
     * Parse text size
     * Returns max allowed size if value great it
     *
     * @param string|int $size
     * @return int
     */
    private function parseTextSize($size)
    {
        $size = trim($size);
        $last = strtolower(substr($size, -1));

        switch ($last) {
            case 'k':
                $size = intval($size) * 1024;
                break;
            case 'm':
                $size = intval($size) * 1024 * 1024;
                break;
            case 'g':
                $size = intval($size) * 1024 * 1024 * 1024;
                break;
        }

        if (empty($size)) {
            return Table::DEFAULT_TEXT_SIZE;
        }
        if ($size >= Table::MAX_TEXT_SIZE) {
            return Table::MAX_TEXT_SIZE;
        }

        return intval($size);
    }

    /**
     * Retrieve type of column by it length
     *
     * @param string $ddlType
     * @param int $length
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getCTypeByLength($ddlType, $length)
    {
        if ($length > 0 && $length <= 255) {
            $cType = $ddlType == Table::TYPE_TEXT ? 'varchar' : 'varbinary';
        } elseif ($length > 255 && $length <= 65536) {
            $cType = $ddlType == Table::TYPE_TEXT ? 'text' : 'blob';
        } elseif ($length > 65536 && $length <= 16777216) {
            $cType = $ddlType == Table::TYPE_TEXT ? 'mediumtext' : 'mediumblob';
        } else {
            $cType = $ddlType == Table::TYPE_TEXT ? 'longtext' : 'longblob';
        }

        return $cType;
    }

    /**
     * @inheritdoc
     */
    public function convertToDefinition(array $definition)
    {
        $length = $this->parseTextSize($definition['length'] ?? 0);
        $ddlType = $definition['type'];
        $cType = $this->getCTypeByLength($ddlType, $length);

        $newDefinition = [
            'xsi:type' => $cType,
            'name' => $definition['name'],
            'nullable' => $definition['nullable'] ?? true,
            'primary' => $definition['primary'] ?? false
        ];

        if (in_array($cType, ['varchar', 'varbinary'])) {
            $newDefinition['length'] = $length;
            $newDefinition['default'] = $definition['default'] ?? null;
        }

        return $newDefinition;
    }
}
