<?php
require __DIR__ . '/vendor/autoload.php';

use \LINE\LINEBot;
use \LINE\LINEBot\HTTPClient\CurlHTTPClient;
use \LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
use \LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use \LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
use \LINE\LINEBot\SignatureValidator as SignatureValidator;

function replyone($input, $text, $httpClient, $bot, $event)
{
    if (strpos($input, 'anime') !== false) {
        $result = anime($text, $bot, $httpClient, $event);
    } elseif (strpos($input, 'search') !== false) {
        $result = search($text, $bot, $httpClient, $event);
    } else {
        $result = $bot->replyText($event['replyToken'], 'Pesan yang dikirimkan salah');
    }
    return $result;
}

function anime($text, $bot, $httpClient, $event)
{
    if ($text[0] == "anime") {
        $flex_template = file_get_contents("carousel_detail_anime.json");
        $data = json_decode($flex_template, true);
        $api = file_get_contents("https://api.jikan.moe/v3/anime/" . $text[1]);
        $data_api = json_decode($api, true);
        $title = $data_api['title'];
        $type = $data_api['type'];
        $source = $data_api['source'];
        $status = $data_api['status'];
        $genre = "";
        $opening = "";
        $ending = "";
        if ($data_api['premiered'] == NULL) {
            $premiered = "?";
        } else {
            $premiered = $data_api['premiered'];
        }
        if ($data_api['aired']['string'] == NULL) {
            $aired = "?";
        } else {
            $aired = $data_api['aired']['string'];
        }
        if ($data_api['score'] == NULL) {
            $score = "?";
        } else {
            $score = $data_api['score'];
        }
        foreach ($data_api['genres'] as $key) {
            $genre .= ", " . $key['name'];
        }
        $genre = substr($genre, 2);
        if (count((array)$data_api['opening_themes']) == 0) {
            $opening = "There is no opening theme yet";
        } else {
            foreach ($data_api['opening_themes'] as $key) {
                $opening .= "\n" . $key;
            }
            $opening = substr($opening, 1);
        }
        if (count((array)$data_api['ending_themes']) == 0) {
            $ending = "There is no ending theme yet";
        } else {
            foreach ($data_api['ending_themes'] as $key) {
                $ending .= "\n" . $key;
            }
            $ending = substr($ending, 1);
        }
        $duration = $data_api['duration'];
        if ($data_api['rating'] == NULL) {
            $rating = "?";
        } else {
            $rating = $data_api['rating'];
        }
        if ($data_api['synopsis'] == NULL) {
            $sinopsis = "There is no synopsis yet";
        } else {
            $sinopsis = $data_api['synopsis'];
        }
        if ($data_api['trailer_url'] == NULL) {
            $video = "There is no trailer yet";
            $data_video = file_get_contents("text_trailer.json");
            $data_teks = json_decode($data_video, true);
            $data_teks['text'] = $video;
            array_pop($data['contents'][4]['body']['contents']);
            array_push($data['contents'][4]['body']['contents'], $data_teks);
        } else {
            $video = $data_api['trailer_url'];
            $data_video = file_get_contents("tombol_trailer.json");
            $data_video2 = json_decode($data_video, true);
            $data_video2['action']['uri'] = $video;
            array_pop($data['contents'][4]['body']['contents']);
            array_push($data['contents'][4]['body']['contents'], $data_video2);
        }
        $data['contents'][0]['body']['contents'][0]['text'] = "Title: " . $title;
        $data['contents'][0]['body']['contents'][1]['text'] = "Type: " . $type;
        $data['contents'][0]['body']['contents'][2]['text'] = "Source: " . $source;
        $data['contents'][0]['body']['contents'][3]['text'] = "Status: " . $status;
        $data['contents'][0]['body']['contents'][4]['text'] = "Premiered: " . $premiered;
        $data['contents'][0]['body']['contents'][5]['text'] = "Aired: " . $aired;
        $data['contents'][0]['body']['contents'][6]['text'] = "Duration: " . $duration;
        $data['contents'][0]['body']['contents'][7]['text'] = "Genres: " . $genre;
        $data['contents'][0]['body']['contents'][8]['text'] = "Rating: " . $rating;
        $data['contents'][0]['body']['contents'][9]['text'] = "Score: " . $score;
        $data['contents'][1]['body']['contents'][0]['text'] = $sinopsis;
        $data['contents'][2]['body']['contents'][0]['text'] = $opening;
        $data['contents'][3]['body']['contents'][0]['text'] = $ending;
        //$data['contents'][4]['body']['contents'][0]['action']['uri'] = $video;
        $newflex = json_encode($data);
        file_put_contents("carousel_detail_anime.json", $newflex);
        $flex_template2 = file_get_contents("carousel_detail_anime.json");
        if ($api === FALSE) {
            $result = $bot->replyText($event['replyToken'], 'Pesan yang dikirimkan salah');
        } else {
            $result = $httpClient->post(LINEBot::DEFAULT_ENDPOINT_BASE . '/v2/bot/message/reply', [
                'replyToken' => $event['replyToken'],
                'messages'   => [
                    [
                        'type'     => 'flex',
                        'altText'  => 'Detail Anime',
                        'contents' => json_decode($flex_template2)
                    ]
                ],
            ]);
        }
    } else {
        $result = $bot->replyText($event['replyToken'], 'Pesan yang dikirimkan salah');
    }
    return $result;
}

function search($text, $bot, $httpClient, $event)
{
    if ($text[0] == "search") {
        //get from api
        //edit json
        $flex_template = file_get_contents("carousel_hasil_search.json");
        $flex_anime = file_get_contents("anime_template.json");
        $data = json_decode($flex_anime, true);
        $data_carousel = json_decode($flex_template, true);
        $query = urlencode($text[1]);
        $api = file_get_contents("https://api.jikan.moe/v3/search/anime?q=$query&limit=5");
        $data_api = json_decode($api, true);
        foreach ($data_api['results'] as $key) {
            $id = $key['mal_id'];
            $judul = $key['title'];
            $gambar = $key['image_url'];
            $sinopsis = $key['synopsis'];
            $data['footer']['contents'][0]['action']['displayText'] = "Anime:" . $id;
            $data['footer']['contents'][0]['action']['data'] = "Anime:" . $id;
            $data['header']['contents'][0]['text'] = $judul;
            $data['hero']['url'] = $gambar;
            if ($sinopsis == NULL) {
                $data['body']['contents'][0]['text'] = "There is no synopsis yet";
            } else {
                $data['body']['contents'][0]['text'] = $sinopsis;
            }

            array_push($data_carousel['contents'], $data);
        }
        $newflex = json_encode($data_carousel);
        file_put_contents("carousel_hasil_search2.json", $newflex);
        $flex_template2 = file_get_contents("carousel_hasil_search2.json");
        if (count((array)$data_api['results']) == 0) {
            $result = $bot->replyText($event['replyToken'], 'Hasil tidak ditemukan');
        } else {
            $result = $httpClient->post(LINEBot::DEFAULT_ENDPOINT_BASE . '/v2/bot/message/reply', [
                'replyToken' => $event['replyToken'],
                'messages'   => [
                    [
                        'type'     => 'flex',
                        'altText'  => 'Search Result',
                        'contents' => json_decode($flex_template2)
                    ]
                ],
            ]);
        }
    } else {
        $result = $bot->replyText($event['replyToken'], 'Pesan yang dikirimkan salah');
    }
    return $result;
}
