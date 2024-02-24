<?php

require_once realpath(__DIR__ . '/../../vendor/autoload.php');
use App\System\Core;

if (isset($_GET['section']) && !empty($_GET['section'])) {
    $dsc = htmlentities($_GET['section']);
    switch ($dsc) {
        case "download":
            if (isset($_GET['subs']) && !empty($_GET['subs'])) {
                $dsub = htmlentities($_GET['subs']);

                if ($dsub === "impianti" OR $dsub === "prezzi") {
                    $res = (new Core())->downloadDataFile($dsub);
                    if ($res) {
                        exit((new Core())->msg["res"]["sucdown"]);
                    } else {
                        exit((new Core())->msg["res"]["errdown"]);
                    }
                } else {
                    exit((new Core())->msg["res"]["badtype"]);
                }
            } else {
                exit((new Core())->msg["res"]["notype"]);
            }
            break;
        case "parse":
            if (isset($_GET['subs']) && !empty($_GET['subs'])) {
                $dsub = htmlentities($_GET['subs']);

                if ($dsub === "impianti" OR $dsub === "prezzi") {
                    (new Core())->checkForDataImport($dsub);
                } else {
                    exit((new Core())->msg["res"]["badtype"]);
                }
            } else {
                exit((new Core())->msg["res"]["notype"]);
            }
            break;
        case "elaborateManualImport":
            if (isset($_GET['subs']) && !empty($_GET['subs'])) {
                $dsub = htmlentities($_GET['subs']);

                if ($dsub === "impianti" OR $dsub === "prezzi") {
                    (new Core())->checkManualImport($dsub);
                } else {
                    exit((new Core())->msg["res"]["badtype"]);
                }
            } else {
                exit((new Core())->msg["res"]["notype"]);
            }
            break;
        case "datalist":
            if (isset($_GET['subs']) 
                && !empty($_GET['subs']) 
                && is_numeric($_GET['subs'])) {
                $page = intval($_GET['subs']);
            } else {
                $page = 1;
            }

            exit((new Core())->loadStationList($page));
            break;
        case "cleanhistory":
            (new Core())->checkAndCleanArchive();
            break;
        default:
            exit((new Core())->msg["res"]["nosez"]);
            break;
    }
} else {
    exit((new Core())->msg["res"]["nosez"]);
}
