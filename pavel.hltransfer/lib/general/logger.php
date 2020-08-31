<?php

namespace Pavel\HLTransfer;

class Logger {

    /** Формат данных */
    const DATA_TEXT = 1;
    const DATA_JSON = 2;


    /** Уровни логирования */
    const DEBUG     = 100;
    const ERROR     = 200;
    const WARNING   = 300;
    const NOTICE    = 400;


    /** Путь по умолчанию */
    const DEFAULT_FILE_PATH = '/log.log';
    /** Показывать время по умолчанию*/
    const DEFAULT_SHOW_TIME = true;
    /** Формат времени по умолчанию*/
    const DEFAULT_TIME_FORMAT = 'd-m-Y H:i:s';
    /** Тип данных по умолчанию */
    const DEFAULT_DATA_TYPE = self::DATA_TEXT;
    /** Уровень логирования по умолчанию */
    const DEFAULT_LOG_LEVEL = self::DEBUG;


    protected $filePath     = self::DEFAULT_FILE_PATH;
    protected $showTime     = self::DEFAULT_SHOW_TIME;
    protected $timeFormat   = self::DEFAULT_TIME_FORMAT;
    protected $dataType     = self::DEFAULT_DATA_TYPE;
    protected $logLevel     = self::DEFAULT_LOG_LEVEL;


    /**
     * Logger constructor
     * @param bool $path - путь файла для записи
     * @param bool $logLevel - уровень логирования (debug | notice | warning | error | 0+)
     * @param bool $dataType - формат данных для логирования (text | json)
     */
    public function __construct($path = false, $logLevel = false, $dataType = false) {
        if ($path) {
            $this->filePath = $path;
        }
        if ($logLevel && intval($logLevel) > 0) {
            $this->logLevel = intval($logLevel);
        }
        switch ($dataType) {
            case 1:
            case 2:
            case 'text':
            case 'json':
                $this->dataType = $dataType;
        }
        $this->filePath = $_SERVER['DOCUMENT_ROOT'] . $this->filePath;
    }


    /**
     * Установить путь файла для записи
     * @param $value
     * @return $this
     */
    public function setFile($value) {

        if ($value) {
            $this->filePath = $_SERVER['DOCUMENT_ROOT'] . $value;
        }

        return $this;
    }


    /**
     * Добавить/убрать дату в начале записи
     * @param $value
     * @return $this
     */
    public function setShowTime($value) {
        $param = !!$value;
        $this->showTime = $param;

        return $this;
    }


    /**
     * Установить формат даты
     * @param $value
     * @return $this
     */
    public function setTimeFormat($value) {
        $this->timeFormat = $value;

        return $this;
    }


    /**
     * Установить формат данных
     * @param $value
     * @return $this
     */
    public function setType($value) {

        switch ($value) {
            case 'json':
            case self::DATA_JSON:
                $value = self::DATA_JSON;
                break;
            case 'text':
            case self::DATA_TEXT:
                $value = self::DATA_TEXT;
                break;
            default:
                $value = self::DATA_TEXT;
        }

        $this->dateType = $value;

        return $this;
    }


    /**
     * Установить уровень логирования
     * @param bool $value
     * @return $this
     */
    public function setLogLevel($value = false) {
        $value = intVal($value);
        if ($value > 0) {
            $this->logLevel = $value;
        }

        return $this;
    }


    /**
     * Записать лог
     * @param $data
     * @param bool $jsonData
     * @param int $logLevel
     */
    public function add($data, $jsonData = false, $logLevel = self::DEBUG) {

        $logLevel = intval($logLevel);
        if ($logLevel >= $this->logLevel) {

            $preparedData = '';
            if ($this->showTime) {
                $preparedData .= date($this->timeFormat) . ' ';
            }

            switch ($this->dateType) {
                case 'json':
                case self::DATA_JSON:
                    $data = self::toJson($data);
                    break;
                case 'text':
                case self::DATA_TEXT:
                    break;
                default:
            }

            if ($jsonData) {
                $data .= '; JSON: ' . self::toJson($jsonData);
            }

            $preparedData .= $data . PHP_EOL;

            $this->writeToFile($this->filePath, $preparedData);
        }
    }


    /**
     * Записать в лог (уровень debug)
     * @param $data
     * @param $jsonData
     */
    public function debug($data, $jsonData = false) {
        $this->add($data, $jsonData, self::DEBUG);
    }


    /**
     * Записать в лог (уровень notice)
     * @param $data
     * @param $jsonData
     */
    public function notice($data, $jsonData = false) {
        $this->add($data, $jsonData, self::NOTICE);
    }


    /**
     * Записать в лог (уровень warning)
     * @param $data
     * @param $jsonData
     */
    public function warning($data, $jsonData = false) {
        $this->add($data, $jsonData, self::WARNING);
    }


    /**
     * Записать в лог (уровень error)
     * @param $data
     * @param $jsonData
     */
    public function error($data, $jsonData = false) {
        $this->add($data, $jsonData, self::ERROR);
    }


    /**
     * Записать в лог без проверки условий
     * @param $data
     * @param bool $path
     */
    public static function log($data, $path = false) {

        if(!$path) $path = self::DEFAULT_FILE_PATH;
        if(is_array($data) || is_object($data))
            $data = self::toJson($data);

        $text = date(self::DEFAULT_TIME_FORMAT) . ': ' . $data . PHP_EOL;

        file_put_contents($_SERVER['DOCUMENT_ROOT'] . $path, $text, FILE_APPEND);
    }


    /**
     * Метод записи в файл
     * @param $path
     * @param $data
     */
    protected function writeToFile($path, $data) {
        file_put_contents($path, $data, FILE_APPEND);
    }


    /**
     * Возвращает JSON представление данных
     * @param $data
     * @return string
     */
    protected static function toJson($data) {
        return json_encode($data, JSON_UNESCAPED_UNICODE );
    }
}
