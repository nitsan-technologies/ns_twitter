<?php

declare(strict_types=1);

namespace Nitsan\NsTwitter\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

/**
 * TwitterService
 */
class TwitterService 
{
    const API_URL = 'https://api.twitter.com/2';
    
    /**
     * @var ?Client
     */
    protected ?Client $client = null;

    /**
     * @var int
     */
    protected int $t3Version;

    public function __construct()
    {
        $this->client = new Client();
        $typo3VersionArray = VersionNumberUtility::convertVersionStringToArray(
            VersionNumberUtility::getCurrentTypo3Version()
        );
        $this->t3Version = $typo3VersionArray['version_main'];
    }

    /**
     * @param string $userName
     * @return void
     */
    public function getUserProfile(string $userName)
    {
        $url = $this::API_URL.'/users/by/username/'.$userName;
        $params = [
            'user.fields' => 'profile_image_url,id'
        ];
        return $this->getRequestData($url, $params);
    }

    /**
     * @param integer $userId
     * @param integer $maxResult
     * @param string $exclude
     * @return array
     */
    public function getUserTweets(int $userId, int $maxResult, string $exclude = ''): array
    {
        $url = $this::API_URL.'/users'.'/'.$userId.'/tweets';
        $params = [
            'tweet.fields' => 'created_at',
            'max_results' => $maxResult
        ];
        if($exclude != '') {
            $params['exclude'] = $exclude;
        }
        return $this->getRequestData($url, $params);
    }

    /**
     * @param string $url
     * @param array $params
     * @return array
     */
    private function getRequestData(string $url, array $params): array
    {
        try {
            $response = $this->client->get($url, [
                'query' => $params,
                'headers' => [
                    'Authorization' => "Bearer ".$this->getExtCong()['bearertoken']
                ]
            ])->getBody()->getContents();

            $data = json_decode($response, true);
            if(isset($data['errors'])) {
                return [
                    'success' => false,
                    'message' => $data['errors'][0]['detail']
                ];
            }else {
                return [
                    'success' => true,
                    'data' => $data['data']
                ];
            }

        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'message' => $this->handleGuzzleError($e),
            ];
        }
    }

    /**
     * @param GuzzleException $e
     * @return string
     */
    private function handleGuzzleError(GuzzleException $e): string
    {
        if($this->t3Version == 8) {
            return LocalizationUtility::translate('LLL:EXT:ns_twitter/Resources/Private/Language/locallang.xlf:api.error.401');
        } else {
            if ($e->getCode() === 401) {
                return LocalizationUtility::translate('LLL:EXT:ns_twitter/Resources/Private/Language/locallang.xlf:api.error.401');
            }elseif($e->getCode() === 400 && preg_match($e->getMessage(), 'The `username` query parameter value')) {
                return LocalizationUtility::translate('LLL:EXT:ns_twitter/Resources/Private/Language/locallang.xlf:api.error.400');
            }else {
                return LocalizationUtility::translate('LLL:EXT:ns_twitter/Resources/Private/Language/locallang.xlf:api.error.401');
            }
        }
    }
    
    /**
     * @return array
     */
    private function getExtCong(): array
    {
        if ($this->t3Version > 8) {
            // @extensionScannerIgnoreLine
            $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
            return $extensionConfiguration->get('ns_twitter');
        } else {
            // @extensionScannerIgnoreLine
            return unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ns_twitter']);
        }
    }
}