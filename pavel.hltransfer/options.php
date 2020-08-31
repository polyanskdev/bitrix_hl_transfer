<?
/** Страница редактирования настроек модуля */

use \Bitrix\Main\Application,
    \Bitrix\Main\Config\Option,
    \Bitrix\Main\Localization\Loc,
    \Bitrix\Highloadblock\HighloadBlockTable,
    \Bitrix\Highloadblock\HighloadBlockLangTable,
    \Pavel\HLTransfer\Helper;

// Права доступа
$userRight = $APPLICATION->GetUserRight(Helper::MODULE_ID);
if ($userRight < 'W') {
    $APPLICATION->authForm('Авторизоваться');
}

$context = Application::getInstance()->getContext();
$request = $context->getRequest();

Loc::loadMessages($context->getServer()->getDocumentRoot() . '/bitrix/modules/main/options.php');
Loc::loadMessages(__FILE__);

// Группировка настроек по вкладкам
$tabControl = new CAdminTabControl( 'tabControl', [
    [
        'DIV' => 'settings',
        'TAB' => Loc::getMessage('PAVEL_HLTRANSFER_TAB_1'),
        'TITLE' => Loc::getMessage('PAVEL_HLTRANSFER_TAB_TITLE_1'),
    ]
]);


// Получаем список Highload-блоков
$HLBlockListID = [];
$HLBlockList = [];
$HLob = HighloadBlockTable::getlist(['select' => ['ID', 'NAME']]);
while($item = $HLob->fetch()) {
    $HLBlockList[$item['ID']] = $item['NAME'];
    $HLBlockListID[] = $item['ID'];
}

if(count($HLBlockListID)) {
    $HLNamesOb = HighloadBlockLangTable::getlist([
        'select' => ['ID', 'NAME'],
        'filter' => [
            'ID' => $HLBlockListID,
            'LID' => 'ru',
        ]
    ]);
    while ($item = $HLNamesOb->fetch()) {
        if (isset($HLBlockList[$item['ID']])) {
            $HLBlockList[$item['ID']] = $item['NAME'];
        }
    }
}


// Список параметров модуля
$settingOptions = [
    'hl_list' => [
        'TYPE' => 'select',
        'LABEL' => Loc::getMessage('PAVEL_HLTRANSFER_PARAM_HL_LIST'),
        'VALUES' => $HLBlockList,
        'MULTIPLE' => 'Y',
        'SIZE' => 5,
    ],
    'import_step_limit' => [
        'TYPE' => 'text',
        'LABEL' => Loc::getMessage('PAVEL_HLTRANSFER_PARAM_IMPORT_STEP_LIMIT'),
    ],
    'export_step_limit' => [
        'TYPE' => 'text',
        'LABEL' => Loc::getMessage('PAVEL_HLTRANSFER_PARAM_EXPORT_STEP_LIMIT'),
    ],
    'log_type' => [
        'TYPE' => 'select',
        'LABEL' => Loc::getMessage('PAVEL_HLTRANSFER_PARAM_LOG_TYPE'),
        'VALUES' => [
            'default' => Loc::getMessage('PAVEL_HLTRANSFER_PARAM_LOG_ITEM_1'),
            'full' => Loc::getMessage('PAVEL_HLTRANSFER_PARAM_LOG_ITEM_2'),
        ]
    ]
];


