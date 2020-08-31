<?php

namespace Pavel\HLTransfer;

use \Bitrix\Main\Config\Option;

class Export {

    protected $filePath;
    protected $limit;
    protected $offset;
    protected $logger;


    /**
     * Export constructor
     * @param string $filePath
     * @param int|null $limit
     * @param int|null $offset
     */
    function __construct(string $filePath, ?int $limit = null, ?int $offset = null) {
        $this->filePath = $filePath;
        $this->offset = $offset ?? 0;
        $this->limit = $limit ?? 0;

        $this->loggerInit();
    }


    /**
     * Инициализация логера
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
    public function loggerInit() {
        $logType = Option::get(Helper::MODULE_ID, 'log_type', 'default');
        switch($logType) {
            case 'full': $logLevel = Logger::DEBUG; break;
            default: $logLevel = Logger::ERROR;
        }

        $this->logger = new Logger(Helper::EXPORT_LOG_PATH, $logLevel);
    }


    /**
     * Начало выгрузки
     * @return array
     */
    public function init() {

        $result = [
            'count' => intval(Export::getElementCount())
        ];

        $counter = &$_SESSION['HL_TRANSFER']['EXPORT']['COUNTER'];

        $this->logger->debug('Начало выгрузки');
        $this->removeOldFile();
        $counter = [
            'ELEMENT' => 0,
            'ERROR' => 0,
        ];

        return $result;
    }


    /**
     * Выполнить шаг выгрузки
     * @return array
     */
    public function next() {

        $result = [
            'status' => 'success',
            'cnt' => 0,
            'errorCount' => 0
        ];

        $counter = &$_SESSION['HL_TRANSFER']['EXPORT']['COUNTER'];

        try {

            $changeList = $this->loadChangeList();

            if(is_array($changeList) && count($changeList) > 0) {

                foreach ($changeList as $item) {
                    $result['cnt']++;
                    $serializedData = $this->serialize($item);
                    $this->write($serializedData);
                }
            }

        } catch(\Exception $e) {
            $result['status'] = 'error';
            $result['errorMessage'] = $e->getMessage();
            $result['errorCount']++;
            $this->logger->error('Ошибка: ' . $e->getMessage());
        }

        $counter['ELEMENT'] += $result['cnt'];
        $counter['ERROR'] += $result['errorCount'];

        return $result;
    }


    /**
     * Окончание выгрузки
     * @return array
     */
    public function finish() {

        $counter = &$_SESSION['HL_TRANSFER']['EXPORT']['COUNTER'];

        $result = [
            'statistic' => [
                'element' => $counter['ELEMENT'],
                'error' =>  $counter['ERROR'],
            ]
        ];

        $this->logger->debug(implode(PHP_EOL, [
            'Выгрузка завершена',
            'Экспортировано элементов: ' . $counter['ELEMENT'],
            'Количество ошибок: ' . $counter['ERROR'],
        ]));

        return $result;
    }


    /**
     * Получить элементы выгрузки для текущего шага
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    protected function loadChangeList() {
        return ChangesTable::getlist([
            'limit' => $this->limit,
            'offset' => $this->offset
        ])->fetchAll();
    }


    /**
     * Сериализация объекта
     * @param $data
     * @return string
     */
    protected function serialize($data) {
        return serialize($data);
    }


    /**
     * Запись в файл
     * @param $data
     * @throws \Exception
     */
    protected function write($data) {

        $r = file_put_contents($_SERVER['DOCUMENT_ROOT'] . $this->filePath, $data . PHP_EOL, FILE_APPEND);
        if($r === false) {
            throw new \Exception('Не удалось записать данные в файл');
        }
    }


    /**
     * Удалить файл выгрузки
     */
    protected function removeOldFile() {
        unlink($_SERVER['DOCUMENT_ROOT'] . $this->filePath);
    }


    /**
     * Получить количество элементов для выгрузки
     * @return int
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getElementCount() {
        return ChangesTable::getlist()->getSelectedRowsCount();
    }

}
