<?php

namespace App\Services;

use GuzzleHttp\Client;

/**
 * ぐるなびAPIを扱うクラスです
 */
class Gurunavi
{
    private const RESTAURANTS_SEARCH_API_URL = 'https://api.gnavi.co.jp/RestSearchAPI/v3/';

    /**
     * 検索ワードを渡すと、ぐるなびAPIのレスポンスを配列で返します
     *
     * @param string $word
     * @return array
     */
    public function searchRestaurants(string $word): array
    {
        $client = new Client();
        $response = $client
            ->get(self::RESTAURANTS_SEARCH_API_URL, [
                'query' => [
                    'keyid' => config('constants.gurunabi_access_key'),
                    'freeword' => str_replace(' ', ',', $word),
                ],
                'http_errors' => false,
            ]);
        return json_decode($response->getBody()->getContents(), true);
    }
}