// Сохранение настроек модуля
if($request->isPost()) {

    if($userRight > 'R' && check_bitrix_sessid()) {
        $post = $request->getPostList()->toArray();

        if (!empty($post['restore'])) {
            try {
                Option::delete(Helper::MODULE_ID);
                CAdminMessage::showMessage([
                    'MESSAGE' => Loc::getMessage('PAVEL_HLTRANSFER_DEFAULT_SETTING'),
                    'TYPE' => 'OK',
                ]);
            } catch (\Bitrix\Main\ArgumentNullException $e) {
                CAdminMessage::showMessage([
                    'MESSAGE' => $e->getMessage(),
                    'TYPE' => 'ERROR'
                ]);
            }
        } elseif(is_array($post) && count($post) && !empty($post['save'])) {

            foreach($settingOptions as $optionName => $option) {

                if(isset($post[$optionName])) {
                    $value = $post[$optionName];
                    if($option['TYPE'] === 'select' && $option['MULTIPLE'] === 'Y') {
                        $value = serialize($value);
                    }

                    try {
                        Option::set(Helper::MODULE_ID, $optionName, $value);
                    } catch (\Bitrix\Main\ArgumentOutOfRangeException $e) {
                        CAdminMessage::showMessage([
                            'MESSAGE' => $e->getMessage(),
                            'TYPE' => 'ERROR'
                        ]);
                    }

                } else {
                    try {
                        Option::delete(Helper::MODULE_ID, ['name' => $optionName]);
                    } catch (\Bitrix\Main\ArgumentNullException $e) {
                        CAdminMessage::showMessage([
                            'MESSAGE' => $e->getMessage(),
                            'TYPE' => 'ERROR'
                        ]);
                    }
                }
            }

            CAdminMessage::showMessage([
                'MESSAGE' => Loc::getMessage('PAVEL_HLTRANSFER_PARAM_SAVE'),
                'TYPE' => 'OK'
            ]);
        }
    } else {
        CAdminMessage::showMessage([
            'MESSAGE' => Loc::getMessage( 'PAVEL_HLTRANSFER_PARAM_SAVE_FAIL'),
            'TYPE' => 'ERROR'
        ]);
    }
}
?>

<?$tabControl->begin();?>
<form method="post" action="<?=sprintf('%s?mid=%s&lang=%s', $request->getRequestedPage(), urlencode($mid), LANGUAGE_ID)?>">
    <?echo bitrix_sessid_post();?>

    <?$tabControl->beginNextTab();?>

        <?foreach($settingOptions as $optionName => $option):
            $optionValue = Option::get(Helper::MODULE_ID, $optionName);
            ?>
            <tr>
                <td width="40%">
                    <label for="param_<?=$optionName?>"><?=$option['LABEL']?>:</label>
                </td>
                <td width="60%">
                    <?if($option['TYPE'] === 'text'):?>
                        <input type="<?=$option['TYPE']?>"
                               size="20"
                               name="<?=$optionName?>"
                               id="param_<?=$optionName?>"
                               value="<?=$optionValue?>"
                        />
                    <?elseif($option['TYPE'] === 'select' && $option['MULTIPLE'] === 'Y'):
                        $size = $option['MULTIPLE'] ? $option['MULTIPLE'] : 5;
                        $optionValue = strlen($optionValue) > 0 ? unserialize($optionValue) : [];
                        ?>
                        <select name="<?=$optionName?>[]" multiple="multiple" size="<?=$size?>">
                            <?foreach($option['VALUES'] as $key => $name):?>
                                <option value="<?=$key?>"<?if(in_array($key, $optionValue)):?> selected <?endif;?>>
                                    <?=$name?>
                                </option>
                            <?endforeach;?>
                        </select>
                    <?elseif($option['TYPE'] === 'select'):?>
                        <select name="<?=$optionName?>">
                            <?foreach($option['VALUES'] as $key => $name):?>
                                <option value="<?=$key?>"<?if($optionValue == $key):?> selected <?endif;?>>
                                    <?=$name?>
                                </option>
                            <?endforeach;?>
                        </select>
                    <?endif;?>
                </td>
            </tr>
        <?endforeach;?>

    <?$tabControl->EndTab();?>
    <?$tabControl->buttons();?>
        <input type="submit"
               name="save"
               value="<?=Loc::getMessage('MAIN_SAVE')?>"
               title="<?=Loc::getMessage('MAIN_OPT_SAVE_TITLE') ?>"
               class="adm-btn-save"
               <?=($userRight > 'R') ? '' : 'disabled'?>
        />
        <input type="submit"
               name="restore"
               title="<?=Loc::getMessage('MAIN_HINT_RESTORE_DEFAULTS') ?>"
               onclick="return confirm('<?= AddSlashes(GetMessage('MAIN_HINT_RESTORE_DEFAULTS_WARNING')) ?>')"
               value="<?=Loc::getMessage('MAIN_RESTORE_DEFAULTS') ?>"
               <?=($userRight > 'R') ? '' : 'disabled'?>
        />
    <?$tabControl->end();?>
</form>
