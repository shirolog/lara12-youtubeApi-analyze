<?php

namespace App\Services;

use DateInterval;
use Illuminate\Support\Facades\Http;

class YoutubeServices
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.youtube.key');
    }

    //チャンネルIdとチャンネル名の関連付け

    public function getChannelInfo($input)
    {
        if (str_starts_with($input, '@')) {
            $channelId = $this->getChannelIdFromHandle($input);
        } else {
            $channelId = $input;
        }

        if (!$channelId) {
            throw new \Exception('ハンドルからチャンネルIDを取得できませんでした。');
        }

        return $this->getYoutube($channelId);
    }

    public function getYoutube($channelId)
    {
        $url = 'https://www.googleapis.com/youtube/v3/channels';

        $response = Http::get($url, [
            'part' => 'snippet,statistics,contentDetails',
            'id' => $channelId,
            'key' => $this->apiKey,
        ]);

        if ($response->failed()) {
            throw new \Exception('YouTube APIへのリクエストに失敗しました。');
        }

        $data = $response->json();

        // itemsが空なら例外
        if (empty($data['items'])) {
            throw new \Exception('チャンネルが見つかりませんでした。');
        }

        return $data['items'][0] ?? null;
    }

    // ハンドル名 (@example) からチャンネルIDを取得

    public function getChannelIdFromHandle($handle)
    {
        $url = 'https://www.googleapis.com/youtube/v3/search';

        $response = Http::get($url, [
            'part' => 'snippet',
            'q' => $handle,
            'type' => 'channel',
            'key' => $this->apiKey,
        ]);

        if ($response->failed()) {
            throw new \Exception('チャンネル検索に失敗しました。');
        }

        $data = $response->json();

        return $data['items'][0]['id']['channelId'] ?? null;
    }

    public function getTopVideosByViews($channelId, $limit = 10)
    {
        $channel = $this->getYoutube($channelId);

        $uploadsId = $channel['contentDetails']['relatedPlaylists']['uploads'] ?? null;

        if (!$uploadsId) {
            return [];
        }

        $searchResponse = Http::get('https://www.googleapis.com/youtube/v3/playlistItems', [
            'part' => 'contentDetails',
            'playlistId' => $uploadsId,
            'maxResults' => 50,
            'key' => $this->apiKey,
        ]);

        if ($searchResponse->failed()) {
            throw new \Exception('動画一覧取得に失敗しました。');
        }

        $searchData = $searchResponse->json();

        if (empty($searchData['items'])) {
            throw new \Exception('動画が見つかりません。');
        }

        $videoIds = collect($searchData['items'])->pluck('contentDetails.videoId')->join(',');

        //videos APIで統計情報を取得
        $videoDetailUrl = 'https://www.googleapis.com/youtube/v3/videos';

        $videoResponse = Http::get($videoDetailUrl, [
            'part' => 'snippet,statistics,contentDetails',
            'id' => $videoIds,
            'key' => $this->apiKey,
        ]);

        if ($videoResponse->failed()) {
            throw new \Exception('動画詳細データの取得に失敗しました。');
        }

        $videoData = $videoResponse->json();

        if (empty($videoData['items'])) {
            throw new \Exception('動画詳細情報が取得できませんでした。');
        }

        //再生順に並べ替え

        return collect($videoData['items'])->filter(fn($v) => isset($v['statistics']['viewCount']))->sortByDesc(fn($v) => (int) $v['statistics']['viewCount'])->take($limit)->values()->all();
    }

    public function getVideosForDurationAnalysis($channelId, $limit = 50)
    {
        $channel = $this->getYoutube($channelId);

        $uploadsId = $channel['contentDetails']['relatedPlaylists']['uploads'] ?? null;

        if (!$uploadsId) {
            return [];
        }

        $searchResponse = Http::get('https://www.googleapis.com/youtube/v3/playlistItems', [
            'part' => 'contentDetails',
            'playlistId' => $uploadsId,
            'maxResults' => $limit,
            'key' => $this->apiKey,
        ]);

        if ($searchResponse->failed()) {
            throw new \Exception('動画一覧取得に失敗しました。');
        }

        $searchData = $searchResponse->json();

        if (empty($searchData['items'])) {
            throw new \Exception('動画が見つかりません。');
        }

        $videoIds = collect($searchData['items'])->pluck('contentDetails.videoId')->join(',');

        //videos APIで統計情報を取得
        $videoDetailUrl = 'https://www.googleapis.com/youtube/v3/videos';

        $videoResponse = Http::get($videoDetailUrl, [
            'part' => 'contentDetails',
            'id' => $videoIds,
            'key' => $this->apiKey,
        ]);

        if ($videoResponse->failed()) {
            throw new \Exception('動画詳細データの取得に失敗しました。');
        }

        $videoData = $videoResponse->json();

        if (empty($videoData['items'])) {
            throw new \Exception('動画詳細情報が取得できませんでした。');
        }

        return $videoData['items'];
    }

    //秒数変換
    private function isoDurationToSeconds(string $duration)
    {
        $interval = new DateInterval($duration);

        return $interval->h * 3600 + $interval->i * 60 + $interval->s;
    }

    public function analyzeDuration(array $videos)
    {
        $result = [
            '3分未満' => 0,
            '3~10分' => 0,
            '10~30分' => 0,
            '30分~1時間' => 0,
            '1時間以上' => 0,
        ];

        foreach ($videos as $video) {
            $seconds = $this->isoDurationToSeconds($video['contentDetails']['duration']);

            switch (true) {
                case $seconds < 180:
                    $result['3分未満']++;
                    break;

                case $seconds < 600:
                    $result['3~10分']++;
                    break;

                case $seconds < 1800:
                    $result['10~30分']++;
                    break;

                case $seconds < 3600:
                    $result['30分~1時間']++;
                    break;

                default:
                    $result['1時間以上']++;
                    break;
            }
        }
        return $result;
    }
}
