<?php
/**
 * 2007-2019 Friends of PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0).
 * It is also available through the world-wide-web at this URL: https://opensource.org/licenses/AFL-3.0
 */

namespace DemoManufHooksUsage\Domain\Reviewer\QueryHandler;

use DemoManufHooksUsage\Domain\Reviewer\Query\GetReviewerSettingsForForm;
use DemoManufHooksUsage\Domain\Reviewer\QueryResult\ReviewerSettingsForForm;
use DemoManufHooksUsage\Repository\ReviewerRepository;

/**
 * Gets reviewer settings data ready for form display.
 */
class GetReviewerSettingsForFormHandler
{
    /**
     * @var ReviewerRepository
     */
    private $reviewerRepository;

    /**
     * @param ReviewerRepository $reviewerRepository
     */
    public function __construct(ReviewerRepository $reviewerRepository)
    {
        $this->reviewerRepository = $reviewerRepository;
    }

    public function handle(GetReviewerSettingsForForm $query)
    {
        if (null === $query->getManufacturerId()) {
            return new ReviewerSettingsForForm(false);
        }

        return new ReviewerSettingsForForm(
            $this->reviewerRepository->getIsAllowedToReviewStatus($query->getManufacturerId()->getValue())
        );
    }
}
