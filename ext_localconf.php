<?php
if (!defined('TYPO3')) {
    die('Access denied.');
}

call_user_func(static function () {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'SfJoin',
        'Extbase',
        [
            \StefanFroemken\SfJoin\Controller\ProductController::class => 'listExtbase',
        ]
    );
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'SfJoin',
        'QueryBuilderExtbase',
        [
            \StefanFroemken\SfJoin\Controller\ProductController::class => 'listQueryBuilderExtbase',
        ]
    );
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'SfJoin',
        'QueryBuilderPlain',
        [
            \StefanFroemken\SfJoin\Controller\ProductController::class => 'listQueryBuilderPlain',
        ]
    );
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'SfJoin',
        'ContentObject',
        [
            \StefanFroemken\SfJoin\Controller\ProductController::class => 'listContentObject',
        ]
    );
});
