<?php
/**
 * 2007-2019 Friends of PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0).
 * It is also available through the world-wide-web at this URL: https://opensource.org/licenses/AFL-3.0
 */

namespace DemoManufHooksUsage\Controller\Admin;

use DemoManufHooksUsage\Domain\Reviewer\Command\ToggleIsAllowedToReviewCommand;
use DemoManufHooksUsage\Domain\Reviewer\Exception\CannotCreateReviewerException;
use DemoManufHooksUsage\Domain\Reviewer\Exception\CannotToggleAllowedToReviewStatusException;
use DemoManufHooksUsage\Domain\Reviewer\Exception\ReviewerException;
use PrestaShop\PrestaShop\Core\Domain\Manufacturer\Exception\ManufacturerException;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * This controller holds all custom actions which are added by extending "Sell > Manufacturers" page.
 *
 * @see https://devdocs.prestashop.com/1.7/modules/concepts/controllers/admin-controllers/ for more details.
 */
class ManufacturerReviewController extends FrameworkBundleAdminController
{
    /**
     * Catches the toggle action of manufacturer review.
     *
     * @param int $manufacturerId
     *
     * @return RedirectResponse
     */
    public function toggleIsAllowedForReviewAction($manufacturerId)
    {
        try {
            /*
             * This part demonstrates the usage of CQRS pattern command to perform write operation for Reviewer entity.
             * @see https://devdocs.prestashop.com/1.7/development/architecture/manuf/ for more detailed information.
             *
             * As this is our recommended approach of writing the data but we not force to use this pattern in modules -
             * you can use directly an entity here or wrap it in custom service class.
             */
            $this->getCommandBus()->handle(new ToggleIsAllowedToReviewCommand((int) $manufacturerId));

            $this->addFlash('success', $this->trans('Successful update.', 'Admin.Notifications.Success'));
        } catch (ReviewerException $e) {
            $this->addFlash('error', $this->getErrorMessageForException($e, $this->getErrorMessageMapping()));
        }

        return $this->redirectToRoute('admin_manufacturers_index');
    }

    /**
     * Gets error message mappings which are later used to display friendly user error message instead of the
     * exception message.
     *
     * @see https://devdocs.prestashop.com/1.7/development/architecture/domain-exceptions/ for more detailed explanation
     *
     * @return array
     */
    private function getErrorMessageMapping()
    {
        return [
            ManufacturerException::class => $this->trans(
                'Something bad happened when trying to get manufacturer id',
                'Modules.Demomanufhooksusage.Manufacturerreviewcontroller'
            ),
            CannotCreateReviewerException::class => $this->trans(
                'Failed to create reviewer',
                'Modules.Demomanufhooksusage.Manufacturerreviewcontroller'
            ),
            CannotToggleAllowedToReviewStatusException::class => $this->trans(
                'An error occurred while updating the status.',
                'Modules.Demomanufhooksusage.Manufacturerreviewcontroller'
            ),
        ];
    }
}
