<?php
namespace Nitsan\NsTwitter\Controller;

use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use Nitsan\NsTwitter\Contrib\OAuthConsumer;
use Nitsan\NsTwitter\Contrib\OAuthToken;
use Nitsan\NsTwitter\Contrib\OAuthRequest;
use Nitsan\NsTwitter\Contrib\OAuthSignatureMethod_HMAC_SHA1;
/***************************************************************
*
*  Copyright notice
*
*  (c) 2017
*
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 3 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Core\Http\RequestFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;

/**
 * TweetController
 */
class TweetController extends ActionController
{
    /**
     * The base api url
     *
     * @var string
     */
    protected $api_url = 'https://api.twitter.com/1.1/';

    /**
     * action list
     *
     * @return void
     */
    public function listAction(): ResponseInterface
    {
        $settings = $this->settings;
        $limit = empty($settings['limit']) ? 5 : $settings['limit'];
        if (version_compare(GeneralUtility::makeInstance(Typo3Version::class)->getBranch(), '9.0', '>')) {
            $configuration = isset($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['ns_twitter']) ? $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['ns_twitter'] : '';
        } else {
            $configuration = isset($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['ns_twitter']) ? GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('ns_twitter') : '';
        }
        $this->setConsumer($configuration['key'], $configuration['secret']);
        $this->setToken($configuration['authkey'], $configuration['authtoken']);

        /** Set access tokens here - see: https://dev.twitter.com/apps/ **/
        if ($configuration['key'] !== '' && $configuration['secret'] && $configuration['authkey'] && $configuration['authtoken']) {
            $params['exclude_replies'] = $this->settings['exclude_replies'];
            $params['include_rts'] = $this->settings['include_rts']==1 ? 0 : 1;
            $params['tweet_mode'] = $this->settings['tweet_mode'];
            $params['count'] = $limit;
            try {
                if ($this->settings['mode'] == 'user') {
                    $params['screen_name'] = [
                        'screen_name' => urlencode($this->settings['username'])
                    ];
                    $path = 'statuses/user_timeline';
                    $params['include_entities'] = 'true';
                    $response = $this->connectAPI($path, 'GET', $params, $limit);
                    $tweets = json_decode($response, 1);
                } else {
                    $params['q'] = urlencode($this->settings['hashtag']);
                    $path = 'search/tweets';
                    $params['include_entities'] = 'true';
                    $response = $this->connectAPI($path, 'GET', $params, $limit);
                    $tweets = json_decode($response, 1);
                    $tweets = $tweets['statuses'];
                }

                if (isset($tweets['errors'])) {
                    throw new \Exception('Error : ' . $tweets['errors']['0']['message']);
                } else {
                    $result = '';
                    foreach ($tweets as $key => $value) {
                        $results[$key] = $value;
                        $createdDate = $value['created_at'];

                        // Check if this a retweet (helpful for Template)
                        $results[$key]['is_retweet'] = isset($value['retweeted_status']);

                        if ($this->settings['dateFormat'] == 'ago') {
                            $resultdate = $this->timeDifference($createdDate);
                        } else {
                            $resultdate = strtotime($createdDate);
                        }
                        // Keep raw text for use in template for plaintext
                        $results[$key]['text_raw'] = $results[$key]['text'];
                        // Store converted text
                        $results[$key]['text'] = $this->convert_links($results[$key]['text']);

                        if ($this->settings['tweet_mode'] == 'extended') {
                            if ($results[$key]['is_retweet']) {
                                // Keep raw for use in template for plaintext
                                $results[$key]['retweeted_status']['full_text_raw'] = $value['retweeted_status']['full_text'];
                                $results[$key]['retweeted_status']['full_text'] = $this->convert_links($results[$key]['retweeted_status']['full_text']);
                            }
                            $results[$key]['full_text_raw'] = $results[$key]['full_text'];
                            $results[$key]['full_text'] = $this->convert_links($results[$key]['full_text']);
                            $results[$key]['text'] = $results[$key]['full_text'];
                        }

                        $results[$key]['created_at'] = $resultdate;
                    }
                    if (!empty($results)) {
                        $this->view->assign('tweets', $results);
                    } else {
                        if ($this->settings['mode']=='user') {
                            $args[] = $this->settings['username'];
                        } else {
                            $args[] = $this->settings['hashtag'];
                        }
                        $this->addFlashMessage(LocalizationUtility::translate(
                            'tweet.empty',
                            'ns_twitter',
                            $args
                        ), '', AbstractMessage::ERROR);
                    }
                }
            } catch (\Exception $e) {
                $this->addFlashMessage($e->getMessage(), '', AbstractMessage::ERROR);
            }
        } else {
            $this->addFlashMessage(LocalizationUtility::translate('outhError', 'ns_twitter'), '', AbstractMessage::ERROR);
        }
        return $this->htmlResponse();
    }

