<?php

require_once realpath(__DIR__ . '/../../vendor/autoload.php');
use App\System\Core;

if (isset($_GET['section']) && !empty($_GET['section'])) {
    $dsc = htmlentities($_GET['section']);
    switch ($dsc) {
        /**
         * Download dei File suddivisi per Tipologia:
         * 
         * - impianti
         * Per ottenere il file contenente le informazioni sugli 
         * Impianti di Rifornimento
         * 
         * - prezzi
         * Per ottenere il file contenente i prezzi praticati
         * all'interno delle Stazioni di Servizio suddivisi per
         * tipo di carburante e servizio
         */
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
        /**
         * Elaborazione dei File suddivisi per Tipologia:
         * 
         * - impianti
         * - prezzi
         */
        case "checkForDataImport":
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
        /**
         * Elaborazione manuale dei File contenuti nella cartella "import"
         * suddivisi per Tipologia:
         * 
         * - impianti
         * - prezzi
         */
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
        /**
         * Elenco delle Stazioni di Rifornimento Carburante e loro informazioni,
         * Tipologie di Carburante fornito e Prezzi applicati.
         */
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
        /**
         * Pulizia dello Storico Import ed Elaborazioni
         */
        case "cleanhistory":
            (new Core())->checkAndCleanArchive();
            break;
        /**
         * Risposta di default del Sistema
         */
        default:
            exit((new Core())->msg["res"]["nosez"]);
            break;
    }
} else {
    exit((new Core())->msg["res"]["nosez"]);
}
