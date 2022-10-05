<?php
class PostFieldsBuilder
{
    public array $config;

    public string $text;

    public array $answers;
    public array $navigation;
    public array $keyboard;

    public string $method;

    public string $choice; 

    public function __construct()
    {
        $this->config = File::getConfig();
    }



    public function prepareDataUsingMessage($message) 
    {

        if($isCallback = isset($this->choice))
        {
            $this->method = 'editMessageText';

            switch($this->choice){
                case 'prev':
                    $this->config['counter']--;
                    $this->setFieldsByCounter();
                    break;

                case 'next':
                    $this->config['counter']++;
                    $this->setFieldsByCounter();
                    break;
                case 'end':
                    $this->text = "FBI thank you for your cooperation";
                    $this->addNavigation('restart');
                    break;
                case 'restart':
                    $this->config['counter'] = 0;
                    $this->setFieldsByCounter();
                    break;
                default:
                    $this->setFieldsByMessage($message);
                    break;
            }
            File::updateConfig($this->config);

        }

        else{

            $this->method = 'sendMessage';

            $text = mb_strtolower($message['text']);

            switch($text){
                case '/start':
                    $this->setFieldsByCounter();
                    $this->method = 'sendMessage';
                    break;

                case '/help':
                    $this->text =  "It's a simple bot that collect your data and send it to FBI";
                    break;

                default:
                    $this->text = "I don't understand";
                    break;
            }
        }
        $this->buildKeyboard();
    }

    protected function showAnswer() : void
    {
        foreach($this->answers as &$option) {
            $option['text'] = mb_substr($option['text'], 0, -1);

            $option['text'].= ($option['text'] != $this->choice) ? UNCHECKED : CHECKED ;
        }
    }

    public function addAnswer($text,$prefix="")
    {
        $this->answers[] = $this->addCallbackButton($text,$prefix);
    }
    public function addNavigation($text)
    {
        $this->navigation[] = $this->addCallbackButton($text);
    }

    public function addCallbackButton($text,$postfix="")
    {
        return array('text' => $text.$postfix, "callback_data" => $text);

    }

    public function setFieldsByCounter() : void
    {
        $counter = $this->config['counter'];
        $maxCount = $this->config['maxCount'];

        if($counter>$maxCount or $counter<0)
        {
            echo "There's no question with such index";
        }


        $questions = File::getQuestions();
        $question = $questions[$counter];

        foreach($question['answers'] as $id => $answer)
        {
            $this->addAnswer($answer,UNCHECKED);
        }


        $previousChoice = File::getAnswers()[$counter];
        if(isset($previousChoice))
        {
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

    private function setFieldsByMessage($message)
    {
        $this->text = $message['text'];

        $this->keyboard = $message['reply_markup']['inline_keyboard'];
        $this->answers = $this->keyboard[0];
        File::updateCurrentAnswer($this->choice);
        $this->showAnswer();
    }

    private function buildKeyboard()
    {
        if(empty($this->keyboard))
        {
            if(!empty($this->answers))
                $this->keyboard[] = $this->answers;
            if(!empty($this->navigation))
                $this->keyboard[] = $this->navigation;
        }
        else if (isset($this->answers))
            $this->keyboard[0] = $this->answers;
    }

    public function getPostFields($message) : array
    {

        $postFields = [
            'text' => $this->text,
            'chat_id' => CHAT_ID,
        ];

        if ($this->method == 'editMessageText'){
            $postFields['message_id'] = $message["message_id"];
        }
        if (!empty($this->keyboard)) {
            $postFields['reply_markup'] = ['inline_keyboard' => $this->keyboard ];
        }

        return $postFields;

    }


}
