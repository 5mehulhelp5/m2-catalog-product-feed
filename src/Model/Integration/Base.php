<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductFeed\Model\Integration;

use Exception;
use FeWeDev\Base\Arrays;
use FeWeDev\Base\Variables;
use Infrangible\CatalogProductFeed\Helper\Custom;
use Infrangible\CatalogProductFeed\Model\Attribute;
use Infrangible\CatalogProductFeed\Model\Attribute\Custom\AbstractCustom;
use Infrangible\CatalogProductFeed\Model\IIntegration;
use Infrangible\CatalogProductFeed\Model\ResourceModel\Attribute\Collection;
use Magento\Catalog\Model\Product;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class Base implements IIntegration
{
    /** @var Variables */
    protected $variables;

    /** @var Arrays */
    protected $arrays;

    /** @var \Infrangible\Core\Helper\Attribute */
    protected $attributeHelper;

    /** @var Custom */
    protected $productFeedCustomHelper;

    /** @var string[] */
    protected $attributeCodes = [];

    /** @var int[] */
    private $eavAttributeIds = [];

    /** @var string[] */
    private $eavAttributeCodes = [];

    /** @var AbstractCustom[] */
    private $customFields = [];

    /** @var string[] */
    private $fixedValues = [];

    /** @var array */
    protected $productAttributes = [];

    /** @var array */
    protected $variantAttributes = [];

    /** @var string[] */
    protected $valueFormats = [];

    /** @var string[] */
    protected $characterDataAttributes = [];

    /** @var string[] */
    protected $requiredEavAttributeCodes = [];

    /** @var string[] */
    protected $stripTagsAttributeCodes = [];

    public function __construct(
        Variables $variableHelper,
        Arrays $arrayHelper,
        \Infrangible\Core\Helper\Attribute $eavAttributeHelper,
        Custom $productFeedCustomHelper
    ) {
        $this->variables = $variableHelper;
        $this->arrays = $arrayHelper;
        $this->attributeHelper = $eavAttributeHelper;
        $this->productFeedCustomHelper = $productFeedCustomHelper;
    }

    /**
     * @throws Exception
     */
    protected function setupAttributes(Collection $attributeCollection): void
    {
        /** @var Attribute $attribute */
        foreach ($attributeCollection as $attribute) {
            $this->setupAttribute($attribute);
        }
    }

    /**
     * @throws Exception
     */
    protected function setupAttribute(Attribute $attribute): void
    {
        $attributeId = $attribute->getId();
        $attributeCode = $attribute->getAttributeCode();
        $eavAttributeId = $attribute->getEavAttributeId();
        $customField = $attribute->getCustomField();
        $fixedValue = $attribute->getFixedValue();
        $characterData = $attribute->getCharacterData();
        $stripTags = $attribute->getStripTags();
        $valueFormat = $attribute->getValueFormat();

        $this->attributeCodes[ $attributeId ] = $attributeCode;

        if ($eavAttributeId) {
            $this->addEavAttribute(
                $attributeId,
                $this->variables->intValue($eavAttributeId)
            );
        }

        if ($customField) {
            $this->addCustomFieldAttribute(
                $attributeId,
                $customField
            );
        }

        if ($fixedValue) {
            $this->addFixedValueAttribute(
                $attributeId,
                $fixedValue
            );
        }

        if ($attribute->isProduct()) {
            $this->addProductAttribute($attributeId);
        }

        if ($attribute->isVariant()) {
            $this->addVariantAttribute($attributeId);
        }

        if ($characterData) {
            $this->characterDataAttributes[ $attributeId ] = $attribute->getAttributeCode();
        }

        if ($stripTags) {
            $this->stripTagsAttributeCodes[ $attributeId ] = $attribute->getAttributeCode();
        }

        if ($valueFormat) {
            $this->valueFormats[ $attributeId ] = $valueFormat;
        }
    }

    protected function getProductAttributeCodes(): array
    {
        $productAttributeCodes = [];

        foreach ($this->attributeCodes as $attributeId => $attributeCode) {
            if ($this->isProductAttribute($attributeId)) {
                $productAttributeCodes[ $attributeId ] = $attributeCode;
            }
        }

        return $productAttributeCodes;
    }

    protected function getVariantAttributeCodes(): array
    {
        $variantAttributeCodes = [];

        foreach ($this->attributeCodes as $attributeId => $attributeCode) {
            if ($this->isVariantAttribute($attributeId)) {
                $variantAttributeCodes[ $attributeId ] = $attributeCode;
            }
        }

        return $variantAttributeCodes;
    }

    /**
     * @return string[]
     */
    public function getRequiredEavAttributeCodes(): array
    {
        return $this->requiredEavAttributeCodes;
    }

    protected function getEavAttributeCode(int $attributeId): string
    {
        return $this->arrays->getValue(
            $this->eavAttributeCodes,
            $this->variables->stringValue($attributeId)
        );
    }

    /**
     * @throws Exception
     */
    protected function addEavAttribute(int $attributeId, int $eavAttributeId): void
    {
        $this->eavAttributeIds[ $attributeId ] = $eavAttributeId;

        $eavAttribute = $this->attributeHelper->getAttribute(
            Product::ENTITY,
            $this->variables->stringValue($eavAttributeId)
        );

        $this->eavAttributeCodes[ $attributeId ] = $eavAttribute->getAttributeCode();
        $this->requiredEavAttributeCodes[] = $eavAttribute->getAttributeCode();
    }

    protected function isEavAttribute(int $attributeId): bool
    {
        return array_key_exists(
            $attributeId,
            $this->eavAttributeIds
        );
    }

    protected function getCustomField(int $attributeId): AbstractCustom
    {
        return $this->arrays->getValue(
            $this->customFields,
            $this->variables->stringValue($attributeId)
        );
    }

    /**
     * @throws Exception
     */
    protected function addCustomFieldAttribute(int $attributeId, string $customField): void
    {
        $customFieldModel = $this->productFeedCustomHelper->getCustomFieldModel($customField);

        $this->customFields[ $attributeId ] = $customFieldModel;

        if ($customFieldModel !== false) {
            foreach ($customFieldModel->requireEavAttributeCodes() as $requireEavAttributeCode) {
                if (! $this->variables->isEmpty($requireEavAttributeCode)) {
                    $eavAttribute = $this->attributeHelper->getAttribute(
                        Product::ENTITY,
                        $requireEavAttributeCode
                    );

                    $this->requiredEavAttributeCodes[] = $eavAttribute->getAttributeCode();
                }
            }
        }
    }

    protected function isCustomField(int $attributeId): bool
    {
        return array_key_exists(
            $attributeId,
            $this->customFields
        );
    }

    /**
     * @return mixed
     */
    protected function getFixedValue(int $attributeId)
    {
        return $this->arrays->getValue(
            $this->fixedValues,
            $this->variables->stringValue($attributeId)
        );
    }

    protected function addFixedValueAttribute(int $attributeId, string $fixedValue)
    {
        $this->fixedValues[ $attributeId ] = $fixedValue;
    }

    protected function isFixedValue(int $attributeId): bool
    {
        return array_key_exists(
            $attributeId,
            $this->fixedValues
        );
    }

    protected function addProductAttribute(int $attributeId)
    {
        $this->productAttributes[ $attributeId ] = true;
    }

    protected function getProductAttributes(): array
    {
        return $this->productAttributes;
    }

    protected function isProductAttribute(int $attributeId): bool
    {
        return array_key_exists(
            $attributeId,
            $this->productAttributes
        );
    }

    protected function addVariantAttribute(int $attributeId): void
    {
        $this->variantAttributes[ $attributeId ] = true;
    }

    protected function isVariantAttribute(int $attributeId): bool
    {
        return array_key_exists(
            $attributeId,
            $this->variantAttributes
        );
    }

    protected function isStripTags(int $attributeId): bool
    {
        return array_key_exists(
            $attributeId,
            $this->stripTagsAttributeCodes
        );
    }

    protected function getValueFormat(int $attributeId)
    {
        return $this->arrays->getValue(
            $this->valueFormats,
            $this->variables->stringValue($attributeId)
        );
    }

    protected function getExportData(
        string $mode,
        array $attributes,
        int $storeId,
        array $productData,
        bool $isVariant,
        array $categoryPaths,
        array $urlRewrites,
        array $galleryImages,
        array $indexedPrices,
        array $stockItem,
        array $reviewSummary,
        array $children,
        array $bundled,
        array $grouped
    ): array {
        $exportData = [];

        foreach ($attributes as $attributeId => $attributeCode) {
            if ($this->canExportValue(
                $mode,
                $storeId,
                $isVariant,
                $attributeId
            )) {
                $exportData[ $attributeCode ] = $this->processExportValue(
                    $attributeId,
                    $storeId,
                    $productData,
                    $isVariant,
                    $categoryPaths,
                    $urlRewrites,
                    $galleryImages,
                    $indexedPrices,
                    $stockItem,
                    $reviewSummary,
                    $children,
                    $bundled,
                    $grouped
                );
            }
        }

        return $exportData;
    }

    protected function canExportValue(string $mode, int $storeId, bool $isVariant, int $attributeId): bool
    {
        return true;
    }

    protected function processExportValue(
        int $attributeId,
        int $storeId,
        array $productData,
        bool $isVariant,
        array $categoryPaths,
        array $urlRewrites,
        array $galleryImages,
        array $indexedPrices,
        array $stockItem,
        array $reviewSummary,
        array $children,
        array $bundled,
        array $grouped
    ): string {
        $exportValue = null;

        if ($this->isCustomField($attributeId)) {
            $customField = $this->getCustomField($attributeId);

            $exportValue = $this->processCustomField(
                $customField,
                $storeId,
                $productData,
                $isVariant,
                $categoryPaths,
                $urlRewrites,
                $galleryImages,
                $indexedPrices,
                $stockItem,
                $children,
                $bundled,
                $grouped
            );
        } elseif ($this->isFixedValue($attributeId)) {
            $exportValue = $this->getFixedValue($attributeId);
        } elseif ($this->isEavAttribute($attributeId)) {
            $eavAttributeCode = $this->getEavAttributeCode($attributeId);

            if (array_key_exists(
                $eavAttributeCode,
                $productData
            )) {
                $exportValue = $productData[ $eavAttributeCode ];
            }
        }

        $exportValue = $this->stripTags(
            $attributeId,
            $exportValue
        );

        return $this->formatValue(
            $attributeId,
            $exportValue
        );
    }

    protected function processCustomField(
        AbstractCustom $customField,
        int $storeId,
        array $productData,
        bool $isVariant,
        array $categoryPaths,
        array $urlRewrites,
        array $galleryImages,
        array $indexedPrices,
        array $stockItem,
        array $children,
        array $bundled,
        array $grouped
    ): array {
        return $customField->process(
            $storeId,
            $productData,
            $isVariant,
            $categoryPaths,
            $urlRewrites,
            $galleryImages,
            $indexedPrices,
            $stockItem,
            $children,
            $bundled,
            $grouped
        );
    }

    protected function stripTags(int $attributeId, $value)
    {
        if ($this->isStripTags($attributeId)) {
            if (is_scalar($value)) {
                $value = trim(strip_tags($value));

                $converted = strtr(
                    $value,
                    array_flip(
                        get_html_translation_table(
                            HTML_ENTITIES,
                            ENT_QUOTES
                        )
                    )
                );

                $value = preg_replace(
                    '/^[\s\x00]+|[\s\x00]+$/u',
                    '',
                    $converted
                );
            }
        }

        return $value;
    }

    protected function formatValue(int $attributeId, $value): string
    {
        $valueFormat = $this->getValueFormat($attributeId);

        if (! $this->variables->isEmpty($valueFormat)) {
            if (is_scalar($value)) {
                return @sprintf(
                    $valueFormat,
                    $value
                );
            }

            if (is_array($value) && array_key_exists(
                    'id',
                    $value
                ) && array_key_exists(
                    'value',
                    $value
                )) {
                $value[ 'value' ] = @sprintf(
                    $valueFormat,
                    $value[ 'value' ]
                );
            }
        }

        return $value;
    }
}
