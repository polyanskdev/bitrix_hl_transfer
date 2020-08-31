<?
namespace Pavel\HLTransfer;

use \Bitrix\Main\Localization\Loc,
    \Bitrix\Main\Entity\Event,
    \Bitrix\Main\Loader,
    \Bitrix\Main\Type\DateTime,
    \Bitrix\Main\Config\Option,
    \Bitrix\Highloadblock\HighloadBlockTable;

Loc::loadMessages(__FILE__);

class EventManager {

    /**
     * Добавляет пункт меню
     * @param $aGlobalMenu
     * @param $aModuleMenu
     */
    public static function addMenu(&$aGlobalMenu, &$aModuleMenu) {

        $aGlobalMenu['global_menu_custom'] = [
            'menu_id' => 'custom',
            'page_icon' => 'services_title_icon',
            'index_icon' => 'services_page_icon',
            'text' => Loc::getMessage('PAVEL_HLTRANSFER_TEXT'),
            'title' => Loc::getMessage('PAVEL_HLTRANSFER_TEXT'),
            'sort' => 900,
            'items_id' => 'global_menu_custom',
            'help_section' => 'custom',
            'items' => []
        ];
    }


    /**
     * Инициализация отслеживания изменений в HL блоках
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function initHLObserver() {

        if(
            Loader::includeModule('highloadblock') &&
            Loader::includeModule(Helper::MODULE_ID)
        ) {

            $observeHLBlocksID = Option::get(Helper::MODULE_ID, 'hl_list');
            $observeHLBlocksID = (strlen($observeHLBlocksID) > 0) ? unserialize($observeHLBlocksID) : null;

            if($observeHLBlocksID) {
                $HLBlockList = HighloadBlockTable::getlist([
                    'filter' => ['ID' => $observeHLBlocksID]
                ])->fetchAll();

                if (is_array($HLBlockList) && count($HLBlockList)) {

                    $eventManager = \Bitrix\Main\EventManager::getInstance();

                    foreach ($HLBlockList as $HLBlock) {
                        $eventManager->addEventHandler('', $HLBlock['NAME'] . 'OnAfterAdd', ['\Pavel\HLTransfer\EventManager', 'itemChanged']);
                        $eventManager->addEventHandler('', $HLBlock['NAME'] . 'OnAfterUpdate', ['\Pavel\HLTransfer\EventManager', 'itemChanged']);
                        $eventManager->addEventHandler('', $HLBlock['NAME'] . 'OnAfterDelete', ['\Pavel\HLTransfer\EventManager', 'itemChanged']);
                    }
                }
            }
        }
    }


    /**
     * Фиксация изменений элемента HL блока
     * @param Event $event
     * @return \Bitrix\Main\Entity\EventResult
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function itemChanged(Event $event) {

        $eventName = $event->getEventType();

        $entityName = null;
        $deleted = null;

        $pregResult = preg_match('/(.+)(OnAfter)(.+)/i', $eventName, $pregMatches);
        if($pregResult === 1 && isset($pregMatches[3])) {
            $entityName = trim($pregMatches[1]);
            $deleted = trim(strtolower($pregMatches[3])) === 'delete' ? 'Y' : 'N';
        }

        $HLBlockList = History::getObservableHL();

        if($entityName && in_array($entityName, $HLBlockList)) {
            $elementID = $event->getParameter('id');
            $elementID = is_array($elementID) ? intval($elementID['ID']) : intval($elementID);

            $elementFields = $event->getParameter('fields');
            $elementFields = serialize($elementFields);

            if($elementID && $elementFields) {
                try {

                    $commitData = [
                        'HL_BLOCK_NAME' => $entityName,
                        'HL_ELEMENT_ID' => $elementID,
                        'HL_ELEMENT_FIELDS' => $deleted !== 'Y' ? $elementFields : '',
                        'HL_ELEMENT_DELETED' => $deleted,
                        'DATE_UPDATE' => new DateTime()
                    ];
                    if($deleted === 'Y') {
                        unset($commitData['HL_ELEMENT_FIELDS']);
                    }

                    History::commit($commitData);

                } catch(Exception $e) {
                    // $e->getMessage();
                }
            }
        }

        return new \Bitrix\Main\Entity\EventResult();
    }

}
