<?php
/**
 * 2007-2019 Friends of PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0).
 * It is also available through the world-wide-web at this URL: https://opensource.org/licenses/AFL-3.0
 */

namespace DemoManufHooksUsage\Entity;

use PrestaShop\PrestaShop\Adapter\Entity\ObjectModel;

class Reviewer extends ObjectModel
{
    /**
     * @var int
     */
    public $id_manufacturer;

    /**
     * @var int
     */
    public $is_allowed_for_review;

    public static $definition = [
        'table' => 'demomanufhooksusage_reviewer',
        'primary' => 'id_reviewer',
        'fields' => [
            'id_manufacturer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'is_allowed_for_review' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
        ],
    ];
}
