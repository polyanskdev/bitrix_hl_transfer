<?php

namespace Pavel\HLTransfer;

use \Bitrix\Main\Loader,
    \Bitrix\Main\Config\Option,
    \Bitrix\Main\Application;

global $APPLICATION;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';
if (!Loader::includeModule(Helper::MODULE_ID)) {
    die('Ошибка подключения модуля: ' . Helper::MODULE_ID);
}

// Права доступа
$userRight = $APPLICATION->GetUserRight(Helper::MODULE_ID);

$request = Application::getInstance()->getContext()->getRequest();

$result = [];

if($userRight > 'R') {
    if ($request->isPost()) {
        $post = $request->getPostList()->toArray();

        switch ($post['action']) {

            case 'initExport':

                $offset = $post['offset'] ?? 0;
                $limit = intval(Option::get(Helper::MODULE_ID, 'export_step_limit', 0));

                $export = new Export(Helper::EXPORT_FILE_PATH, $limit, $offset);
                $result = $export->init();

                break;

            case 'export':

                $offset = $post['offset'] ?? 0;
                $limit = intval(Option::get(Helper::MODULE_ID, 'export_step_limit', 0));

                $export = new Export(Helper::EXPORT_FILE_PATH, $limit, $offset);
                $exportResult = $export->next();
                if ($exportResult['status'] === 'success') {
                    $result = array_merge($result, $exportResult);
                    $result['filePath'] = Helper::EXPORT_FILE_PATH;
                }

                break;

            case 'exportFinish':

                $export = new Export(Helper::EXPORT_FILE_PATH);
                $result = $export->finish();

                break;

            case 'initImport':

                $offset = $post['offset'] ?? 0;
                $limit = intval(Option::get(Helper::MODULE_ID, 'import_step_limit', 0));

                $import = new Import(Helper::EXPORT_FILE_PATH, $limit, $offset);
                $import->init();
                $result['status'] = 'success';

                break;

            case 'import':

                $offset = $post['offset'] ?? 0;
                $filePath = $_SESSION['HL_TRANSFER']['IMPORT_FILE']['PATH'];
                $limit = intval(Option::get(Helper::MODULE_ID, 'import_step_limit', 0));

                $import = new Import($filePath, $limit, $offset);
                $importResult = $import->next();
                if ($importResult['status'] === 'success') {
                    $result = array_merge($result, $importResult);
                }

                break;

            case 'importFinish':

                $import = new Import(Helper::EXPORT_FILE_PATH);
                $result = $import->finish();

                break;

            case 'uploadFile':

                try {

                    $file = $request->getFile('file');
                    if (!$file) {
                        throw new \Exception('Файл не выбран');
                    }

                    $fileID = \CFile::saveFile($file, '/pavel_hltransfer/');
                    if (!$fileID) {
                        throw new \Exception('Не удалось загрузить файл');
                    }

                    $filePath = \CFile::getPath($fileID);
                    $result['fileID'] = $fileID;
                    $result['filePath'] = $filePath;

                    $_SESSION['HL_TRANSFER']['IMPORT_FILE'] = [
                        'ID' => $fileID,
                        'PATH' => $filePath
                    ];

                } catch (\Exception $e) {
                    $result['error'] = $e->getMessage();
                }

                break;

            case 'deleteFile':

                $fileID = intval($post['fileID']);
                if ($fileID) {
                    \CFile::delete($fileID);
                }

                $result['status'] = 'success';

                break;
        }
    }
} else {
    $result['error'] = 'Доступ закрыт';
}

$result['status'] = (empty($result) || isset($result['error'])) ? 'fail' : 'success';
echo \Bitrix\Main\Web\Json::encode($result);
die();
