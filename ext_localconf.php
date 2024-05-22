<?php
declare(strict_types=1);

defined('TYPO3_MODE') || defined('TYPO3') || die('Access denied.');

$typo3VersionArray = \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionStringToArray(
    \TYPO3\CMS\Core\Utility\VersionNumberUtility::getCurrentTypo3Version()
);

if ($typo3VersionArray['version_main'] >= 10) {
    $moduleClass = \Nitsan\NsTwitter\Controller\TweetController::class;
	$extensionName = 'NsTwitter';
} else {
    $moduleClass = 'Tweet';
	$extensionName = 'Nitsan.NsTwitter';
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	$extensionName,
	'Recenttweets',
	[
		$moduleClass => 'list',

	],
);

// @extensionScannerIgnoreLine
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
	'<INCLUDE_TYPOSCRIPT: source="FILE:EXT:ns_twitter/Configuration/TSconfig/ContentElementWizard.tsconfig">'
);


$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
$iconRegistry->registerIcon(
	'twitter-plugin',
	\TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
	['source' => 'EXT:ns_twitter/Resources/Public/Icons/ns_twitter.svg']
);