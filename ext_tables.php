<?php
if (!defined('TYPO3')) {
    die('Access denied.');
}

 \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
     '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:ns_twitter/Configuration/TSconfig/ContentElementWizard.tsconfig">'
 );
