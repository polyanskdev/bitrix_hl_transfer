<?php

namespace Pavel\HLTransfer;

use \Bitrix\Main\Loader,
    \Bitrix\Main\Type\DateTime,
    \Bitrix\Main\Config\Option,
    \Bitrix\Highloadblock\HighloadBlockTable;

class History {

    /**
     * Фиксация изменения
     * @param array $params
     * @return null
     * @throws \Bitrix\Main\ObjectException
     */
    public static function commit(array $params) {
        $result = null;

        static::checkParams($params);

        if(empty($params['HL_ELEMENT_DELETED'])) {
            $params['HL_ELEMENT_DELETED'] = 'N';
        }

        if(
            !method_exists($params['DATE_UPDATE'], 'toString') ||
            is_numeric(strtotime($params['DATE_UPDATE']->toString()))
        ) {
            $params['DATE_UPDATE'] = new DateTime();
        }

        if($historyID = static::getID($params['HL_BLOCK_NAME'], $params['HL_ELEMENT_ID'])) {
            static::update($historyID, $params);
        } else {
            static::add($params);
        }

        return $result;
    }


    /**
     * Получить список HL блоков для которых включено отслеживание изменений
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getObservableHL() {
        $result = [];

        $option = Option::get(Helper::MODULE_ID, 'hl_list', '');
        $HLBlockListID = strlen($option) > 0 ? unserialize($option) : [];
        if (is_array($HLBlockListID) && count($HLBlockListID)) {
            $HLBlockList = HighloadBlockTable::getlist([
                'filter' => ['ID' => $HLBlockListID]
            ]);
            while ($HLBlock = $HLBlockList->fetch()) {
                $result[] = $HLBlock['NAME'];
            }
        }

        return $result;
    }


    /**
     * Проверка обязательных параметров
     * @param array $params
     * @throws \Exception
     */
    protected static function checkParams(array $params) {

        if(empty($params['HL_BLOCK_NAME']))
            throw new \Exception('Empty Highload block name');

        if(intval($params['HL_ELEMENT_ID']) <= 0)
            throw new \Exception('Invalid element ID');

    }


    /**
     * Получить изменение из истории по имени сущности и ID
     * @param string $HLName
     * @param int $elementID
     * @return int|null
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    protected static function getID(string $HLName, int $elementID) {
        $result = null;
        $historyElement = ChangesTable::getList([
            'select' => ['ID'],
            'filter' => [
                'HL_BLOCK_NAME' => $HLName,
                'HL_ELEMENT_ID' => $elementID,
            ],
            'limit' => 1
        ])->fetch();

        if($historyElement) {
            $result = intval($historyElement['ID']);
        }

        return $result;
    }


    /**
     * Добавить изменение в историю
     * @param array $params
     * @return array|int|null
     * @throws \Bitrix\Main\LoaderException
     */
    protected static function add(array $params) {
        $result = null;

        if(Loader::includeModule(Helper::MODULE_ID)) {

            $res = ChangesTable::add($params);
            if($res->isSuccess()) {
                $result = $res->getID();
            }
        }

        return $result;
    }


    /**
     * Обновить изменение в истории
     * @param int $id
     * @param array $params
     * @return array|int|null|string
     * @throws \Bitrix\Main\LoaderException
     */
    public static function update(int $id, array $params) {
        $result = null;

        if(Loader::includeModule(Helper::MODULE_ID)) {
            $res = ChangesTable::update($id, $params);
            if($res->isSuccess()) {
                $result = $res->getID();
            }
        }

        return $result;
    }

}
