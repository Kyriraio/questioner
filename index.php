<?php
class Bot{
function Bot($token,$questions){
    $this->token = $token;
    $this->questions = $questions;
    $this->path ="api.telegram.org/bot$token";
}
function sendMessage($message){
    $url = "api.telegram.org/bot$this->token/sendMessage?chat_id=543510374&text=hi";
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_HEADER=>0,
        //CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $path.$method."?".http_build_query($data),
        CURLOPT_FOLLOWLOCATION =>1,
    ]);
    $result = curl_exec($curl);
    echo "Error CURL: " . curl_error($curl) . " \nError number: " . curl_errno($curl);

    curl_close($curl);
    return (json_decode($result, 1) ? json_decode($result, 1) : $result);
}
private $token,$path;
public $questions;
}
$token="1640935985:AAEs2Rhj7tDizcSPWfiEZymS7IBhi7QuyHY";
$path="api.telegram.org/bot$token/";
function sendTelegram($method,$data,$path,$headers = [])
{
    $url = 'https://api.telegram.org/bot1640935985:AAEs2Rhj7tDizcSPWfiEZymS7IBhi7QuyHY/sendMessage';
    $curl = curl_init();
   curl_setopt_array($curl, [
        CURLOPT_HEADER=>0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $url,
       CURLOPT_CUSTOMREQUEST=>"GET",
     /*  CURLOPT_POSTFIELDS =>http_build_query(array(
           "chat_id" => 543510374,
           "text" => "hello mafucka"
       ))*/
    ]);
    $result = curl_exec($curl);
   // echo "Error CURL: " . curl_error($curl) . " \nError number: " . curl_errno($curl);

    curl_close($curl);
    return (json_decode($result, 1) ? json_decode($result, 1) : $result);
}


$data = json_decode(file_get_contents("php://input"), TRUE);
if($data)
{

    $chatId = $data["message"]["chat"]["id"];
    $text = $data["message"]["text"];
    $send_data = array(
      "chat_id" => $chatId,
        "text" => "hello mafucka"
    );
    $send_data = json_encode($send_data);
    $res = sendTelegram('sendMessage',$send_data,$path);
    var_dump($res);
}
else{

    echo"Error: no input data";
}
