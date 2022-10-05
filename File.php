<?php

class File
{

    public static function getArrayOf($name)
    {
        $path = "users/".CHAT_ID."/$name";
        return file_exists($path)
            ? json_decode(file_get_contents($path),1)
            : json_decode(file_get_contents(PATTERNS),1)[$name];
    }

    public static function getQuestions()
    {
        return json_decode(file_get_contents(QUESTIONS), 1);
    }

    public static function getConfig()
    {
        return self::getArrayOf(CONFIG);

    }

    public static function getAnswers()
    {
        return self::getArrayOf(ANSWERS);
    }

    public static function update($name, $data)
    {
        $path = __DIR__."\users\\".CHAT_ID;
        if (!is_dir($path) )
        mkdir($path,0777, true);

        file_put_contents($path."/$name", json_encode($data));
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
