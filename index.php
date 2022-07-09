<?php  //first of all lets make rough bot with rough methods and no data validation , so I could get the point
session_start();


class Telegram{

    private $token;

    private $config;

    public function __construct(string $token)
    {
        $this->token = $token;
        $this->config =json_decode(file_get_contents("config.txt"),1);
    }


    public function sendMessage(/*int $chat_id, string $message*/ ){


        $data = json_decode(file_get_contents("php://input"), TRUE);
        $text = mb_strtolower($data['message']['text']);

        $counter = $this->config['counter'];
        $question = json_decode(file_get_contents("questions.txt"), 1)[$counter];

               /* foreach($question['answers'] as $answer)
                {
                    $formatAnswers.= "['text' => {$answer}],";
                }*/
        $sendData = [
            'text' => 'Что вы хотите заказать?',
            'reply_markup'  => [
                'resize_keyboard' => true,
                'keyboard' => [
                    [
                        ['text' => 'Яблоки'],
                        ['text' => 'Груши'],
                    ],
                    [
                        ['text' => 'Лук'],
                        ['text' => 'Чеснок'],
                    ]
                ]
            ]
        ];
        $sendData['chat_id'] = $data["message"]["chat"]["id"];


        file_put_contents('answers.txt',$text."\n", FILE_APPEND);
        $this->config['counter']++;
        file_put_contents('config.txt',json_encode($this->config));
        $chat_id = $data["message"]["chat"]["id"];

        $message = json_decode(file_get_contents("questions.txt"), 1)[$counter]["title"];
$sendData = json_encode($sendData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $curl = curl_init("https://api.telegram.org/bot$this->token/sendMessage");
        curl_setopt_array($curl, [
            CURLOPT_HEADER=>0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER=>array('Content-Type: application/json'),
            CURLOPT_POSTFIELDS =>$sendData
        ]);
    $result = curl_exec($curl);
        if(curl_exec($result) === false)
        {
            echo 'Curl error: ' . curl_error($result);
        }
        else
        {
            echo 'Operation completed without any errors';
        }
    curl_close($curl);



    return (json_decode($result, 1) ? json_decode($result, 1) : $result);
}

}
DEFINE('TOKEN','1640935985:AAEs2Rhj7tDizcSPWfiEZymS7IBhi7QuyHY');

$Bot = new Telegram(TOKEN);
$result = $Bot->sendMessage();
var_dump($result);

/*$token="1640935985:AAEs2Rhj7tDizcSPWfiEZymS7IBhi7QuyHY";
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
       CURLOPT_POSTFIELDS =>http_build_query(array(
           "chat_id" => 543510374,
           "text" => "hello mafucka"
       ))
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
}*/
