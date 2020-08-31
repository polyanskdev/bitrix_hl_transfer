<?require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

use \Bitrix\Main\Page\Asset,
    \Bitrix\Main\Localization\Loc,
    \Bitrix\Main\UI\Extension,
    \Pavel\HLTransfer\Helper;

Loc::loadMessages(__FILE__);

require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php';
global $APPLICATION;

Extension::load(['jquery2', 'ui.progressbar', 'ui.alerts', 'ui.buttons']);
Asset::getInstance()->addJs('/bitrix/js/pavel.hltransfer/main.js');
// todo addCss почему-то не подключает, делаем по старому
//Asset::getInstance()->addCss('/bitrix/css/pavel.hltransfer/main.css');
$APPLICATION->SetAdditionalCSS('/bitrix/css/pavel.hltransfer/main.css');

// Права доступа
$userRight = $APPLICATION->GetUserRight(Helper::MODULE_ID);

$tabControl = new CAdminTabControl( 'tabControl', [
    [
        'DIV' => 'hl-transfer-import-tab',
        'TAB' => Loc::getMessage('PAVEL_HLTRANSFER_ADMIN_TAB_IMPORT_TAB'),
        'TITLE' => Loc::getMessage('PAVEL_HLTRANSFER_ADMIN_TAB_IMPORT_TITLE'),
    ],
    [
        'DIV' => 'hl-transfer-export-tab',
        'TAB' => Loc::getMessage('PAVEL_HLTRANSFER_ADMIN_TAB_EXPORT_TAB'),
        'TITLE' => Loc::getMessage('PAVEL_HLTRANSFER_ADMIN_TAB_EXPORT_TITLE'),
    ]
]);
?>

<?if($userRight > 'R'):?>
    <?$tabControl->begin();?>
    <?$tabControl->beginNextTab();?>

        <div id="import-alert-wrap" class="ui-alert ui-alert-success ui-alert-icon-info export-alert-wrap">
            <span class="ui-alert-message"></span>
        </div>
        <div id="progress-wrap" class="ui-progressbar ui-progressbar-lg ui-progressbar-column export__progress-wrap progress-wrap">
            <div id="progress-text-before" class="ui-progressbar-text-before"></div>
            <div class="ui-progressbar-track">
                <div id="progress-line" class="ui-progressbar-bar" style="width:0%;"></div>
            </div>
            <div id="progress-text-after" class="ui-progressbar-text-after"></div>
        </div>

        <form method = "post" enctype="multipart/form-data">
            <button id="import-start" class="ui-btn ui-btn-primary">Начать импорт</button>
            <div id="hl-transfer-import-file-wrap" class="file-input__wrap">
                <input type="file" id="hl-transfer-import-file" name="file_import">
                <label for="hl-transfer-import-file" id="hl-transfer-import-file-choose" class="ui-btn ui-btn-primary">Выбрать файл</label>
                <label for="hl-transfer-import-file" id="hl-transfer-import-file-name" title="Изменить файл" class="hl-transfer__import-file-choose"></label>
            </div>
        </form>

    <?$tabControl->beginNextTab();?>

        <div id="export-alert-wrap" class="ui-alert ui-alert-success ui-alert-icon-info export-alert-wrap">
            <span class="ui-alert-message"></span>
        </div>
        <div id="progress-wrap" class="ui-progressbar ui-progressbar-lg ui-progressbar-column export__progress-wrap progress-wrap">
            <div id="progress-text-before" class="ui-progressbar-text-before"></div>
            <div class="ui-progressbar-track">
                <div id="progress-line" class="ui-progressbar-bar" style="width:0%;"></div>
            </div>
            <div id="progress-text-after" class="ui-progressbar-text-after"></div>
        </div>

        <button id="export-start" class="ui-btn ui-btn-primary">Начать экспорт</button>

    <?$tabControl->EndTab();?>
    <?$tabControl->end();?>
<?else:?>
    <div class="adm-info-message-wrap adm-info-message-red">
        <div class="adm-info-message">
            <div class="adm-info-message-title excel-export__result-message">Доступ закрыт</div>
            <div class="adm-info-message-icon"></div>
        </div>
    </div>
<?endif;?>

<?require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php';
