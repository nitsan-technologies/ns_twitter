<?php
namespace Nitsan\NsTwitter\Hooks;

use TYPO3\CMS\Backend\View\PageLayoutViewDrawItemHookInterface;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

class PageLayoutView implements PageLayoutViewDrawItemHookInterface
{
    public function preProcess(\TYPO3\CMS\Backend\View\PageLayoutView &$parentObject, &$drawItem, &$headerContent, &$itemContent, array &$row)
    {
        $extKey = 'ns_twitter';

        if ($row['CType'] == 'list' && $row['list_type'] == 'nstwitter_recenttweets') {
            $drawItem = false;
            $headerContent = '';

            // template
            $view = $this->getFluidTemplate($extKey, 'Nstwitter');

            if (!empty($row['pi_flexform'])) {
                /** @var \TYPO3\CMS\Core\Service\FlexFormService $flexFormService */
                if (version_compare(GeneralUtility::makeInstance(Typo3Version::class)->getBranch(), '9.0', '>')) {
                    $flexFormService = GeneralUtility::makeInstance(FlexFormService::class);
                } else {
                    $flexFormService = GeneralUtility::makeInstance(FlexFormService::class);
                }
            }

            // assign all to view
            $view->assignMultiple([
                'data' => $row,
                'flexformData' => $flexFormService->convertFlexFormContentToArray($row['pi_flexform']),
            ]);

            // return the preview
            $itemContent = $parentObject->linkEditContent($view->render(), $row);
        }
    }

    /**
     * @param string $extKey
     * @param string $templateName
     * @return string the fluid template
     */
    protected function getFluidTemplate($extKey, $templateName)
    {
        // prepare own template
        $fluidTemplateFile = GeneralUtility::getFileAbsFileName('EXT:' . $extKey . '/Resources/Private/Backend/' . $templateName . '.html');
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename($fluidTemplateFile);
        return $view;
    }
}
