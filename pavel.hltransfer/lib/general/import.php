<?php

namespace Pavel\HLTransfer;

use \Bitrix\Main\Config\Option,
    \Bitrix\Highloadblock\HighloadBlockTable;

class Import {

    protected $filePath;
    protected $limit;
    protected $offset;
    protected $logger;


    /**
     * Import constructor
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

        $this->logger = new Logger(Helper::IMPORT_LOG_PATH, $logLevel);
    }


    /**
     * Начало выгрузки
     */
    public function init() {

        $counter = &$_SESSION['HL_TRANSFER']['IMPORT']['COUNTER'];

        $this->logger->debug('Начало выгрузки');
        $counter = [
            'ADD' => 0,
            'UPDATE' => 0,
            'DELETE' => 0,
            'ERROR' => 0,
        ];
    }


    /**
     * Выполнить шаг выгрузки
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function next() {

        $result = [
            'status' => 'success',
            'cnt' => 0,
            'allCnt' => 0,
            'errorCount' => 0
        ];

        $counter = &$_SESSION['HL_TRANSFER']['IMPORT']['COUNTER'];

        // Часть данных из файла для текущего шага
        $dataStep = [];
        // Список сущностей
        $entityNameList = [];
        // Читаем файл
        $fileLines = $this->readFile();


        // Определяем границы текущего шага
        $countFileLines = count($fileLines);
        $result['allCnt'] = $countFileLines;
        $maxElementNum = $this->offset + $this->limit;
        $limit = ($this->limit > 0 && $maxElementNum < $countFileLines) ? $maxElementNum : $countFileLines;

        for($i = $this->offset; $i < $limit; $i++) {

            try {
                $data = $this->unserialize($fileLines[$i]);

                if (is_array($data)) {
                    // Раскладываем данные в удобном формате
                    $data['CREATED'] = 'N';
                    $dataStep[$data['HL_BLOCK_NAME']]['ITEMS'][$data['HL_ELEMENT_ID']] = $data;
                    $dataStep[$data['HL_BLOCK_NAME']]['ITEMS_ID_LIST'][] = $data['HL_ELEMENT_ID'];
                    $entityNameList[$data['HL_BLOCK_NAME']] = $data['HL_BLOCK_NAME'];
                }
            } catch(\Exception $e) {
                $result['cnt']++;
                $result['errorCount']++;
                $errorMag = "Ошибка: {$e->getMessage()}; HL блок: {$data['HL_BLOCK_NAME']}; ID элемента: {$data['HL_ELEMENT_ID']}";
                $this->logger->error($errorMag);
            }
        }

        // Получаем список необходимых HL блоков
        if(count($dataStep)) {

            $dbEntity = HighloadBlockTable::getlist(['filter' => ['NAME' => $entityNameList]]);
            while($entity = $dbEntity->fetch()) {
                $compileEntity = HighloadBlockTable::compileEntity($entity);
                $dataStep[$entity['NAME']]['CLASS_NAME'] = $compileEntity->getDataClass();
            }

            foreach ($dataStep as $entityName => &$entityData) {
                try {
                    if (!$entityData['CLASS_NAME']) {

                        if(is_array($entityData['ITEMS']) && count($entityData['ITEMS'])) {
                            $result['cnt'] += count($entityData['ITEMS']);
                            $result['errorCount'] += count($entityData['ITEMS']);
                        }

                        throw new \Exception("Класс '{$entityName}' не найден");
                    }

                    $entityClassName = $entityData['CLASS_NAME'];
                    $itemsID = $entityData['ITEMS_ID_LIST'];

                    $itemsData = $entityClassName::getlist(['filter' => ['ID' => $itemsID]])->fetchAll();
                    foreach ($itemsData as $item) {
                        if (isset($dataStep[$entityName]['ITEMS'][$item['ID']])) {
                            $dataStep[$entityName]['ITEMS'][$item['ID']]['CREATED'] = 'Y';
                        }
                    }

                    if (is_array($entityData['ITEMS']) && count($entityData['ITEMS'])) {
                        foreach ($entityData['ITEMS'] as $item) {
                            try {
                                if ($item['HL_ELEMENT_DELETED'] === 'Y') {
                                    $res = $entityClassName::delete($item['HL_ELEMENT_ID']);
                                    if ($res->isSuccess()) {
                                        $counter['DELETE']++;
                                    } else {
                                        $result['errorCount']++;
                                        $errMessage = 'Не удалось удалить элемент. ';
                                        $errMessage .= implode('; ', $res->getErrorMessages());
                                        throw new \Exception($errMessage);
                                    }

                                } else {
                                    $prepareData = $this->unserialize($item['HL_ELEMENT_FIELDS']);

                                    if ($item['CREATED'] === 'Y') {
                                        $res = $entityClassName::update($item['HL_ELEMENT_ID'], $prepareData);
                                        if ($res->isSuccess()) {
                                            $counter['UPDATE']++;
                                        } else {
                                            $result['errorCount']++;
                                            $errMessage = 'Не удалось обновить элемент. ';
                                            $errMessage .= implode('; ', $res->getErrorMessages());
                                            throw new \Exception($errMessage);
                                        }
                                    } else {
                                        // todo По задаче новые элементы не создаем, выдаем ошибку
                                        /*$res = $entityClassName::add($prepareData);
                                        if ($res->isSuccess()) {
                                            $counter['ADD']++;
                                        } else {
                                            $result['errorCount']++;
                                            $errMessage = 'Не удалось добавить элемент. ';
                                            $errMessage .= implode('; ', $res->getErrorMessages());
                                            throw new \Exception($errMessage);
                                        }*/
                                        $result['errorCount']++;
                                        throw new \Exception('Элемент не найден');
                                    }
                                }
                            } catch(\Exception $e) {
                                $errorMag = "Ошибка: {$e->getMessage()}; HL блок: {$item['HL_BLOCK_NAME']}; ID элемента: {$item['HL_ELEMENT_ID']}";
                                $this->logger->error($errorMag);
                            }
                            $result['cnt']++;
                        }
                    }
                }
                catch(\Exception $e) {
                    $this->logger->error('Ошибка: ' . $e->getMessage());
                }
            }

        }

        $counter['ERROR'] += $result['errorCount'];

        return $result;
    }


    /**
     * Окончание выгрузки
     * @return array
     */
    public function finish() {

        $counter = &$_SESSION['HL_TRANSFER']['IMPORT']['COUNTER'];

        $result = [
            'statistic' => [
                'add' => $counter['ADD'],
                'update' => $counter['UPDATE'],
                'delete' => $counter['DELETE'],
                'error' =>  $counter['ERROR'],
            ]
        ];

        $this->logger->debug(implode(PHP_EOL, [
            'Выгрузка завершена',
            'Добавлено элементов: ' . $counter['ADD'],
            'Изменено элементов: ' . $counter['UPDATE'],
            'Удалено элементов: ' . $counter['DELETE'],
            'Количество ошибок: ' . $counter['ERROR'],
        ]));

        return $result;
    }


    /**
     * Чтение файла
     * @return array|bool|null
     */
    protected function readFile() {
        $result = null;

        $filePath = $_SERVER['DOCUMENT_ROOT'] . $this->filePath;
        if(is_readable($filePath)) {
            $result = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        }

        return is_array($result) ? $result : null;
    }


    /**
     * Десериализация объекта
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    protected function unserialize($data) {
        $result = unserialize($data);
        if($result === false) {
            throw new \Exception('Ошибка десериализации');
        }
        return $result;
    }

}
