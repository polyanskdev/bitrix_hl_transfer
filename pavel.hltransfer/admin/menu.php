<?
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$aMenu[] = [
    'parent_menu' => 'global_menu_custom',
    'text' => Loc::getMessage('PAVEL_HLTRANSFER_TEXT_MENU'),
    'icon' => 'highloadblock_menu_icon',
    'page_icon' => 'highloadblock_menu_icon',
    'items' => [
        [
            'text' => Loc::getMessage('PAVEL_HLTRANSFER_SUBMENU_TEXT'),
            'url' => 'hltransfer.php?lang=' . LANGUAGE_ID,
            'more_url' => ['hltransfer.php?lang=' . LANGUAGE_ID],
            'title' => Loc::getMessage('PAVEL_HLTRANSFER_SUBMENU_TITLE'),
        ]
    ]
];

return $aMenu;
