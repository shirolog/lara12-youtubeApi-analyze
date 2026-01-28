<?php

namespace App\Http\Controllers;

use App\Services\YoutubeServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class YoutubeController extends Controller
{   

    protected $youtubeService;

    public function __construct(YoutubeServices $youtubeService)
    {
        $this->youtubeService = $youtubeService;
    }

    public function index() {

        return view('youtube.channel');
    }


    public function analyze(Request $request) {


        $validator = Validator::make($request->all(),[

            'channel_id' => 'required|string',
        ]);

        if($validator->fails()) {

            return response()->json([
                'errors' => $validator->errors(),
            ],422);
        }


        try{

            //チャンネル情報
            $channelData = $this->youtubeService->getChannelInfo($request->input('channel_id'));

            //動画一覧
            $videoes = $this->youtubeService->getTopVideoesByViews($channelData['id']);

            $allVideos = $this->youtubeService->getVideosForDurationAnalysis($channelData['id'], 50);

            //動画時間分析

            $durationStats = $this->youtubeService->analyzeDuration($allVideos);

            return response()->json([
                'status' => 'success',
                'message' => '分析が完了しました。',
                'data' => $channelData,
                'videoes' => [
                    'items' => $videoes,
                ],
                'durationStats' => $durationStats,
            ]);

        }catch(\Exception $e) {

            return response()->json([

                'status' => 'error',
                'message' => $e->getMessage(),
            ],500);
        }

    }
}
