<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/sf-join.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\SfJoin\Domain\Repository;

use StefanFroemken\SfJoin\Domain\Model\Product;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\Generic\Query;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbQueryParser;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Repo for products
 */
class ProductRepository extends Repository
{
    /**
     * In case of translation: sorting is horrible:
     * 1. Extbase fetches the translated records in correct order
     * 2. Extbase looks up l10n_parent and fetches record in default language
     * 3. Record in default language will be process with versionOL
     * 4. Record in default language will be translated (again)
     * All this, just to have correct order in translations
     *
     * @var array
     */
    protected $defaultOrderings = [
        'title' => QueryInterface::ORDER_ASCENDING
    ];

    protected ConfigurationManagerInterface $configurationManager;

    public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager): void
    {
        $this->configurationManager = $configurationManager;
    }

    public function findWithCategoryExtbase(): QueryResultInterface
    {
        $query = $this->createQuery();

        return $query
            ->matching(
                $query->logicalNot($query->equals('categories.uid', null))
            )
            ->execute();
    }

    public function findWithCategoryQueryBuilderExtbase(): QueryResultInterface
    {
        /** @var Query $query */
        $query = $this->createQuery();
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_sfjoin_domain_model_product');
        $queryBuilder
            ->select('p.*')
            ->from('tx_sfjoin_domain_model_product', 'p')
            ->join(
                'p',
                'sys_category_record_mm',
                'sc_mm',
                (string)$queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq(
                        'sc_mm.tablenames',
                        $queryBuilder->createNamedParameter('tx_sfjoin_domain_model_product')
                    ),
                    $queryBuilder->expr()->eq(
                        'sc_mm.fieldname',
                        $queryBuilder->createNamedParameter('categories')
                    ),
                    $queryBuilder->expr()->eq(
                        'sc_mm.uid_foreign',
                        $queryBuilder->quoteIdentifier('p.uid')
                    )
                )
            )
            ->where(
                $queryBuilder->expr()->isNotNull(
                    'sc_mm.uid_local'
                )
            );

        // Add FE restriction to select valid records respecting: hidden, deleted, starttime, endtime
        $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
        // BUT: It still selects records from ALL languages. It's YOUR turn to select records in default language
        // As we set QueryBuilder to Extbase Query the records will be translated for us.
        $queryBuilder->andWhere(
            $queryBuilder->expr()->lte(
                'sys_language_uid',
                $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
            ),
            $queryBuilder->expr()->eq(
                'l10n_parent',
                $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
            ),
        );

        // The FE Restiction does not reduce the products to configured PIDs
        // We have to set them on our own:
        $queryBuilder->andWhere(
            $queryBuilder->expr()->in(
                'pid',
                $queryBuilder->createNamedParameter($query->getQuerySettings()->getStoragePageIds(), Connection::PARAM_INT_ARRAY)
            )
        );

        // You have to add GROUP BY, else a product will be displayed as often as it is related to a category.
        // $queryBuilder->groupBy('p.uid');
        // BUT: This GROUP BY breaks on ONLY_FULL_GROUP_BY configured servers.

        // On ONLY_FULL_GROUP_BY configured servers you have to add ALL columns from SELECT part
        $queryBuilder->groupBy('p.uid', 'p.pid', 'p.tstamp', 'p.tstamp', 'p.crdate', 'p.deleted', 'p.hidden', 'p.starttime', 'p.endtime', 'p.sys_language_uid', 'p.l10n_parent', 'p.l10n_state', 'p.t3_origuid', 'p.l10n_diffsource', 'p.t3ver_oid', 'p.t3ver_wsid', 'p.t3ver_state', 'p.t3ver_stage', 'p.title', 'p.categories', 'p.properties');
        // BUT: Although we have a JOIN query, extbase still just replaces "*" with "COUNT(*)" instead of COUNT(DISTINCT table.uid).
        // Because of this known bug you get a COUNT result of categories FOR EACH product.
        // So, COUNT will return 3 for the first product with 3 related categories instead of the amount of product records.

        // This bug will also break pagination.
        // Because of this problem using the QueryBuilder is nearly useless

        return $query->statement($queryBuilder)->execute();
    }

    /**
     * This is a solution with just the TYPO3 QueryBuilder. No Extbase Statement!!!
     * I have tried to get the exact same result of method $this->findWithCategoryExtbase().
     * The products are also sorted after translation!!!
     * Keep in mind that getLanguageStatement was called two times. This is not
     * a problem in general, but as you see, this method is extremely long.
     * Additional feature: With this method also the categories are sorted.
     * BUT: It is up to you, to implement Pagination.
     *
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    public function findWithCategoryQueryBuilderPlain(): array
    {
        $query = $this->createQuery();
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_sfjoin_domain_model_product');
        $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
        $queryBuilder
            ->select('p.uid', 'p.l10n_parent')
            ->from('tx_sfjoin_domain_model_product', 'p');

        $result = $queryBuilder
            ->join(
                'p',
                'sys_category_record_mm',
                'sc_mm',
                (string)$queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq(
                        'sc_mm.tablenames',
                        $queryBuilder->createNamedParameter('tx_sfjoin_domain_model_product')
                    ),
                    $queryBuilder->expr()->eq(
                        'sc_mm.fieldname',
                        $queryBuilder->createNamedParameter('categories')
                    ),
                    $queryBuilder->expr()->eq(
                        'sc_mm.uid_foreign',
                        $queryBuilder->quoteIdentifier('p.uid')
                    )
                )
            )
            ->where(
                $queryBuilder->expr()->isNotNull(
                    'sc_mm.uid_local'
                )
            )
            ->andWhere(
                $queryBuilder->expr()->or(
                    ...$this->getLanguageStatement(
                    'tx_sfjoin_domain_model_product',
                    'p',
                    $query->getQuerySettings(),
                    $queryBuilder
                )
                )
            )
            ->andWhere(
                $queryBuilder->expr()->in(
                    'pid',
                    $queryBuilder->createNamedParameter($query->getQuerySettings()->getStoragePageIds(), Connection::PARAM_INT_ARRAY)
                )
            )
            ->groupBy('p.uid', 'p.l10n_parent')
            ->orderBy('p.title', 'ASC')
            ->executeQuery();

        $products = [];
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        while ($product = $result->fetchAssociative()) {
            $productInDefaultLanguage = $pageRepository->getRawRecord(
                'tx_sfjoin_domain_model_product',
                (int)($product['l10n_parent'] ?: $product['uid'])
            );
            $pageRepository->versionOL('tx_sfjoin_domain_model_product', $productInDefaultLanguage);
            $translatedProduct = $pageRepository->getLanguageOverlay('tx_sfjoin_domain_model_product', $productInDefaultLanguage);

            $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_category');
            $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
            $resultCategories = $queryBuilder
                ->select('sc.uid')
                ->from('sys_category', 'sc')
                ->join(
                    'sc',
                    'sys_category_record_mm',
                    'sc_mm',
                    (string)$queryBuilder->expr()->and(
                        $queryBuilder->expr()->eq(
                            'sc_mm.tablenames',
                            $queryBuilder->createNamedParameter('tx_sfjoin_domain_model_product')
                        ),
                        $queryBuilder->expr()->eq(
                            'sc_mm.fieldname',
                            $queryBuilder->createNamedParameter('categories')
                        ),
                        // We have to add uid AND l10n_parent, so that getLanguageStatement can work correct
                        $queryBuilder->expr()->or(
                            $queryBuilder->expr()->eq(
                                'sc_mm.uid_local',
                                $queryBuilder->quoteIdentifier('sc.uid')
                            ),
                            $queryBuilder->expr()->eq(
                                'sc_mm.uid_local',
                                $queryBuilder->quoteIdentifier('sc.l10n_parent')
                            )
                        )
                    )
                )
                ->where(
                    $queryBuilder->expr()->eq(
                        'sc_mm.uid_foreign',
                        $queryBuilder->createNamedParameter(
                            $translatedProduct['_LOCALIZED_UID'] ?: $translatedProduct['uid'],
                            Connection::PARAM_INT
                        )
                    )
                )
                ->andWhere(
                    $queryBuilder->expr()->or(
                        ...$this->getLanguageStatement(
                        'sys_category',
                        'sc',
                        $query->getQuerySettings(),
                        $queryBuilder
                    )
                    )
                )
                ->orderBy('sc.title', 'ASC')
                ->executeQuery();

            $translatedProduct['categories'] = [];
            while ($category = $resultCategories->fetchAssociative()) {
                $categoryInDefaultLanguage = $pageRepository->getRawRecord(
                    'sys_category',
                    (int)($category['l10n_parent'] ?: $category['uid'])
                );
                $pageRepository->versionOL('sys_category', $categoryInDefaultLanguage);
                $translatedCategory = $pageRepository->getLanguageOverlay('sys_category', $categoryInDefaultLanguage);
                $translatedProduct['categories'][] = $translatedCategory;
            }

            $products[] = $translatedProduct;
        }

        return $products;
    }

    public function findWithCategoryContentObject(): ObjectStorage
    {
        $query = $this->createQuery();
        $cObj = $this->configurationManager->getContentObject();

        $products = $cObj->getRecords(
            'tx_sfjoin_domain_model_product',
            [
                'pidInList' => implode(',', $query->getQuerySettings()->getStoragePageIds()),
                'selectFields' => 'tx_sfjoin_domain_model_product.uid, tx_sfjoin_domain_model_product.title',
                'max' => '15',
                'begin' => '0',
                'orderBy' => 'tx_sfjoin_domain_model_product.title ASC',
                'groupBy' => 'tx_sfjoin_domain_model_product.uid, tx_sfjoin_domain_model_product.pid, , tx_sfjoin_domain_model_product.t3ver_state, tx_sfjoin_domain_model_product.title',
                'join' => 'sys_category_record_mm ON {#sys_category_record_mm.tablenames} = "tx_sfjoin_domain_model_product" AND {#sys_category_record_mm.fieldname} = "categories" AND {#sys_category_record_mm.uid_foreign} = {#tx_sfjoin_domain_model_product.uid}',
                'where' => 'sys_category_record_mm.uid_local IS NOT NULL',
            ]
        );

        // COUNT works, as we have a fetched result set (array) instead of a query.

        // The products are translated which is OK.
        // BUT: The ORDER BY only sorts products in default language.
        // So, after translation the records are not sorted anymore

        $dataMapper = GeneralUtility::makeInstance(DataMapper::class);
        $categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);
        $objectStorage = new ObjectStorage();

        /** @var Product $product */
        foreach ($dataMapper->map(Product::class, $products) as $product) {
            $product->setCategories($categoryRepository->getAllCategoriesByProductUid(
                $product->_getProperty('_localizedUid') ?: $product->getUid()
            ));
            $objectStorage->attach($product);
        }

        return $objectStorage;
    }

    public function findWithCategoryQueryBuilderExtbaseSolution(): QueryResultInterface
    {
        /** @var Query $query */
        $query = $this->createQuery();
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_sfjoin_domain_model_product');
        $queryBuilder
            ->select('p.uid')
            ->from('tx_sfjoin_domain_model_product', 'p')
            ->join(
                'p',
                'sys_category_record_mm',
                'sc_mm',
                (string)$queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq(
                        'sc_mm.tablenames',
                        $queryBuilder->createNamedParameter('tx_sfjoin_domain_model_product')
                    ),
                    $queryBuilder->expr()->eq(
                        'sc_mm.fieldname',
                        $queryBuilder->createNamedParameter('categories')
                    ),
                    $queryBuilder->expr()->eq(
                        'sc_mm.uid_foreign',
                        $queryBuilder->quoteIdentifier('p.uid')
                    )
                )
            )
            ->where(
                $queryBuilder->expr()->isNotNull(
                    'sc_mm.uid_local'
                )
            );

        // The FE Restiction does not reduce the products to configured PIDs
        // We have to set them on our own:
        $queryBuilder->andWhere(
            $queryBuilder->expr()->in(
                'pid',
                $queryBuilder->createNamedParameter($query->getQuerySettings()->getStoragePageIds(), Connection::PARAM_INT_ARRAY)
            )
        );

        return $query->matching($query->in('uid', $queryBuilder->executeQuery()->fetchAllAssociative()))->execute();
    }

    /**
     * Extend Extbase Query with SELECT and GROUP BY
     */
    public function findWithCategoryExtbaseSolution(): array
    {
        $query = $this->createQuery();
        $queryParser = GeneralUtility::makeInstance(Typo3DbQueryParser::class);
        $query = $query->matching(
            $query->logicalNot($query->equals('categories.uid', null))
        );

        $queryBuilderWithAllKindsOfOverlayIncluded = $queryParser->convertQueryToDoctrineQueryBuilder($query);
        $queryBuilderWithAllKindsOfOverlayIncluded->select(
            'tx_sfjoin_domain_model_product.uid',
            'tx_sfjoin_domain_model_product.pid',
            'tx_sfjoin_domain_model_product.title'
        );
        $queryBuilderWithAllKindsOfOverlayIncluded->groupBy(
            'tx_sfjoin_domain_model_product.uid',
            'tx_sfjoin_domain_model_product.pid',
            'tx_sfjoin_domain_model_product.title'
        );
        $queryBuilderWithAllKindsOfOverlayIncluded->setMaxResults(15);
        $queryBuilderWithAllKindsOfOverlayIncluded->setFirstResult(0);

        return $queryBuilderWithAllKindsOfOverlayIncluded->executeQuery()->fetchAllAssociative();
    }

    private function getLanguageStatement(
        string                 $tableName,
        string                 $tableAlias,
        QuerySettingsInterface $querySettings,
        QueryBuilder           $queryBuilder
    ): array
    {
        if (empty($GLOBALS['TCA'][$tableName]['ctrl']['languageField'])) {
            return [];
        }

        // Select all entries for the current language
        // If any language is set -> get those entries which are not translated yet
        // They will be removed by \TYPO3\CMS\Core\Domain\Repository\PageRepository::getRecordOverlay if not matching overlay mode
        $languageField = $GLOBALS['TCA'][$tableName]['ctrl']['languageField'];

        $languageAspect = $querySettings->getLanguageAspect();

        $transOrigPointerField = $GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField'] ?? '';
        if (!$transOrigPointerField || !$languageAspect->getContentId()) {
            return [$queryBuilder->expr()->in(
                $tableAlias . '.' . $languageField,
                [$languageAspect->getContentId(), -1]
            )];
        }

        if (!$languageAspect->doOverlays()) {
            return [$queryBuilder->expr()->in(
                $tableAlias . '.' . $languageField,
                [$languageAspect->getContentId(), -1]
            )];
        }

        $defLangTableAlias = $tableAlias . '_dl';
        $defaultLanguageRecordsSubSelect = $queryBuilder->getConnection()->createQueryBuilder();
        $defaultLanguageRecordsSubSelect
            ->select($defLangTableAlias . '.uid')
            ->from($tableName, $defLangTableAlias)
            ->where(
                $defaultLanguageRecordsSubSelect->expr()->and(
                    $defaultLanguageRecordsSubSelect->expr()->eq($defLangTableAlias . '.' . $transOrigPointerField, 0),
                    $defaultLanguageRecordsSubSelect->expr()->eq($defLangTableAlias . '.' . $languageField, 0)
                )
            );

        $andConditions = [];
        // records in language 'all'
        $andConditions[] = $queryBuilder->expr()->eq($tableAlias . '.' . $languageField, -1);
        // translated records where a default language exists
        $andConditions[] = $queryBuilder->expr()->and(
            $queryBuilder->expr()->eq($tableAlias . '.' . $languageField, $languageAspect->getContentId()),
            $queryBuilder->expr()->in(
                $tableAlias . '.' . $transOrigPointerField,
                $defaultLanguageRecordsSubSelect->getSQL()
            )
        );
        if ($languageAspect->getOverlayType() === LanguageAspect::OVERLAYS_MIXED) {
            // returns records from current language which have a default language
            // together with not translated default language records
            $translatedOnlyTableAlias = $tableAlias . '_to';
            $queryBuilderForSubselect = $queryBuilder->getConnection()->createQueryBuilder();
            $queryBuilderForSubselect
                ->select($translatedOnlyTableAlias . '.' . $transOrigPointerField)
                ->from($tableName, $translatedOnlyTableAlias)
                ->where(
                    $queryBuilderForSubselect->expr()->and(
                        $queryBuilderForSubselect->expr()->gt($translatedOnlyTableAlias . '.' . $transOrigPointerField, 0),
                        $queryBuilderForSubselect->expr()->eq($translatedOnlyTableAlias . '.' . $languageField, $languageAspect->getContentId())
                    )
                );
            // records in default language, which do not have a translation
            $andConditions[] = $queryBuilder->expr()->and(
                $queryBuilder->expr()->eq($tableAlias . '.' . $languageField, 0),
                $queryBuilder->expr()->notIn(
                    $tableAlias . '.uid',
                    $queryBuilderForSubselect->getSQL()
                )
            );
        }

        return $andConditions;
    }

    private function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
