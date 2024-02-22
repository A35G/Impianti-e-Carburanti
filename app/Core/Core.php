<?php

namespace App\System;

use App\Csv\Manage;
use App\Management\Database;

class Core
{
    private $rootDir;
    public $msg;

    function __construct() {
        $this->rootDir = realpath(__DIR__ . "/../../");

        $this->msg = $this->loadText();
    }

    private function loadText()
    {
        static $msg;

        if (empty($msg)) {
            $appDir = realpath(__DIR__ . "/../");
            $file_path = $appDir . '/Lang/Lang.php';
            $found = false;
            if (file_exists($file_path)) {
                $found = true;
                require $file_path;
            }

            if (!$found) {
                exit(0);
            }

            if (!isset($msg) OR !is_array($msg)) {
                exit(0);
            }
        }

        return $msg;
    }

    public function checkManualImport(string $typeFile)
    {
        return (new Manage())->checkFile($typeFile);
    }

    public function checkForDataImport(string $typeFile)
    {
        (new Manage())->elaborateFile($typeFile);
    }

    public function downloadDataFile(string $typeFile)
    {
        (new Manage())->checkAndDownloadFile($typeFile);
    }

    public function checkAndCleanArchive()
    {
        $clnv = (new Database())->config["rmvfiles"];
        $clna = (new Database())->config["rmvdays"];
        if ($clnv) {
            if (is_numeric($clna) && !empty($clna)) {
                (new Database())->checkArchiveAndClean($clna);
            }
        }
    }

    public function loadStationList(int $nump)
    {
        return (new Database())->loadDataStation($nump);
    }

}
