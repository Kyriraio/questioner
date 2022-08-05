<?php
class File
{

    public static function getArrayOf($name)
    {
        return json_decode(file_get_contents(CHAT_ID."/$name"), 1);
    }

    public static function getQuestions()
    {
        return json_decode(file_get_contents(QUESTIONS), 1);
    }

    public static function getConfig()
    {//there should be the kind of exception
        return self::getArrayOf(CONFIG);
    }

    public static function getAnswers()
    {
        return self::getArrayOf(ANSWERS);
    }

    public static function update($name, $data)
    {
        file_put_contents(__DIR__.'/'.CHAT_ID."/$name", json_encode($data));
    }

    public static function updateConfig($config): void
    {
    self::update(CONFIG,$config);
    }

    public static function updateCurrentAnswer($data)
    {
        $answers = self::getAnswers();
        $id = self::getConfig()['counter'];
        $answers[$id] = $data;
        self::update(ANSWERS, $answers);
    }
}