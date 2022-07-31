<?php

//0. Если ничего не делать, ничего не заработает. Вот уж сюрприз!)
//1. Сейчас надо доделать класс sendMessage, в конце у класса Bot останется совсем ничего, конфиг, user_id и никаких данных
// для отправки сообщения.
//2. Решить вопрос с 3 методами, почти одинаковыми... Получается что мы всегда передаём объект sendMessage, вместе с ним
// передаём метод, дальше что-то придумываем. Если нереально сделать это красиво, то делаем три разные функции, делая ещё 1
// одновременно с ними, она будет объединять максимум общего среди них.
//3. На данный момент программа полностью работает, теперь ещё и с красивым кодом,
//  теперь надо добавить exception там где надо, вместо уёбищных echo 'error'
//4?. Можно попробовать обложить всё тестами.
//5. Скинуть всё рудику, чтобы всё-равно услышать, что код говно -_-.
//5.1 мб выделить функции работы с файлами, получения данных и обновления их в файлах в отдельный интерфейс.



class Bot extends Telegram
{

    private string $text ;
    private array $answers ;
    private array $navigation;
    private array $keyboard;
    private string $method;

    public function __construct($token)
    {
        parent::__construct($token);
    }
    public function sendChanges()
    {
        $this->buildMessageByWebhookData();

        $this->sendPost($this->method, $this->getPostFields());
    }


    protected function getPostFields() : array
    {

        $webhookMessage = $this->webhookData;
        $postFields = [
            'text' => $this->text,
            'chat_id' => $this->chatId,
        ];

        if ($this->method == 'editMessageText'){
            $postFields['message_id'] = $webhookMessage["message_id"];
        }
        if (!empty($this->keyboard)) {
            $postFields['reply_markup'] = ['inline_keyboard' => $this->keyboard ];
        }

        return $postFields;

    }

    protected function buildMessageByWebhookData() : void
    {
        if($this->isCallback){

            $this->method = 'editMessageText';
            $callbackQuery = $this->webhookData;

            switch($this->choice){
                case 'prev':
                    $this->changeCounterBy(-1);
                    $this->setSendDataByCounter();
                    break;

                case 'next':
                    $this->changeCounterBy(1);
                    $this->setSendDataByCounter();
                    break;
                case 'end':
                    $this->setText("FBI thank you for your cooperation");
                    $this->addNavigation('restart');
                    break;
                case 'restart':
                    $this->changeCounterBy(-$this->config['counter']);
                    $this->setSendDataByCounter();
                    break;
                default:

                   /* $this->setText($callbackQuery['text']);

                    $this->keyboard = $callbackQuery['reply_markup']['inline_keyboard'];
                    $this->updateAnswer();
                    $this->showAnswer();*/
                    $this->setSendDataByCounter();
                    break;
            }

            /*$result = $this->editMessage('editMessageText',$callbackBody['message'],$sendText,$keyboard);*/
        }

        else{

            $this->method = 'sendMessage';

            $text = mb_strtolower($this->webhookData['text']);

            switch($text){
                case '/start':
                    $this->setSendDataByCounter();
                    $this->method = 'sendMessage';
/*                    $this->editMessage('sendMessage',$callbackQuery['message'],$sendText,$keyboard);*/
                    break;

                case '/help':
                    $this->setText( "It's a simple bot that collect your data and send it to FBI");
                    break;

                default:
                    $this->setText( "I don't understand");
                    break;
            }

/*            $result = $this->editMessage('sendMessage',$callbackBody['message'],$sendText);*/
        }

        if(empty($this->keyboard))
        {
           if(!empty($this->answers))
               $this->keyboard[] = $this->answers;
           if(!empty($this->navigation))
               $this->keyboard[] = $this->navigation;
        }

    }


    protected function setText($text)
    {
        $this->text = $text;
    }
    protected function getText()
    {
        return $this->text;
    }
    protected function addAnswer($text,$prefix="")
    {
        $this->answers[] = $this->addCallbackButton($text,$prefix);
    }
    protected function addNavigation($text)
    {
        $this->navigation[] = $this->addCallbackButton($text);
    }

