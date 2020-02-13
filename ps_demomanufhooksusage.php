<?php
/**
 * 2007-2019 Friends of PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0).
 * It is also available through the world-wide-web at this URL: https://opensource.org/licenses/AFL-3.0
 */

use DemoManufHooksUsage\Domain\Reviewer\Command\UpdateIsAllowedToReviewCommand;
use DemoManufHooksUsage\Domain\Reviewer\Exception\CannotCreateReviewerException;
use DemoManufHooksUsage\Domain\Reviewer\Exception\CannotToggleAllowedToReviewStatusException;
use DemoManufHooksUsage\Domain\Reviewer\Exception\ReviewerException;
use DemoManufHooksUsage\Domain\Reviewer\Query\GetReviewerSettingsForForm;
use DemoManufHooksUsage\Domain\Reviewer\QueryResult\ReviewerSettingsForForm;
use Doctrine\DBAL\Query\QueryBuilder;
use PrestaShop\PrestaShop\Core\CommandBus\CommandBusInterface;
use PrestaShop\PrestaShop\Core\Domain\Manufacturer\Exception\ManufacturerException;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\ToggleColumn;
use PrestaShop\PrestaShop\Core\Grid\Definition\GridDefinitionInterface;
use PrestaShop\PrestaShop\Core\Grid\Filter\Filter;
use PrestaShop\PrestaShop\Core\Search\Filters\ManufacturerFilters;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use PrestaShopBundle\Form\Admin\Type\YesAndNoChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

/**
 * Class Ps_DemoManufHooksUsage demonstrates the usage of CQRS pattern and hooks.
 */
class Ps_DemoManufHooksUsage extends Module
{
    public function __construct()
    {
        $this->name = 'ps_demomanufhooksusage';
        $this->version = '1.0.0';
        $this->author = 'AV';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->getTranslator()->trans(
            'Demo is for Manufacturers',
            [],
            'Modules.Demomanufhooksusage.Admin'
        );

        $this->description =
            $this->getTranslator()->trans(
                'Help developers to understand how to create module using new hooks and apply best practices when using CQRS',
                [],
                'Modules.Demomanufhooksusage.Admin'
            );

        $this->ps_versions_compliancy = [
            'min' => '1.7.6.0',
            'max' => _PS_VERSION_,
        ];
    }

    /**
     * This function is required in order to make module compatible with new translation system.
     *
     * @return bool
     */
    public function isUsingNewTranslationSystem()
    {
        return true;
    }

    /**
     * Install module and register hooks to allow grid modification.
     *
     * @see https://devdocs.prestashop.com/1.7/modules/concepts/hooks/use-hooks-on-modern-pages/
     *
     * @return bool
     */
    public function install()
    {
        return parent::install() &&
            // Register hook to allow Manufacturer grid definition modifications.
            // Each grid's definition modification hook has it's own name. Hook name is built using
            // this structure: "action{grid_id}GridDefinitionModifier", in this case "grid_id" is "manufacturer"
            // this means we will be modifying "Sell > Manufacturers" page grid.
            // You can check any definition factory service in PrestaShop\PrestaShop\Core\Grid\Definition\Factory
            // to see available grid ids. Grid id is returned by `getId()` method.
            $this->registerHook('actionManufacturerGridDefinitionModifier') &&
            // Register hook to allow Manufacturer grid query modifications which allows to add any sql condition.
            $this->registerHook('actionManufacturerGridQueryBuilderModifier') &&
            // Register hook to allow overriding manufacturer form
            // this structure: "action{block_prefix}FormBuilderModifier", in this case "block_prefix" is "manufacturer"
            // {block_prefix} is either retrieved automatically by its type. E.g "ManufacturerType" will be "manufacturer"
            // or it can be modified in form type by overriding "getBlockPrefix" function
            $this->registerHook('actionManufacturerFormBuilderModifier') &&
            $this->registerHook('actionAfterCreateManufacturerFormHandler') &&
            $this->registerHook('actionAfterUpdateManufacturerFormHandler') &&
            $this->registerHook('displayManufUsageValue') &&
            $this->registerHook('displayProductListManufUsageValue') &&
            $this->installTables()
        ;
    }

    public function uninstall()
    {
        return parent::uninstall() && $this->uninstallTables();
    }

