<?php
defined('TYPO3_MODE') || die('Access denied.');

$_EXTKEY = 'ns_twitter';

// Adding fields to the tt_content table definition in TCA
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript', 'Nitsan Twitter');
