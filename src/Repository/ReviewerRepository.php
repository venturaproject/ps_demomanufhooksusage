<?php
/**
 * 2007-2019 Friends of PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0).
 * It is also available through the world-wide-web at this URL: https://opensource.org/licenses/AFL-3.0
 */

namespace DemoManufHooksUsage\Repository;

use Doctrine\DBAL\Connection;
use PDO;

class ReviewerRepository
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $dbPrefix;

    /**
     * @param Connection $connection
     * @param string $dbPrefix
     */
    public function __construct(Connection $connection, $dbPrefix)
    {
        $this->connection = $connection;
        $this->dbPrefix = $dbPrefix;
    }

    /**
     * Finds manufacturer id if such exists.
     *
     * @param int $manufacturerId
     *
     * @return int
     */
    public function findIdByManufacturer($manufacturerId)
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder
            ->select('`id_reviewer`')
            ->from($this->dbPrefix . 'demomanufhooksusage_reviewer')
            ->where('`id_manufacturer` = :manufacturer_id')
        ;

        $queryBuilder->setParameter('manufacturer_id', $manufacturerId);

        return (int) $queryBuilder->execute()->fetch(PDO::FETCH_COLUMN);
    }

    /**
     * Gets allowed to review status by manufacturer.
     *
     * @param int $manufacturerId
     *
     * @return bool
     */
    public function getIsAllowedToReviewStatus($manufacturerId)
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder
            ->select('`is_allowed_for_review`')
            ->from($this->dbPrefix . 'demomanufhooksusage_reviewer')
            ->where('`id_manufacturer` = :manufacturer_id')
        ;

        $queryBuilder->setParameter('manufacturer_id', $manufacturerId);

        return (bool) $queryBuilder->execute()->fetch(PDO::FETCH_COLUMN);
    }
}
