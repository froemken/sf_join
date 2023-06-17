<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/sf-join.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\SfJoin\Domain\Repository;

use StefanFroemken\SfJoin\Domain\Model\Category;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbQueryParser;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Repo for products
 */
class CategoryRepository extends Repository
{
    /**
     * @var array
     */
    protected $defaultOrderings = [
        'title' => QueryInterface::ORDER_ASCENDING,
    ];

    public function initializeObject()
    {
        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);
    }

    public function getAllCategoriesByProductUid(int $product): ObjectStorage
    {
        $query = $this->createQuery();

        $queryParser = GeneralUtility::makeInstance(Typo3DbQueryParser::class);
        $queryBuilder = $queryParser->convertQueryToDoctrineQueryBuilder($query);
        $queryBuilder
            ->join(
                'sys_category',
                'sys_category_record_mm',
                'sys_category_record_mm',
                (string)$queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq(
                        'sys_category_record_mm.tablenames',
                        $queryBuilder->createNamedParameter('tx_sfjoin_domain_model_product')
                    ),
                    $queryBuilder->expr()->eq(
                        'sys_category_record_mm.fieldname',
                        $queryBuilder->createNamedParameter('categories')
                    ),
                    $queryBuilder->expr()->or(
                        $queryBuilder->expr()->eq(
                            'sys_category_record_mm.uid_local',
                            $queryBuilder->quoteIdentifier('sys_category.uid')
                        ),
                        $queryBuilder->expr()->eq(
                            'sys_category_record_mm.uid_local',
                            $queryBuilder->quoteIdentifier('sys_category.l10n_parent')
                        )
                    )
                )
            )
            ->andWhere(
                $queryBuilder->expr()->eq(
                    'sys_category_record_mm.uid_foreign',
                    $queryBuilder->createNamedParameter($product, Connection::PARAM_INT)
                )
            );

        $objectStorage = new ObjectStorage();
        $dataMapper = GeneralUtility::makeInstance(DataMapper::class);
        $categories = $dataMapper->map(Category::class, $queryBuilder->executeQuery()->fetchAllAssociative());
        foreach ($categories as $category) {
            $objectStorage->attach($category);
        }

        return $objectStorage;
    }
}
