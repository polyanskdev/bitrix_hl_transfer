<?
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';
if($path = \Bitrix\Main\Loader::getLocal('modules/pavel.hltransfer/admin/hltransfer_request.php')) {
    require_once($path);
}
