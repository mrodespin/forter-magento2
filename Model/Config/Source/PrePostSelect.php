<?php

namespace Forter\Forter\Model\Config\Source;

/**
 * Class PrePostSelect
 * @package Forter\Forter\Model\Config\Source
 */
class PrePostSelect implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '1', 'label' => __('Auth pre paymernt')],
            ['value' => '2', 'label' => __('Auth post paymernt')]
        ];
    }
}
