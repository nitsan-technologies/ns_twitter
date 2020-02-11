<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

 \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
     '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:ns_twitter/Configuration/TSconfig/ContentElementWizard.txt">'
 );
