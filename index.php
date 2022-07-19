<?php  //first lets make rough bot with rough methods and no data validation , so I could get the point
session_start();

//1.setConfig() Можно запихнуть в деструктор и вызвать всего 1 раз.
class Telegram{

    private string $token;
    private ?int $userId = null;
    private array $config;

//я отказался от private $config. Такой подход обеспечивал не нужный мне быстрый доступ к конфигу между вызовами функций
//однако из-за этого появлялся новый уровень абстракции. Теперь я работаю напрямую с файлом. Мне не нужно лишнее хранилище
//в будущем придётся хранить все магические строки не в коде, а в конфиге.
//Почти наверняка нужен private $config... Дожили...
    private function getConfig()
    {//there should be the kind of exception
        return json_decode(file_get_contents("{$this->userId}/config.txt"),1);
    }

    private function setConfig($config)
    {
        file_put_contents("{$this->userId}/config.txt",json_encode($config));
    }


    private function getAnswer($counter)
    {
        return json_decode(file_get_contents("{$this->userId}/answers.txt"),1)[$counter];
    }

    private function setAnswer($counter,$data)
    {
        $answers = json_decode(file_get_contents("{$this->userId}/answers.txt"),1);
        $answers[$counter] = $data;
        file_put_contents("{$this->userId}/answers.txt",json_encode($answers));
    }

    private function changeQuestion($counterShift)
    {
        $config = &$this->config;
        $counter = &$config['counter'];
        $counter+=$counterShift;
        $questions = json_decode(file_get_contents("{$this->userId}/questions.txt"), 1);



        if ($counter<=$config['maxCount'] && $counter>=0) {

            $this->setConfig($config);
            $this->config = $config;
            $question = $questions[$counter];
            foreach($question['answers'] as $id => $answer)
            {
                $sendData['keyboard'][0][$id]= array('text' => $answer.UNCHECKED,"callback_data" =>$answer);
            }

            $sendData['text'] = $question['title'];

            return $sendData;
        }
        else{
            echo "Wrong question id ";
            return 0;
        }

    }
    public function __construct(string $token)
    {
        $this->token = $token;


    }
    public function sendPost($curl,$data){
        curl_setopt_array($curl, [
            CURLOPT_HEADER=>0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER=>array('Content-Type: application/json'),
            CURLOPT_POSTFIELDS =>$data
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

    public function sendMessage(int $chat_id, string $text, array $keyboard = null)
    {
        $data = [
            'text' => $text,
            'chat_id' => $chat_id
        ];

        if (isset($keyboard)) {
            $data['reply_markup'] = ['inline_keyboard' => [$keyboard] ];
        }

        $data = json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

        $curl = curl_init("https://api.telegram.org/bot$this->token/sendMessage");
        return $this->sendPost($curl,$data);
    }

    public function editMessage(int $message_id, int $chat_id, string $text, array $keyboard = null)
    {
        $data = [
            'text' => $text,
            'chat_id' => $chat_id,
            'message_id' => $message_id
        ];

        if (isset($keyboard)) {
            $data['reply_markup'] = ['inline_keyboard' => $keyboard ];
        }

        $data = json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

        $curl = curl_init("https://api.telegram.org/bot$this->token/editMessageText");

        return $this->sendPost($curl,$data);
    }
///////////////////////////////////////////////////////////////////////
    public function doWork(/*int $chat_id, string $message*/ ){


        $data = json_decode(file_get_contents("php://input"), TRUE);


        $keyboard = array();

        if(isset($data['callback_query'])){

            $data =$data['callback_query'];

            $this->userId = $data['from']['id'];
            $this->config = $this->getConfig();



            switch($choice = $data['data']){
                case 'prev':
                    $questionData = $this->changeQuestion(-1);
                    $sendText = $questionData['text'];
                    $keyboard = $questionData['keyboard'];
                    break;

                case 'next':
                    $questionData = $this->changeQuestion(1);
                    $sendText = $questionData['text'];
                    $keyboard = $questionData['keyboard'];
                    break;
                default:

                    $sendText = $data['message']['text'];
                    $keyboard = $data['message']['reply_markup']['inline_keyboard'];
                    foreach($keyboard[0] as &$option) {
                        $option['text'] = mb_substr($option['text'], 0, -1);

                        $option['text'].= ($option['text'] != $choice) ? UNCHECKED : CHECKED ;
                    }

                   $this->setAnswer($this->config['counter'], $choice);

                    break;
            }

            $keyboard[1] = array();
            $config = $this->config;

           if($config['counter']>0)
            $keyboard[1][] = array('text' => 'prev','callback_data'=> 'prev');
           if($config['counter']<$config['maxCount'])
            $keyboard[1][] = array('text' => 'next','callback_data'=> 'next');
        }
        else{
            $text = mb_strtolower($data['message']['text']);

            switch($text){
                case '/start':
                    $sendText = "Hi there.\nYou have just started my quiz.\nPass or die.";
                    break;

                case '/help':
                    $sendText = "It's a simple bot that collect your data and send it to FBI";
                    break;

                default:


                    break;
            }
        }



        $chat_id = $data["message"]["chat"]["id"];
        $message_id = $data["message"]["message_id"];


         return $this->editMessage($message_id,$chat_id,$sendText,$keyboard);

}




}
const CHECKED = '✅';
const UNCHECKED = '☑';
const TOKEN = '1640935985:AAEs2Rhj7tDizcSPWfiEZymS7IBhi7QuyHY';

$Bot = new Telegram(TOKEN);
$result = $Bot->doWork();

var_dump($result);


