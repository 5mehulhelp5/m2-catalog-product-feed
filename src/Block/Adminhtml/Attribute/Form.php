<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

namespace Infrangible\CatalogProductFeed\Block\Adminhtml\Attribute;

use FeWeDev\Base\Arrays;
use Infrangible\CatalogProductFeed\Model\Config\Source\Attribute\Custom;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\Fieldset;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Infrangible\CatalogProductFeed\Model\Attribute;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class Form extends \Infrangible\BackendWidget\Block\Form
{
    /** @var Custom */
    protected $sourceCustomAttributes;

    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Arrays $arrays,
        \Infrangible\Core\Helper\Registry $registryHelper,
        \Infrangible\BackendWidget\Helper\Form $formHelper,
        Custom $sourceCustomAttributes,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $arrays,
            $registryHelper,
            $formHelper,
            $data
        );

        $this->sourceCustomAttributes = $sourceCustomAttributes;
    }

    protected function prepareFields(\Magento\Framework\Data\Form $form)
    {
        $fieldSet = $form->addFieldset(
            'attribute',
            [
                'legend' => __('Attribute Data')
            ]
        );

        $this->addTextField(
            $fieldSet,
            'attribute_code',
            __('Feed Attribute Code')->render(),
            true
        );
        $this->addEavAttributeField(
            $fieldSet,
            'eav_attribute_id',
            __('Product Attribute as Source')->render()
        );
        $this->addOptionsField(
            $fieldSet,
            'custom_field',
            __('Custom Field')->render(),
            $this->sourceCustomAttributes->toOptionArray(),
            null
        );
        $this->addTextField(
            $fieldSet,
            'fixed_value',
            __('Fixed Value')->render()
        );
        $this->addTextField(
            $fieldSet,
            'value_format',
            __('Value Format')->render()
        );
        $this->addYesNoField(
            $fieldSet,
            'is_product',
            __('Product Attribute')->render()
        );
        $this->addYesNoField(
            $fieldSet,
            'is_variant',
            __('Variant Attribute')->render()
        );
        $this->addYesNoField(
            $fieldSet,
            'use_for_delta',
            __('Delta-Index')->render()
        );
        $this->addYesNoField(
            $fieldSet,
            'character_data',
            __('Character Data')->render()
        );
        $this->addYesNoField(
            $fieldSet,
            'strip_tags',
            __('Strip Tags')->render()
        );

        $this->prepareAttributeFields(
            $form,
            $fieldSet
        );

        /** @var Attribute $attribute */
        $attribute = $this->getObject();

        $this->_eventManager->dispatch(
            'infrangible_catalogproductfeed_attribute_edit_form_prepare_form',
            ['form' => $form, 'attribute' => $attribute]
        );
    }

    abstract protected function prepareAttributeFields(
        \Magento\Framework\Data\Form $form,
        Fieldset $attributeDataFieldSet
    );
}