    /**
     * Hook allows to modify Manufacturers grid definition.
     * This hook is a right place to add/remove columns or actions (bulk, grid).
     *
     * @param array $params
     */
    public function hookActionManufacturerGridDefinitionModifier(array $params)
    {
        /** @var GridDefinitionInterface $definition */
        $definition = $params['definition'];

        $translator = $this->getTranslator();

        $definition
            ->getColumns()
            ->addAfter(
                'active',
                (new ToggleColumn('is_allowed_for_review'))
                    ->setName($translator->trans('Featured', [], 'Modules.Demomanufhooksusage.Admin'))
                    ->setOptions([
                        'field' => 'is_allowed_for_review',
                        'primary_field' => 'id_manufacturer',
                        'route' => 'ps_demomanufhooksusage_toggle_is_allowed_for_review',
                        'route_param_name' => 'manufacturerId',
                    ])
            )
        ;

        $definition->getFilters()->add(
            (new Filter('is_allowed_for_review', YesAndNoChoiceType::class))
            ->setAssociatedColumn('is_allowed_for_review')
        );
    }

    /**
     * Hook allows to modify Manufacturers query builder and add custom sql statements.
     *
     * @param array $params
     */
    public function hookActionManufacturerGridQueryBuilderModifier(array $params)
    {
        /** @var QueryBuilder $searchQueryBuilder */
        $searchQueryBuilder = $params['search_query_builder'];

        /** @var ManufacturerFilters $searchCriteria */
        $searchCriteria = $params['search_criteria'];

        $searchQueryBuilder->addSelect(
            'IF(dcur.`is_allowed_for_review` IS NULL,0,dcur.`is_allowed_for_review`) AS `is_allowed_for_review`'
        );

        $searchQueryBuilder->leftJoin(
            'm',
            '`' . pSQL(_DB_PREFIX_) . 'demomanufhooksusage_reviewer`',
            'dcur',
            'dcur.`id_manufacturer` = m.`id_manufacturer`'
        );

        if ('is_allowed_for_review' === $searchCriteria->getOrderBy()) {
            $searchQueryBuilder->orderBy('dcur.`is_allowed_for_review`', $searchCriteria->getOrderWay());
        }

        foreach ($searchCriteria->getFilters() as $filterName => $filterValue) {
            if ('is_allowed_for_review' === $filterName) {
                $searchQueryBuilder->andWhere('dcur.`is_allowed_for_review` = :is_allowed_for_review');
                $searchQueryBuilder->setParameter('is_allowed_for_review', $filterValue);

                if (!$filterValue) {
                    $searchQueryBuilder->orWhere('dcur.`is_allowed_for_review` IS NULL');
                }
            }
        }
    }

    /**
     * Hook allows to modify Manufacturers form and add additional form fields as well as modify or add new data to the forms.
     *
     * @param array $params
     */
    public function hookActionManufacturerFormBuilderModifier(array $params)
    {
        /** @var FormBuilderInterface $formBuilder */
        $formBuilder = $params['form_builder'];
        $formBuilder->add('is_allowed_for_review', SwitchType::class, [
            'label' => $this->getTranslator()->trans('Featured', [], 'Modules.Demomanufhooksusage.Admin'),
            'required' => false,
        ]);

        /**
         * @var CommandBusInterface
         */
        $queryBus = $this->get('prestashop.core.query_bus');

        /**
         * This part demonstrates the usage of CQRS pattern query to perform read operation from Reviewer entity.
         *
         * @see https://devdocs.prestashop.com/1.7/development/architecture/manuf/ for more detailed information.
         *
         * As this is our recommended approach of reading the data but we not force to use this pattern in modules -
         * you can use directly an entity here or wrap it in custom service class.
         *
         * @var ReviewerSettingsForForm
         */
        $reviewerSettings = $queryBus->handle(new GetReviewerSettingsForForm($params['id']));

        $params['data']['is_allowed_for_review'] = $reviewerSettings->isAllowedForReview();

        $formBuilder->setData($params['data']);
    }

    /**
     * Hook allows to modify Manufacturers form and add additional form fields as well as modify or add new data to the forms.
     *
     * @param array $params
     *
     * @throws ManufacturerException
     */
    public function hookActionAfterUpdateManufacturerFormHandler(array $params)
    {
        $this->updateManufacturerReviewStatus($params);
    }

    /**
     * Hook allows to modify Manufacturers form and add additional form fields as well as modify or add new data to the forms.
     *
     * @param array $params
     *
     * @throws ManufacturerException
     */
    public function hookActionAfterCreateManufacturerFormHandler(array $params)
    {
        $this->updateManufacturerReviewStatus($params);
    }

    /**
     * @param array $params
     *
     * @throws \PrestaShop\PrestaShop\Core\Module\Exception\ModuleErrorException
     */
    private function updateManufacturerReviewStatus(array $params)
    {
        $manufacturerId = $params['id'];
        /** @var array $manufacturerFormData */
        $manufacturerFormData = $params['form_data'];
        $isAllowedForReview = (bool) $manufacturerFormData['is_allowed_for_review'];

        /** @var CommandBusInterface $commandBus */
        $commandBus = $this->get('prestashop.core.command_bus');

        try {
            /*
             * This part demonstrates the usage of CQRS pattern command to perform write operation for Reviewer entity.
             * @see https://devdocs.prestashop.com/1.7/development/architecture/manuf/ for more detailed information.
             *
             * As this is our recommended approach of writing the data but we not force to use this pattern in modules -
             * you can use directly an entity here or wrap it in custom service class.
             */
            $commandBus->handle(new UpdateIsAllowedToReviewCommand(
                $manufacturerId,
                $isAllowedForReview
            ));
        } catch (ReviewerException $exception) {
            $this->handleException($exception);
        }
    }

