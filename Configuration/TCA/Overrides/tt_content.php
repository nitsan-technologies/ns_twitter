<?php

declare(strict_types=1);

defined('TYPO3_MODE') || defined('TYPO3') || die('Access denied.');

(static function () {

    $typo3VersionArray = \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionStringToArray(
        \TYPO3\CMS\Core\Utility\VersionNumberUtility::getCurrentTypo3Version()
    );
    
    if ($typo3VersionArray['version_main'] >= 13) {
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            'NsTwitter',
            'Recenttweets',
            'LLL:EXT:ns_twitter/Resources/Private/Language/locallang_db.xlf:wizardTitle',
            'twitter-plugin',
            'plugins',
            'LLL:EXT:ns_twitter/Resources/Private/Language/locallang_db.xlf:wizardDescription'
        );
    } else {
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            'NsTwitter',
            'Recenttweets',
            'LLL:EXT:ns_twitter/Resources/Private/Language/locallang_db.xlf:wizardTitle',
            'twitter-plugin'
        );
    }
    
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['nstwitter_recenttweets'] = 'recursive,select_key,pages';
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['nstwitter_recenttweets'] = 'pi_flexform';
    
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
        'nstwitter_recenttweets', 
        'FILE:EXT:ns_twitter/Configuration/FlexForms/RecentTweets.xml'
    );
    
})();
