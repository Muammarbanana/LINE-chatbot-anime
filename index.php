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
    $app->post('/webhook', function ($request, $response) use ($bot, $pass_signature)
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
     
    });
     
    $app->run();
?>