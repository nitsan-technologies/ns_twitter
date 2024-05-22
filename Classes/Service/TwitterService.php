<?php

declare(strict_types=1);

namespace Nitsan\NsTwitter\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
     * @var array
     */
    protected array $extConf;

    public function __construct()
    {
        $this->client = new Client();
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        $this->extConf = $extensionConfiguration->get('ns_twitter');
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
                    'Authorization' => "Bearer ".$this->extConf['bearertoken']
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
        if ($e->getCode() === 401) {
            return LocalizationUtility::translate('LLL:EXT:ns_twitter/Resources/Private/Language/locallang.xlf:api.error.401');
        }elseif($e->getCode() === 400 && str_contains($e->getMessage(), 'The `username` query parameter value')) {
            return LocalizationUtility::translate('LLL:EXT:ns_twitter/Resources/Private/Language/locallang.xlf:api.error.400');
        }else {
            return LocalizationUtility::translate('LLL:EXT:ns_twitter/Resources/Private/Language/locallang.xlf:api.error.401');
        }
    }
}
