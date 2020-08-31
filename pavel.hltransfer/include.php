<?

namespace Pavel\HLTransfer;

\Bitrix\Main\Loader::registerAutoLoadClasses(
    'pavel.hltransfer',
    [
        '\Pavel\HLTransfer\EventManager' => 'lib/eventmanager.php',
        '\Pavel\HLTransfer\Import' => 'lib/general/import.php',
        '\Pavel\HLTransfer\Export' => 'lib/general/export.php',
        '\Pavel\HLTransfer\History' => 'lib/general/history.php',
        '\Pavel\HLTransfer\Logger' => 'lib/general/logger.php',
        '\Pavel\HLTransfer\Helper' => 'lib/general/helper.php',
    ]
);
