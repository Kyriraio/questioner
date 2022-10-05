<?php

require_once'constants.php';
function sendPost(string $method, array $postFields)
{

    $curl = curl_init("https://api.telegram.org/bot".TOKEN."/$method");

    curl_setopt_array($curl, [
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_HTTPHEADER => array('Content-Type: application/json')
    ]);

    if (!empty($postFields)) {
        curl_setopt($curl,
            CURLOPT_POSTFIELDS,
            json_encode($postFields,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    $result = curl_exec($curl);

    if ($result === false) {
        echo 'Curl error: ' . curl_error($curl);
    } else {
        echo 'Operation completed without errors';
    }
    curl_close($curl);

    return (json_decode($result, 1) ? json_decode($result, 1) : $result);

}

$webhookData = json_decode(file_get_contents("php://input"), 1);

if(isset($webhookData['callback_query']))
{
    $isCallback = true;
    $webhookData = $webhookData['callback_query'];
    $choice = $webhookData['data'];
}
else
{
    $isCallback = false;
}
$webhookData = $webhookData['message'];

define('CHAT_ID',$webhookData['chat']['id']);
require_once 'File.php';
require_once'PostFieldsBuilder.php';

$postData = new PostFieldsBuilder();
if($isCallback) $postData->choice = $choice;

$postData->prepareDataUsingMessage($webhookData);
sendPost($postData->method,$postData->getPostFields($webhookData));

