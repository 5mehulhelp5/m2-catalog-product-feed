<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductFeed\Model\Config\Custom;

use DOMDocument;
use DOMNode;
use Magento\Framework\Config\ConverterInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Converter implements ConverterInterface
{
    /**
     * @param DOMDocument $source
     */
    public function convert($source): array
    {
        $result = [];

        /** @var DOMNode $childNode */
        foreach ($source->childNodes as $childNode) {
            if ($childNode->nodeName === 'config') {
                /** @var DOMNode $childChildNode */
                foreach ($childNode->childNodes as $childChildNode) {
                    if ($childChildNode->nodeName === 'custom_fields') {
                        /** @var DOMNode $childChildChildNode */
                        foreach ($childChildNode->childNodes as $childChildChildNode) {
                            $name = null;
                            $label = null;
                            $model = null;

                            if ($childChildChildNode->nodeName === 'custom_field') {
                                $attributes = $childChildChildNode->attributes;

                                if ($attributes) {
                                    $nameNode = $attributes->getNamedItem('name');

                                    if ($nameNode) {
                                        $name = (string)$nameNode->nodeValue;
                                    }

                                    /** @var DOMNode $childChildChildChildNode */
                                    foreach ($childChildChildNode->childNodes as $childChildChildChildNode) {
                                        if ($childChildChildChildNode->nodeName === 'label') {
                                            $label = (string)$childChildChildChildNode->nodeValue;
                                        }
                                        if ($childChildChildChildNode->nodeName === 'model') {
                                            $model = (string)$childChildChildChildNode->nodeValue;
                                        }
                                    }
                                }
                            }

                            if (! empty($name) && ! empty($label) && ! empty($model)) {
                                $result[ $name ] = ['label' => $label, 'model' => $model];
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }
}
