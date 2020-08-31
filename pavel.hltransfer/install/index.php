<?
use \Bitrix\Main\Application,
    \Bitrix\Main\Loader,
    \Bitrix\Main\Localization\Loc,
    \Bitrix\Main\ModuleManager,
    \Pavel\HLTransfer\ChangesTable;

Loc::loadMessages(__FILE__);


class pavel_hltransfer extends CModule {

    /**
     * pavel_hltransfer constructor
     */
    public function __construct() {

        $arModuleVersion = [];

        require_once( __DIR__ . '/version.php');

        if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion)){
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->MODULE_ID = 'pavel.hltransfer';
        $this->MODULE_GROUP_RIGHTS = 'Y';
        $this->MODULE_NAME = Loc::getMessage('PAVEL_HLTRANSFER_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('PAVEL_HLTRANSFER_MODULE_DESCRIPTION');
        $this->PARTNER_NAME = Loc::getMessage('PAVEL_HLTRANSFER_MODULE_PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage('PAVEL_HLTRANSFER_MODULE_PARTNER_URL');
    }


    /**
     * Установка модуля
     */
    public function doInstall() {

        // Регистрация модуля в системе
        ModuleManager::registerModule($this->MODULE_ID);
        // Создаем таблицы
        $this->installDB();
        // Установка файлов
        $this->installFiles();
        // Регистрация обработчиков событий
        $this->registerEvents();
    }


    /**
     * Удаление модуля
     */
    public function doUninstall() {

        $this->unRegisterEvents();
        $this->uninstallDB();
        $this->unInstallFiles();
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }


    /**
     * Создание таблиц
     */
    public function installDB() {
        if (Loader::includeModule($this->MODULE_ID)){
            ChangesTable::getEntity()->createDbTable();
        }
    }


    /**
     * Удаление таблиц
     * @throws \Bitrix\Main\Db\SqlQueryException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\SystemException
     */
    public function uninstallDB() {

        if (Loader::includeModule($this->MODULE_ID)){
            $connection = Application::getInstance()->getConnection();
            $connection->dropTable(ChangesTable::getTableName());
        }
    }


    /**
     * Установка файлов
     */
    public function installFiles() {
        CopyDirFiles(__DIR__ . '/admin', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin', true, true);
        CopyDirFiles(__DIR__ . '/js', $_SERVER["DOCUMENT_ROOT"] . '/bitrix/js', true, true);
        CopyDirFiles(__DIR__ . '/css', $_SERVER["DOCUMENT_ROOT"] . '/bitrix/css', true, true);
        mkdir($_SERVER['DOCUMENT_ROOT'] . '/upload/pavel_hltransfer/', 0755, true);
    }


    /**
     * Удаление файлов
     */
    public function unInstallFiles() {
        DeleteDirFiles(__DIR__ . '/admin', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin');
        DeleteDirFilesEx('/bitrix/js/pavel.hltransfer/');
        DeleteDirFilesEx('/bitrix/css/pavel.hltransfer/');
    }


    /**
     * Регистрация обработчиков событий
     */
    public function registerEvents() {
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        $eventManager->registerEventHandler('main', 'OnBuildGlobalMenu', $this->MODULE_ID, '\Pavel\HLTransfer\EventManager', 'addMenu');
        $eventManager->registerEventHandler('main', 'OnPageStart', $this->MODULE_ID, '\Pavel\HLTransfer\EventManager', 'initHLObserver');
    }


    /**
     * Регистрация обработчиков событий
     */
    public function unRegisterEvents() {
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        $eventManager->unRegisterEventHandler('main', 'OnBuildGlobalMenu', $this->MODULE_ID, '\Pavel\HLTransfer\EventManager', 'addMenu');
        $eventManager->unRegisterEventHandler('main', 'OnPageStart', $this->MODULE_ID, '\Pavel\HLTransfer\EventManager', 'initHLObserver');
    }

}
