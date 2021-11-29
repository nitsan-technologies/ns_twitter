<?php
defined('TYPO3') || die('Access denied.');

$_EXTKEY = 'ns_twitter';

// Adding fields to the tt_content table definition in TCA
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('ns_twitter', 'Configuration/TypoScript', '[Nitsan] Twitter');