    private function addCallbackButton($text,$postfix="")
    {
        return array('text' => $text.$postfix, "callback_data" => $text);

    }
    protected function showAnswer() : void
    {
        foreach($this->answers as &$option) {
            $option['text'] = mb_substr($option['text'], 0, -1);

            $option['text'].= ($option['text'] != $this->choice) ? UNCHECKED : CHECKED ;
        }
    }

    private function setSendDataByCounter() : void
    {
        $maxCount = $this->getConfig()['maxCount'];
        $counter = $this->getConfig()['counter'];

        if($counter>$maxCount or $counter<0)
        {
            echo "There's no question with such index";
            //need exception
        }

        $questions = $this->getQuestionsFile();

        $question = $questions[$counter];


        foreach($question['answers'] as $id => $answer)
        {
            $this->addAnswer($answer,UNCHECKED);
        }

        $previousChoice = $this->getAnswersFile()[$counter];
        if(isset($previousChoice) and !isset($this->choice)){
            $this->choice = $previousChoice;
        }
        $this->showAnswer();

        if($counter>0)
            $this->addNavigation('prev');
        if($counter<$maxCount)
            $this->addNavigation('next');
        if($counter==$maxCount)
            $this->addNavigation('end');


        $this->text = $question['title'];

    }
    private function updateAnswer()
    {
        $counter = $this->getConfig()['counter'];
        $answers = $this->getAnswersFile();
        $answers[$counter] = $this->choice;
        $this->fileUpdate('answers.txt',$answers);
    }

}
class Telegram
{
    protected bool $isCallback;
    protected string $token;
    protected int $chatId ;
    protected array $config;
    protected array $webhookData;
    protected string $choice;


    protected function changeCounterBy($num): void
    {
        $this->config['counter'] += $num;
    }


    public function receiveWebhookData()
    {
        $webhookData = json_decode(file_get_contents("php://input"), 1);

        if(isset($webhookData['callback_query']))
        {
            $this->isCallback = true;
            $webhookData = $webhookData['callback_query'];
            $this->choice = $webhookData['data'];
        }
        else
        {
            $this->isCallback = false;
        }
        $this->webhookData = $webhookData['message'];
    }

    private function fileGetArray($name)
    {
        return json_decode(file_get_contents("{$this->getChatId()}/$name"), 1);
    }

    protected function fileUpdate($name, $data)
    {
        file_put_contents("C:\OpenServer\domains\questioner/{$this->getChatId()}/$name", json_encode($data));
    }

    private function setChatId($chatId)
    {
        $this->chatId = $chatId;
    }

    private function getChatId(): int
    {
        return $this->chatId;
    }

    protected function getQuestionsFile()
    {
        return $this->fileGetArray("questions.txt");
    }

    private function getConfigFile()
    {//there should be the kind of exception
        return $this->fileGetArray("config.txt");
    }

    private function updateConfigFile(): void
    {
        $this->fileUpdate('config.txt', $this->config);
    }

    protected function getConfig()
    {//there should be the kind of exception
        return $this->config;
    }

    private function setConfig($config)
    {
        $this->config = $config;
    }


    protected function getAnswersFile()
    {
        return $this->fileGetArray("answers.txt");
    }


    /*private function setWebHook($host)
    {
        $this->sendPost('setWebHook', ['url' => $host]);
    }*/
    public function __construct(string $token)
    {
        $this->token = $token;
        /* $this->setWebHook(HOST);*/
    }

    public function handleWebhook()
    {
        $this->receiveWebhookData();
        $this->setChatId($this->webhookData['chat']['id']);
        $this->setConfig($this->getConfigFile());
    }
    public function __destruct()
    {
        $this->updateConfigFile();
    }


    public function sendPost(string $method, array $postFields)
    {

        $curl = curl_init("https://api.telegram.org/bot$this->token/$method");

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

}
const CHECKED = '✅';
const UNCHECKED = '☑';
const TOKEN = '1640935985:AAEs2Rhj7tDizcSPWfiEZymS7IBhi7QuyHY';
const HOST = 'https://4c6a-176-60-4-137.eu.ngrok.io';

$bot = new Bot(TOKEN);
$bot->handleWebhook();
$bot->sendChanges();


//getConfig для получения $this->config ,updateConfigFile для put data into config file setConfig для обновления $this->config;


