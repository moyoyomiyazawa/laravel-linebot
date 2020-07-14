<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\Event\MessageEvent\TextMessage;

class LineBotController extends Controller
{

    public function index()
    {
        return view('linebot.index');
    }

    public function parrot(Request $request)
    {
        // ログファイルに情報を出力
        Log::debug($request->header());
        Log::debug($request->input());

        $httpClient = new CurlHTTPClient(config('constants.line_access_token'));
        $lineBot = new LINEBot($httpClient, ['channelSecret' => config('constants.line_channel_secret')]);

        // 署名の検証
        $signature = $request->header('x-line-signature');
        if (!$lineBot->validateSignature($request->getContent(), $signature)) {
            abort(400, 'Invalid signature');
        }

        $events = $lineBot->parseEventRequest($request->getContent(), $signature);

        Log::debug($events);

        foreach ($events as $event) {
            if (!($event instanceof TextMessage)) {
                Log::debug('Non text message has come');
                continue;
            }

            $replyToken = $event->getReplyToken();
            $replyText = $event->getText();
            $lineBot->replyText($replyToken, $replyText);
        }
    }
}