<?php
defined('TYPO3_MODE') or die();

$_EXTKEY = 'ns_twitter';

/***************
 * Plugin
 */
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'ns_twitter',
    'Recenttweets',
    'Recent Tweets'
);

$pluginSignature = str_replace('_', '', $_EXTKEY) . '_recenttweets';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'recursive,select_key,pages';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature, 'FILE:EXT:' . $_EXTKEY . '/Configuration/FlexForms/RecentTweets.xml');
