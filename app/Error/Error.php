<?php

namespace App\Error;

class Report
{
    private $rootDir;
    private $logDir;

    private $saveLog = true;

    function __construct() {
        $this->rootDir = realpath(__DIR__ . "/../../");
        $this->logDir = $this->rootDir . "/public/logs";

        $this->saveLog = $this->checkPermissionFolder();
    }

    private function checkPermissionFolder(): bool {
        return (is_writable($this->logDir)) ? true : false;
    }

    public function saveLog(string $textm, string $typem): void {
        if ($this->saveLog !== false) {
            $logFile = "log-" . date("Y-m-d") . ".php";

            $datam = strtoupper($typem) . ": " . date("Y-m-d H:i:s");
            $datam .= " [client ".  $_SERVER['REMOTE_ADDR'] . "] ";
            $datam .= $textm . "\n";

            file_put_contents(
                $this->logDir . "/" . $logFile,
                $datam,
                FILE_APPEND | LOCK_EX
            );
        }
    }
    
}
