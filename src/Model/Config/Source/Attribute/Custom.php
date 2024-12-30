<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductFeed\Model\Config\Source\Attribute;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Custom implements OptionSourceInterface
{
    /** @var \Infrangible\CatalogProductFeed\Helper\Custom */
    protected $productFeedCustomHelper;

    public function __construct(\Infrangible\CatalogProductFeed\Helper\Custom $productFeedCustomHelper)
    {
        $this->productFeedCustomHelper = $productFeedCustomHelper;
    }

    public function toOptions(): array
    {
        $options = [];

        $customFields = $this->productFeedCustomHelper->getCustomFieldData();

        foreach ($customFields as $value => $data) {
            if (is_array($data) && array_key_exists(
                    'label',
                    $data
                )) {
                $options[ $value ] = $data[ 'label' ];
            }
        }

        return $options;
    }

    public function toOptionArray(): array
    {
        $options = [['value' => '', 'label' => __('--Please Select--')]];

        foreach ($this->toOptions() as $value => $label) {
            $options[] = ['value' => $value, 'label' => $label];
        }

        return $options;
    }
}
