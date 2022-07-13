<?php  //first lets make rough bot with rough methods and no data validation , so I could get the point
session_start();


class Telegram{

    private string $token;

//я отказался от private $config. Такой подход обеспечивал не нужный мне быстрый доступ к конфигу между вызовами функций
//однако из-за этого появлялся новый уровень абстракции. Теперь я работаю напрямую с файлом. Мне не нужно лишнее хранилище
//в будущем придётся хранить все магические строки не в коде, а в конфиге.
//Почти наверняка нужен private $config... Дожили...
    private function getConfig()
    {//there should be the kind of exception
        return json_decode(file_get_contents("config.txt"),1);
    }

    private function setConfig($config)
    {
        file_put_contents('config.txt',json_encode($config));
    }

    public function __construct(string $token)
    {
        $this->token = $token;

    }


    public function sendMessage(/*int $chat_id, string $message*/ ){


        $data = json_decode(file_get_contents("php://input"), TRUE);
        $text = mb_strtolower($data['message']['text']);

        switch($text){
            case '/start':
                $sendData = [
                    'text' =>"Hi there.\nYou have just started my quiz.\nPass or die."
                ];
            break;

            case '/help':
                $sendData = [
                    'text' =>"It's a simple bot that collect your data and send it to FBI",
                ];
            break;

            default:
                $config = $this->getConfig();
                $counter = &$config['counter'];

                $questions = json_decode(file_get_contents("questions.txt"), 1);
                $question = $questions[$counter];

                if (!in_array($text,$question['answers'])) {//Проверка, введено ли что-то кроме ответа на пред вопрос.
                    //Если да, то повторно высылаем прошлый json с припиской.

                    $sendData = [
                        'text' =>"Please choose one of the given answers."
                    ];
                }
                else{
                    file_put_contents('answers.txt',$text."\n", FILE_APPEND);

                    $counter++;
                    if( $counter > $config['maxCount'])//infinity loop , because why not
                    {
                        $counter = 0;
                    }

                    $this->setConfig($config);

                    $question = $questions[$counter];

                    foreach($question['answers'] as $id => $answer)
                    {
                        $formatAnswers[$id]= array('text' => $answer,"callback_data" =>$answer);
                    }

                    $sendData = [
                        'text' => $question['title'],
                        'reply_markup'  => [
                            'inline_keyboard' => [
                                $formatAnswers
                            ]
                        ]
                    ];
                }
            break;
        }
        $sendData['chat_id'] = $data["message"]["chat"]["id"];


        $sendData = json_encode($sendData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $curl = curl_init("https://api.telegram.org/bot$this->token/sendMessage");
        curl_setopt_array($curl, [
            CURLOPT_HEADER=>0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER=>array('Content-Type: application/json'),
            CURLOPT_POSTFIELDS =>$sendData
        ]);
        //file_put_contents('lastSend.txt',$sendData);
    $result = curl_exec($curl);

        if($result === false)
        {
            echo 'Curl error: ' . curl_error($curl);
        }
        else
        {
            echo 'Operation completed without any errors';
        }
    curl_close($curl);



    return (json_decode($result, 1) ? json_decode($result, 1) : $result);
}




}
const TOKEN = '1640935985:AAEs2Rhj7tDizcSPWfiEZymS7IBhi7QuyHY';

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