    /**
     * Sets consumer based on key and secret
     *
     * @param string $key
     * @param string $secret
     * @return void
     */
    public function setConsumer($key, $secret)
    {
        $this->consumer = GeneralUtility::makeInstance(OAuthConsumer::class, $key, $secret);
    }

    /**
     * Sets token based on key and secret
     *
     * @param string $key
     * @param string $secret
     * @return void
     */
    public function setToken($key, $secret)
    {
        $this->token = GeneralUtility::makeInstance(OAuthToken::class, $key, $secret);
    }

    public function connectAPI($path, $method, $params, $limit)
    {
        $version = GeneralUtility::makeInstance(VersionNumberUtility::class);

        $versionNum = $version->getNumericTypo3Version();
        $explode = explode('.', $versionNum);
        $request = OAuthRequest::requestOauth($this->consumer, $this->token, $method, $this->api_url . $path . '.json', $params);
        $request->sendRequest(GeneralUtility::makeInstance(OAuthSignatureMethod_HMAC_SHA1::class), $this->consumer, $this->token);
        $url = $request->getUrl();
        if (version_compare(GeneralUtility::makeInstance(Typo3Version::class)->getBranch(), '8.0', '>')) {
            $client = GuzzleClientFactory::getClient();
            try {
                $response = $client->request($method, $url, [
                    'User-Agent' => 'TYPO3 Extension ns_twitter'
                ]);
                $apiResults = $response->getBody()->getContents();
                $statusCode = $response->getStatusCode();
            } catch (ClientException $e) {
                $errros = LocalizationUtility::translate('nstwitter.client.error', 'ns_twitter', [$e->getCode()]);
                throw new \Exception($errros);
            }
        } else {
            $apiRequest = GeneralUtility::makeInstance(
                'TYPO3\\CMS\\Core\\Http\\HttpRequest',
                $url
            );
            $apiResponse = $apiRequest->send();
            $apiResults = $apiResponse->getBody();
            $statusCode = $apiResponse->getStatus();
        }
        if (($statusCode === 200) || empty($apiResults)) {
            return $apiResults;
        } else {
            if ($this->settings['mode']=='user') {
                $args[] = $this->settings['username'];
            } else {
                $args[] = $this->settings['hashtag'];
            }
            $errros = LocalizationUtility::translate('tweet.empty', 'ns_twitter', $args);
            throw new \Exception($errros);
        }
    }

    public function timeDifference($createdDate)
    {

        // get current timestampt
        $current = strtotime('now');

        // get timestamp when tweet created
        $createdDate = strtotime($createdDate);

        // get difference
        $difference = $current - $createdDate;

        // calculate different time values
        $minute = 60;
        $hour = $minute * 60;
        $day = $hour * 24;
        $week = $day * 7;
        if (is_numeric($difference) && $difference > 0) {

            // if less then 3 seconds
            if ($difference < 3) {
                return LocalizationUtility::translate('rightnow', 'ns_twitter');
            }

            // if less then minute
            if ($difference < $minute) {
                return floor($difference) . LocalizationUtility::translate('seconds', 'ns_twitter');
            }

            // if less then 2 minutes
            if ($difference < $minute * 2) {
                return LocalizationUtility::translate('oneminute', 'ns_twitter');
            }

            // if less then hour
            if ($difference < $hour) {
                return floor($difference / $minute) . LocalizationUtility::translate('minute', 'ns_twitter');
            }

            // if less then 2 hours
            if ($difference < $hour * 2) {
                return LocalizationUtility::translate('onehour', 'ns_twitter');
            }

            // if less then day
            if ($difference < $day) {
                return floor($difference / $hour) . LocalizationUtility::translate('hours', 'ns_twitter');
            }

            // if more then day, but less then 2 days
            if ($difference > $day && $difference < $day * 2) {
                return LocalizationUtility::translate('yesterday', 'ns_twitter');
            }

            // if less then year
            if ($difference < $day * 365) {
                return floor($difference / $day) . LocalizationUtility::translate('days', 'ns_twitter');
            }

            // else return more than a year
            return 'Over a year ago';
        }
    }

    public function convert_links($status, $targetBlank = true, $linkMaxLen = 250)
    {

        // the target
        $target = $targetBlank ? ' target="_blank" ' : '';

        // convert link to url
        $status = preg_replace('/\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[A-Z0-9+&@#\/%=~_|]/i', '<a href="\0" target="_blank">\0</a>', $status);

        // convert @ to follow
        $status = preg_replace("/(@([_a-zA-Z0-9\-êàé-]+))/i", "<a href=\"https://twitter.com/$2\" title=\"Follow $2\" $target >$1</a>", $status);

        // convert # to search
        $status = preg_replace("/(#([_a-zA-Z0-9\-êàé-]+))/i", "<a href=\"https://twitter.com/search?q=$2\" title=\"Search $1\" $target >$1</a>", $status);

        // return the status
        return $status;
    }
}
