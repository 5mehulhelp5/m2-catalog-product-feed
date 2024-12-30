<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductFeed\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 *
 * @method string getAttributeId()
 * @method void setAttributeId(string $attributeId)
 * @method string getAttributeCode()
 * @method void setAttributeCode(string $attributeCode)
 * @method string getEavAttributeId()
 * @method void setEavAttributeId(string $eavAttributeId)
 * @method string getCustomField()
 * @method void setCustomField(string $customField)
 * @method string getFixedValue()
 * @method void setFixedValue(string $fixedValue)
 * @method string getValueFormat()
 * @method void setValueFormat(string $valueFormat)
 * @method string getPosition()
 * @method void setPosition(string $position)
 * @method string getIsProduct()
 * @method void setIsProduct(string $isProduct)
 * @method string getIsVariant()
 * @method void setIsVariant(string $isVariant)
 * @method string getUseForDelta()
 * @method void setUseForDelta(string $useForDelta)
 * @method string getCharacterData()
 * @method void setCharacterData(string $characterData)
 * @method string getStripTags()
 * @method void setStripTags(string $stripTags)
 * @method string getIntegration()
 * @method void setIntegration(string $integration)
 * @method string getCreatedAt()
 * @method void setCreatedAt(string $createdAt)
 * @method string getUpdatedAt()
 * @method void setUpdatedAt(string $updatedAt)
 */
class Attribute extends AbstractModel
{
    /** @var string */
    protected $_eventPrefix = 'infrangible_catalogproductfeed_attribute';

    protected function _construct()
    {
        $this->_init(ResourceModel\Attribute::class);
    }

    public function beforeSave(): Attribute
    {
        if ($this->isObjectNew()) {
            $this->setCreatedAt(gmdate('Y-m-d H:i:s'));
        }

        $this->setUpdatedAt(gmdate('Y-m-d H:i:s'));

        return parent::beforeSave();
    }

    public function isProduct(): bool
    {
        return boolval($this->getIsProduct());
    }

    public function isVariant(): bool
    {
        return boolval($this->getIsVariant());
    }

    public function useForDelta(): bool
    {
        return boolval($this->getUseForDelta());
    }
}
