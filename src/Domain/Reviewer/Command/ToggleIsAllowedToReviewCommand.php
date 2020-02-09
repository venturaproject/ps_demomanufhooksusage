<?php
/**
 * 2007-2019 Friends of PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0).
 * It is also available through the world-wide-web at this URL: https://opensource.org/licenses/AFL-3.0
 */

namespace DemoManufHooksUsage\Domain\Reviewer\Command;

use PrestaShop\PrestaShop\Core\Domain\Manufacturer\Exception\ManufacturerException;
use PrestaShop\PrestaShop\Core\Domain\Manufacturer\ValueObject\ManufacturerId;

/**
 * Used for toggling the manufacturer if is allowed to make a review.
 *
 * @see \DemoManufHooksUsage\Domain\Reviewer\CommandHandler\ToggleIsAllowedToReviewHandler how the data is handled.
 */
class ToggleIsAllowedToReviewCommand
{
    /**
     * @var ManufacturerId
     */
    private $manufacturerId;

    /**
     * @param int $manufacturerId
     *
     * @throws ManufacturerException
     */
    public function __construct($manufacturerId)
    {
        $this->manufacturerId = new ManufacturerId($manufacturerId);
    }

    /**
     * @return ManufacturerId
     */
    public function getManufacturerId()
    {
        return $this->manufacturerId;
    }
}
