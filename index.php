<?php
    require __DIR__ . '/vendor/autoload.php';
     
    use \LINE\LINEBot;
    use \LINE\LINEBot\HTTPClient\CurlHTTPClient;
    use \LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
    use \LINE\LINEBot\MessageBuilder\TextMessageBuilder;
    use \LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
    use \LINE\LINEBot\SignatureValidator as SignatureValidator;
     
    // set false for production
    $pass_signature = true;
     
    // set LINE channel_access_token and channel_secret
    $channel_access_token = "aNRZPoy7ZLpEt2u3NdMWXjCD1NrnXh0RzgtHYgDmbaa/QnwY2qHBvxdLRmDfSKMXgBfYZG66G+bG1cJiaMtpRzXT7ubqdzW8pvH1Qjnc9uU+YA5IiuZBSTEUe14qgNF0e+qA32t3cEQ+QZFD+sg76QdB04t89/1O/w1cDnyilFU=";
    $channel_secret = "c7efc3240f4cb10261a76048d2669491";
     
    // inisiasi objek bot
    $httpClient = new CurlHTTPClient($channel_access_token);
    $bot = new LINEBot($httpClient, ['channelSecret' => $channel_secret]);
     
    $configs =  [
        'settings' => ['displayErrorDetails' => true],
    ];
    $app = new Slim\App($configs);
     
    // buat route untuk url homepage
    $app->get('/', function($req, $res)
    {
      echo "Welcome at Slim Framework";
    });
     
    // buat route untuk webhook
    $app->post('/webhook', function ($request, $response) use ($bot, $httpClient)
    {
        // get request body and line signature header
        $body        = file_get_contents('php://input');
        $signature = isset($_SERVER['HTTP_X_LINE_SIGNATURE']) ? $_SERVER['HTTP_X_LINE_SIGNATURE'] : '';
     
        // log body and signature
        file_put_contents('php://stderr', 'Body: '.$body);
     
        if($pass_signature === false)
        {
            // is LINE_SIGNATURE exists in request header?
            if(empty($signature)){
                return $response->withStatus(400, 'Signature not set');
            }
     
            // is this request comes from LINE?
            if(! SignatureValidator::validateSignature($body, $channel_secret, $signature)){
                return $response->withStatus(400, 'Invalid signature');
            }
        }
     
        // kode aplikasi nanti disini
        $data = json_decode($body, true);
        if(is_array($data['events'])){
            foreach ($data['events'] as $event)
            {
                if ($event['type'] == 'message')
                {
                    if(
                        $event['source']['type'] == 'group' or
                        $event['source']['type'] == 'room'
                      ){
                       //message from group / room              
                      } else {
                       //message from single user
                       $input = strtolower($event['message']['text']);
                       if(strpos($input,'anime') !== false ){
                           $text = explode(" ",$event['message']['text']);
                           $flex_template = file_get_contents("anime_template.json");
                           $data = json_decode($flex_template,true);
                           $data['header']['contents']['text'] = $text[1];
                           $newflex = json_encode($data);
                           file_put_contents("anime_template2.json",$newflex);
                           $flex_template2 = file_get_contents("anime_template.json");
                           $result = $httpClient->post(LINEBot::DEFAULT_ENDPOINT_BASE . '/v2/bot/message/reply', [
                            'replyToken' => $event['replyToken'],
                            'messages'   => [
                                [
                                    'type'     => 'flex',
                                    'altText'  => 'Test Flex Message',
                                    'contents' => json_decode($flex_template2)
                                ]
                            ],
                        ]);
                       }else{
                        $result = $bot->replyText($event['replyToken'], 'ini pesan dari single user');
                       }
                       // or we can use replyMessage() instead to send reply message
                       // $textMessageBuilder = new TextMessageBuilder($event['message']['text']);
                       // $result = $bot->replyMessage($event['replyToken'], $textMessageBuilder);
 
                       return $response->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());
                      }
                }
            } 
        }
     
    });
     
    $app->run();
?>