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
 * used to update manufacturers review status.
 *
 * @see \DemoManufHooksUsage\Domain\Reviewer\CommandHandler\UpdateIsAllowedToReviewHandler how the data is handled.
 */
class UpdateIsAllowedToReviewCommand
{
    /**
     * @var ManufacturerId
     */
    private $manufacturerId;

    /**
     * @var bool
     */
    private $isAllowedToReview;

    /**
     * @param int $manufacturerId
     * @param bool $isAllowedToReview
     *
     * @throws ManufacturerException
     */
    public function __construct($manufacturerId, $isAllowedToReview)
    {
        $this->manufacturerId = new ManufacturerId($manufacturerId);
        $this->isAllowedToReview = $isAllowedToReview;
    }

    /**
     * @return ManufacturerId
     */
    public function getManufacturerId()
    {
        return $this->manufacturerId;
    }

    /**
     * @return bool
     */
    public function isAllowedToReview()
    {
        return $this->isAllowedToReview;
    }
}
