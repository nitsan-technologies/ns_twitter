<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'Nitsan.' . $_EXTKEY,
	'Recenttweets',
	array(
		'Tweet' => 'list',
		
	),
	// non-cacheable actions
	array(
		'Tweet' => '',
		
	)
);
