<?php

namespace App\Http\Controllers;

use App\Services\Gurunavi;
use App\Services\RestaurantBubbleBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use LINE\LINEBot;
use LINE\LINEBot\Event\MessageEvent\TextMessage;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder\FlexMessageBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ContainerBuilder\CarouselContainerBuilder;

class LineBotController extends Controller
{

    public function index()
    {
        return view('linebot.index');
    }

    public function restaurants(Request $request)
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

            $gurunavi = new Gurunavi();
            $gurunaviResponse = $gurunavi->searchRestaurants($event->getText());

            if (array_key_exists('error', $gurunaviResponse)) {
                $replyText = $gurunaviResponse['error'][0]['message'];
                $replyToken = $event->getReplyToken();
                $lineBot->replyText($replyToken, $replyText);
                continue;
            }

            $bubbles = [];
            foreach ($gurunaviResponse['rest'] as $restaurant) {
                $bubble = RestaurantBubbleBuilder::builder();
                $bubble->setContents($restaurant);
                $bubbles[] = $bubble;
            }

            $carousel = CarouselContainerBuilder::builder();
            $carousel->setContents($bubbles);

            $flex = FlexMessageBuilder::builder();
            $flex->setAltText('飲食店検索結果');
            $flex->setContents($carousel);

            $lineBot->replyMessage($event->getReplyToken(), $flex);
        }
    }
}
