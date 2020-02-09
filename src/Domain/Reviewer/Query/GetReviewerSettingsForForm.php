<?php
/**
 * 2007-2019 Friends of PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0).
 * It is also available through the world-wide-web at this URL: https://opensource.org/licenses/AFL-3.0
 */

namespace DemoManufHooksUsage\Domain\Reviewer\Query;

use PrestaShop\PrestaShop\Core\Domain\Manufacturer\ValueObject\ManufacturerId;

/**
 * Gets reviewer settings data ready for form display.
 *
 * @see \DemoManufHooksUsage\Domain\Reviewer\QueryHandler\GetReviewerSettingsForFormHandler how the data is retrieved.
 */
class GetReviewerSettingsForForm
{
    /**
     * @var ManufacturerId|null
     */
    private $manufacturerId;

    /**
     * @param int|null $manufacturerId
     */
    public function __construct($manufacturerId)
    {
        $this->manufacturerId = null !== $manufacturerId ? new ManufacturerId((int) $manufacturerId) : null;
    }

    /**
     * @return ManufacturerId|null
     */
    public function getManufacturerId()
    {
        return $this->manufacturerId;
    }
}
