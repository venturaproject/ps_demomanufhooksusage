<?php
/**
 * 2007-2019 Friends of PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0).
 * It is also available through the world-wide-web at this URL: https://opensource.org/licenses/AFL-3.0
 */

namespace DemoManufHooksUsage\Domain\Reviewer\CommandHandler;

use DemoManufHooksUsage\Domain\Reviewer\Command\UpdateIsAllowedToReviewCommand;
use DemoManufHooksUsage\Domain\Reviewer\Exception\CannotToggleAllowedToReviewStatusException;
use DemoManufHooksUsage\Entity\Reviewer;
use DemoManufHooksUsage\Repository\ReviewerRepository;
use PrestaShopException;

/**
 * used to update manufacturers review status.
 */
class UpdateIsAllowedToReviewHandler extends AbstractReviewerHandler
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

    public function handle(UpdateIsAllowedToReviewCommand $command)
    {
        $reviewerId = $this->reviewerRepository->findIdByManufacturer($command->getManufacturerId()->getValue());

        $reviewer = new Reviewer($reviewerId);

        if (0 >= $reviewer->id) {
            $reviewer = $this->createReviewer($command->getManufacturerId()->getValue());
        }

        $reviewer->is_allowed_for_review = $command->isAllowedToReview();

        try {
            if (false === $reviewer->update()) {
                throw new CannotToggleAllowedToReviewStatusException(
                    sprintf('Failed to change status for reviewer with id "%s"', $reviewer->id)
                );
            }
        } catch (PrestaShopException $exception) {
            /*
             * @see https://devdocs.prestashop.com/1.7/development/architecture/domain-exceptions/
             */
            throw new CannotToggleAllowedToReviewStatusException(
                'An unexpected error occurred when updating reviewer status'
            );
        }
    }
}
