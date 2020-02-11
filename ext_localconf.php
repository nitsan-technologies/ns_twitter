<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Nitsan.ns_twitter',
    'Recenttweets',
    [
        'Tweet' => 'list',

    ],
    // non-cacheable actions
    [
        'Tweet' => '',

    ]
);
if (version_compare(TYPO3_branch, '7.0', '>')) {
    if (TYPO3_MODE === 'BE') {
        $icons = [
            'twitter-plugin' => 'twitter-plugin.svg',
        ];
        $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
        foreach ($icons as $identifier => $path) {
            $iconRegistry->registerIcon(
                $identifier,
                \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
                ['source' => 'EXT:ns_twitter/Resources/Public/Icons/' . $path]
            );
        }
    }
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']['ns_twitter']
        = \Nitsan\NsTwitter\Hooks\PageLayoutView::class;
