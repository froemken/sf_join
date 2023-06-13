<?php

if (!defined('TYPO3')) {
    die('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'SfJoin',
    'Extbase',
    'Products by Extbase'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'SfJoin',
    'QueryBuilderExtbase',
    'Products by QueryBuilder Extbase'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'SfJoin',
    'QueryBuilderPlain',
    'Products by QueryBuilder Plain'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'SfJoin',
    'ContentObject',
    'Products by ContentObject'
);
