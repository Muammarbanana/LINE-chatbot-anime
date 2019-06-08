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
                       $text = explode(":",$input);
                       if(strpos($input,'anime') !== false ){
                           if($text[0] == "anime"){
                            //get from api
                            $api = file_get_contents("https://api.jikan.moe/v3/anime/".$text[1]);
                            $data_api = json_decode($api,true);
                            $judul = $data_api['title'];
                            $gambar = $data_api['image_url'];
                            $sinopsis = $data_api['synopsis'];
                            //edit json
                            $flex_template = file_get_contents("anime_template.json");
                            $data = json_decode($flex_template,true);
                            $data['header']['contents'][0]['text'] = $judul;
                            $data['hero']['url'] = $gambar;
                            $data['body']['contents'][0]['text'] = $sinopsis;
                            $newflex = json_encode($data);
                            file_put_contents("anime_template.json",$newflex);
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
                            $result = $bot->replyText($event['replyToken'], 'Pesan yang dikirimkan salah');
                           }    
                       }elseif(strpos($input,'search') !== false ){
                           if($text[0] == "search"){
                            //get from api
                            //edit json
                            $flex_template = file_get_contents("carousel_hasil_search.json");
                            $flex_anime = file_get_contents("anime_template.json");
                            $data = json_decode($flex_anime,true);
                            $data_carousel = json_decode($flex_template,true);
                            $query = urlencode($text[1]);
                            $api = file_get_contents("https://api.jikan.moe/v3/search/anime?q=$query&limit=5");
                            $data_api = json_decode($api,true);
                            foreach($data_api['results'] as $key){
                                $judul = $key['title'];
                                $gambar = $key['image_url'];
                                $sinopsis = $key['synopsis'];
                                $data['header']['contents'][0]['text'] = $judul;
                                $data['hero']['url'] = $gambar;
                                if($sinopsis == NULL){
                                    $data['body']['contents'][0]['text'] = "There is no synopsis yet";
                                }else{
                                    $data['body']['contents'][0]['text'] = $sinopsis;
                                }   
                                
                                array_push($data_carousel['contents'],$data);
                            }
                            $newflex = json_encode($data_carousel);
                            file_put_contents("carousel_hasil_search2.json",$newflex);
                            $flex_template2 = file_get_contents("carousel_hasil_search2.json");
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
                            $result = $bot->replyText($event['replyToken'], 'Pesan yang dikirimkan salah');
                           }
                       }else{
                        $result = $bot->replyText($event['replyToken'], 'Pesan yang dikirimkan salah');
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