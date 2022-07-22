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

    private function updateKeyboard($keyboard,$choice){
        foreach($keyboard[0] as &$option) {
            $option['text'] = mb_substr($option['text'], 0, -1);

            $option['text'].= ($option['text'] != $choice) ? UNCHECKED : CHECKED ;
        }
        return $keyboard;
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
        $config = $this->config;
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
            $choice = $this->getAnswer($counter);
            if(isset($choice)){
                $sendData['keyboard'] = $this->updateKeyboard($sendData['keyboard'],$choice);
            }

            if($config['counter']>0)
                $navigation[] = array('text' => 'prev','callback_data'=> 'prev');
            if($config['counter']<$config['maxCount'])
                $navigation[] = array('text' => 'next','callback_data'=> 'next');
            if($config['counter']==$config['maxCount'])
                $navigation[] = array('text' => 'end','callback_data'=> 'end');

            $sendData['keyboard'][] = $navigation;

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


    public function editMessage(string $method, array $message, string $text, array $keyboard = null)
    {
        $data = [
            'text' => $text,
            'chat_id' => $message["chat"]["id"],
        ];

        if ($method == 'editMessageText'){
            $data['message_id'] = $message["message_id"];
        }
        if (isset($keyboard)) {
            $data['reply_markup'] = ['inline_keyboard' => $keyboard ];
        }


        $data = json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

        $curl = curl_init("https://api.telegram.org/bot$this->token/$method");

        return $this->sendPost($curl,$data);
    }
///////////////////////////////////////////////////////////////////////
    public function doWork(/*int $chat_id, string $message*/ ){


        $data = json_decode(file_get_contents("php://input"), TRUE);
        $dataBuf = $data['callback_query'] ?? $data['message'];
        $this->userId = $dataBuf['from']['id'];
        $this->config =$this->getConfig();

        $keyboard = array();

        if(isset($data['callback_query'])){

            $data = $data['callback_query'];


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
                case 'end':
                    $sendText = "FBI thank you for your cooperation";
                    $keyboard[0][0] =array('text' => 'restart','callback_data'=> 'restart');
                    break;
                case 'restart':
                    $questionData = $this->changeQuestion(-$this->getConfig()['maxCount']);
                    $sendText = $questionData['text'];
                    $keyboard = $questionData['keyboard'];
                    break;
                default:

                    $sendText = $data['message']['text'];
                    $keyboard = $data['message']['reply_markup']['inline_keyboard'];

                    $keyboard = $this->updateKeyboard($keyboard, $choice);

                   $this->setAnswer($this->config['counter'], $choice);

                    break;
            }
            $result = $this->editMessage('editMessageText',$data['message'],$sendText,$keyboard);
        }

        else{
            $text = mb_strtolower($data['message']['text']);

            switch($text){
                case '/start':
                    $questionData = $this->changeQuestion(0);
                    $sendText = $questionData['text'];
                    $keyboard = $questionData['keyboard'];
                    $this->editMessage('sendMessage',$data['message'],$sendText,$keyboard);

                    $sendText = "Hi there.\nYou have just started my quiz.\nPass or die.";
                    break;

                case '/help':
                    $sendText = "It's a simple bot that collect your data and send it to FBI";
                    break;

                default:
                    $sendText = "I don't understand";
                    break;
            }
            $result = $this->editMessage('sendMessage',$data['message'],$sendText);
        }
        return $result;
}




}
const CHECKED = '✅';
const UNCHECKED = '☑';
const TOKEN = '1640935985:AAEs2Rhj7tDizcSPWfiEZymS7IBhi7QuyHY';

$Bot = new Telegram(TOKEN);
$result = $Bot->doWork();

var_dump($result);


