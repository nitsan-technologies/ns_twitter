<?php
namespace Nitsan\NsTwitter\Controller;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use Nitsan\NsTwitter\Service\TwitterService;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * TweetController
 */
class TweetController extends ActionController
{
    /**
     * action list
     *
     * @return void
     */
    public function listAction()
    {
        $twitterService = GeneralUtility::makeInstance(TwitterService::class);
        $userProfile = $twitterService->getUserProfile($this->settings['username']);

        if(isset($userProfile['success']) && $userProfile['success']) {

            $settings = $this->settings;
            $limit = empty($settings['limit']) ? 5 : $settings['limit'];
    
            $exclude = $results = [];
    
            if((int)$this->settings['include_rts'] == 1) {
                $exclude[] = 'retweets';
            }
    
            if((int)$this->settings['exclude_replies'] == 1) {
                $exclude[] = 'replies';
            }
    
            $params['include_rts'] = $this->settings['include_rts']==1 ? 0 : 1;
            $params['count'] = $limit;
    
            $tweets = $twitterService->getUserTweets(
                $userProfile['data']['id'],
                $limit,
                implode(',', $exclude)
            );

            if(isset($tweets['success']) && $tweets['success']) {

                foreach ($tweets['data'] as $key => $value) {
                    $results[$key] = $value;
                    $createdDate = $value['created_at'];
        
                    if ($this->settings['dateFormat'] == 'ago') {
                        $resultdate = $this->timeDifference($createdDate);
                    } else {
                        $resultdate = strtotime($createdDate);
                    }
                    // Keep raw text for use in template for plaintext
                    $results[$key]['text'] = isset($results[$key]['text']) ? $results[$key]['text'] : '';
                    $results[$key]['text_raw'] = $results[$key]['text'];
                    // Store converted text
                    $results[$key]['text'] = $this->convertLinks($results[$key]['text']);
        
                    $results[$key]['created_at'] = $resultdate;
                }
                $this->view->assignMultiple([
                    'userData' => $userProfile['data'],
                    'tweets' => $results,
                    'showNoTweet' => 1
                ]);
            } else {
                $this->addFlashMessage(
                    $tweets['message'], 
                    '', 
                    ContextualFeedbackSeverity::ERROR
                );
                $this->view->assign('showNoTweet', 0);
            }
    
        } else {
            $this->addFlashMessage(
                $userProfile['message'], 
                '',
                ContextualFeedbackSeverity::ERROR
            );
            $this->view->assign('showNoTweet', 0);
        }
        

        if ($this->getCurrentTypo3Version() >= 11) {
            return $this->htmlResponse();
        }
    }

    /**
     * @param string $createdDate
     * @return string
     */
    private function timeDifference(string $createdDate): string
    {
        // get current timestamp
        $current = time();

        // get timestamp when tweet created
        $createdDate = strtotime($createdDate);

        // get difference
        $difference = $current - $createdDate;

        // calculate different time values
        $minute = 60;
        $hour = $minute * 60;
        $day = $hour * 24;
        $year = $day * 365;

        if (!is_numeric($difference) || $difference <= 0) {
            return '';
        }

        if ($difference < 3) {
            return LocalizationUtility::translate('rightnow', 'ns_twitter');
        }

        if ($difference < $minute) {
            return floor($difference) . ' ' . LocalizationUtility::translate('seconds', 'ns_twitter');
        }

        if ($difference < 2 * $minute) {
            return LocalizationUtility::translate('oneminute', 'ns_twitter');
        }

        if ($difference < $hour) {
            return floor($difference / $minute) . ' ' . LocalizationUtility::translate('minutes', 'ns_twitter');
        }

        if ($difference < 2 * $hour) {
            return LocalizationUtility::translate('onehour', 'ns_twitter');
        }

        if ($difference < $day) {
            return floor($difference / $hour) . ' ' . LocalizationUtility::translate('hours', 'ns_twitter');
        }

        if ($difference < 2 * $day) {
            return LocalizationUtility::translate('yesterday', 'ns_twitter');
        }

        if ($difference < $year) {
            return floor($difference / $day) . ' ' . LocalizationUtility::translate('days', 'ns_twitter');
        }

        return 'Over a year ago';
    }

    /**
     * @param string $status
     * @return string
     */
    private function convertLinks(string $status): string
    {
        $patterns = [
            '/\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[A-Z0-9+&@#\/%=~_|]/i' => '<a href="\0" target="_blank">\0</a>',
            '/(@([_a-zA-Z0-9\-êàé-]+))/i' => '<a href="https://twitter.com/$2" title="Follow $2" target="_blank">$1</a>',
            '/(#([_a-zA-Z0-9\-êàé-]+))/i' => '<a href="https://twitter.com/search?q=$2" title="Search $1" target="_blank">$1</a>',
        ];
    
        foreach ($patterns as $pattern => $replacement) {
            $status = preg_replace($pattern, $replacement, $status);
        }
    
        return $status;
    }

    /**
     * @return int
     */
    private function getCurrentTypo3Version(): int
    {
        $typo3VersionArray = VersionNumberUtility::convertVersionStringToArray(
            VersionNumberUtility::getCurrentTypo3Version()
        );

        return $typo3VersionArray['version_main'];
    }
}
