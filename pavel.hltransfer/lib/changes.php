<?php

namespace Pavel\HLTransfer;

use \Bitrix\Main\Entity\DataManager,
    \Bitrix\Main\Entity\IntegerField,
    \Bitrix\Main\Entity\StringField,
    \Bitrix\Main\Entity\BooleanField,
    \Bitrix\Main\Entity\DateTimeField,
    \Bitrix\Main\Type\DateTime,
    \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class ChangesTable extends DataManager {

    /**
     * @return string
     */
    public static function getTableName() {
        return 'pavel_hltransfer_changes';
    }

    /**
     * @return array
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getMap() {
        return [
            new IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true,
                'title' => Loc::getMessage('PAVEL_HLTRANSFER_ID'),
            ]),
            new StringField('HL_BLOCK_NAME', [
                'required' => true,
                'title' => Loc::getMessage('PAVEL_HLTRANSFER_HL_TYPE'),
            ]),
            new IntegerField('HL_ELEMENT_ID', [
                'required' => true,
                'title' => Loc::getMessage('PAVEL_HLTRANSFER_ELEMENT_ID'),
            ]),
            new StringField('HL_ELEMENT_FIELDS', [
                'required' => true,
                'title' => Loc::getMessage('PAVEL_HLTRANSFER_ELEMENT_FIELDS'),
            ]),
            new BooleanField('HL_ELEMENT_DELETED', [
                'title' => Loc::getMessage('PAVEL_HLTRANSFER_DELETED'),
                'values' => ['N', 'Y'],
                'default_value' => 'N'
            ]),
            new DateTimeField('DATE_UPDATE', [
                'title' => Loc::getMessage('PAVEL_HLTRANSFER_DATE_UPDATE'),
                'default_value' => new DateTime
            ])
        ];
    }
}