    /**
     * Installs sample tables required for demonstration.
     *
     * @return bool
     */
    private function installTables()
    {
        $sql = '
            CREATE TABLE IF NOT EXISTS `' . pSQL(_DB_PREFIX_) . 'demomanufhooksusage_reviewer` (
                `id_reviewer` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_manufacturer` INT(10) UNSIGNED NOT NULL,
                `is_allowed_for_review` TINYINT(1) NOT NULL,
                PRIMARY KEY (`id_reviewer`)
            ) ENGINE=' . pSQL(_MYSQL_ENGINE_) . ' COLLATE=utf8_unicode_ci;
        ';

        return Db::getInstance()->execute($sql);
    }

    public function getManufacturerFeatured($id_manufacturer)
    {
       
        $row = Db::getInstance()->getRow(
            '
            SELECT ar.`is_allowed_for_review` AS reviewed 
            FROM ' . _DB_PREFIX_ . 'manufacturer m
            LEFT JOIN `' . _DB_PREFIX_ . 'demomanufhooksusage_reviewer` ar ON ( m.`id_manufacturer` = ar.`id_manufacturer`)
			WHERE ar.`id_manufacturer` = ' . (int) $id_manufacturer 
        );

        return isset($row['reviewed']) ? $row['reviewed'] : false;
    }

    public function hookDisplayManufUsageValue($params)
    {
      
        $id_product = (int) Tools::getValue('id_product');
        
        $product = new Product((int)Tools::getValue('id_product'), true, $this->context->language->id, $this->context->shop->id);
        $manufacturer = new Manufacturer((int) $product->id_manufacturer, $this->context->language->id);
        $featured = $this->getManufacturerFeatured((int)$product->id_manufacturer);
        $this->context->smarty->assign(array(
        'featured' =>  $featured,
        'manufacturer_name' =>  $manufacturer->name,

    ));

    return $this->context->smarty->fetch('module:ps_demomanufhooksusage/views/templates/hook/product.tpl');
}

public function hookDisplayProductListManufUsageValue($params)
{
    /** @var ProductLazyArray $product */
    $product = $params['product'];
    $id_product = (int)$params['product']['id'];
    $manufacturer = new Manufacturer((int)$params['product']['id_manufacturer'], $this->context->language->id);
    $featured = $this->getManufacturerFeatured((int)$params['product']['id_manufacturer']);
    $this->context->smarty->assign(array(
        'product' => $product,
        'product_id' => (int)$params['product']['id'],
        'featured' =>  $featured,
        'manufacturer_name' =>  $manufacturer->name,

    ));

    return $this->context->smarty->fetch('module:ps_demomanufhooksusage/views/templates/hook/product-list.tpl');
}
    /**
     * Uninstalls sample tables required for demonstration.
     *
     * @return bool
     */
    private function uninstallTables()
    {
        $sql = 'DROP TABLE IF EXISTS `' . pSQL(_DB_PREFIX_) . 'demomanufhooksusage_reviewer`';

        return Db::getInstance()->execute($sql);
    }

    /**
     * Handles exceptions and displays message in more user friendly form.
     *
     * @param ReviewerException $exception
     *
     * @throws \PrestaShop\PrestaShop\Core\Module\Exception\ModuleErrorException
     */
    private function handleException(ReviewerException $exception)
    {
        $exceptionDictionary = [
            CannotCreateReviewerException::class => $this->getTranslator()->trans(
                'Failed to create a record for manufacturer',
                [],
                'Modules.Demomanufhooksusage.Admin'
            ),
            CannotToggleAllowedToReviewStatusException::class => $this->getTranslator()->trans(
                'Failed to toggle is allowed to review status',
                [],
                'Modules.Demomanufhooksusage.Admin'
            ),
        ];

        $exceptionType = get_class($exception);

        if (isset($exceptionDictionary[$exceptionType])) {
            $message = $exceptionDictionary[$exceptionType];
        } else {
            $message = $this->getTranslator()->trans(
                'An unexpected error occurred. [%type% code %code%]',
                [
                    '%type%' => $exceptionType,
                    '%code%' => $exception->getCode(),
                ],
                'Admin.Notifications.Error'
            );
        }

        throw new \PrestaShop\PrestaShop\Core\Module\Exception\ModuleErrorException($message);
    }
}
