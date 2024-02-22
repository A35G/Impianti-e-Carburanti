<?php

namespace App\Csv;

use App\System\Core;
use App\Error\Report;
use App\Management\Database;

class Manage
{
    private $rootDir;

    function __construct()
    {
        $this->rootDir = realpath(__DIR__ . "/../../");
    }

    private function checkEmptyFile(string $pathFile)
    {
        clearstatcache();
        return filesize($pathFile);
    }

    public function checkFile(string $typeFile)
    {
        switch ($typeFile) {
            case "impianti":
                $folder = "impianti";
                break;
            case "prezzi":
                $folder = "prezzi";
                break;
        }

        $fileDir = $this->rootDir . "/public/import/" . $folder;
        if (is_readable($fileDir) === false) {
            $m = (new Core())->msg["folder"]["noReadPermission"];

            $m = sprintf($m, $folder);
            (new Report())->saveLog($m, "error");
            return false;
        }

        $lsFile = array();
        $excFile = array(".","..",".DS_Store",".htaccess");
        if ($handle = opendir($fileDir)) {
            while (false !== ($entry = readdir($handle))) {
                if (is_dir($entry)) {
                    continue;
                }

                if (in_array($entry, $excFile) !== false) {
                    continue;
                }

                $rsCheck = $this->checkEmptyFile($fileDir . "/" . $entry);
                if ($rsCheck !== false && !empty($rsCheck)) {
                    $lsFile[] = $entry;
                }
            }

            closedir($handle);
        }

        if (!empty($lsFile)) {
            return (new Database())->checkImportList($typeFile, $lsFile);
        }
    }

    public function elaborateFile(string $typeFile)
    {
        $drc = (new Database())->checkToImportData($typeFile);
        if (is_array($drc)) {
            $nameFile = $drc["nameFile"];

            $fileDir = $this->rootDir . "/public/import/" . $typeFile;
            $fileToRead = $fileDir . "/" . $nameFile;
            if (is_readable($fileToRead) === false) {
                $argsErr = array(
                    "error",
                    $drc["codeFile"],
                    date("Y-m-d H:i:s"),
                    2,
                    $error
                );

                (new Database())->saveDataFile($typeFile, $argsErr);

                $m = sprintf(
                    (new Core())->msg["folder"]["noReadPermission"],
                    $nameFile
                );

                (new Report())->saveLog($m, "error");

                exit(0);
            }

            ini_set('auto_detect_line_endings', true);

            switch ($typeFile) {
                case "impianti":
                    $csvl = 10;
                    break;
                case "prezzi":
                    $csvl = 5;
                    break;
            }

            $row = 1;
            $saveData = $errorData = 0;
            $infoFile = array();
            if (($handle = fopen($fileToRead, "r")) !== false) {

                $m = (new Core())->msg["file"]["initParse"];

                $m = sprintf($m, $nameFile, date("d/m/Y - H:i:s"));

                (new Report())->saveLog($m, "notice");

                while (($data = fgetcsv($handle, 0, ";")) !== false) {
                    if (intval($row) > 2) {
                        $num = count($data);

                        if (intval($num) === intval($csvl)) {
                            if ($data[0] !== false && !empty($data[0])) {

                                if ($typeFile === "impianti") {
                                    $rsd = (new Database())->saveDataRow($data);
                                    if ($rsd === false) {

                                        $er = sprintf(
                                            (new Core())->msg["db"]["noSaveRow"],
                                            intval($row)
                                        );

                                        $datar = implode(";",$data);
                                        $datar .= ";" . $er;

                                        $infoFile[] = $datar;

                                        ++$errorData;
                                    } else {
                                        ++$saveData;
                                    }
                                }

                                if ($typeFile === "prezzi") {
                                    $dataPrice = json_encode(array(
                                        "impianto"  =>  $data[0],
                                        "tipo"      =>  $data[1],
                                        "prezzo"    =>  $data[2],
                                        "self"      =>  $data[3],
                                        "data"      =>  $data[4]
                                    ));

                                    $rsData = (new Database())->saveDataRowPrice($dataPrice);
                                    if ($rsData === false) {
                                        $er = sprintf(
                                            (new Core())->msg["db"]["noSaveRow"],
                                            intval($row)
                                        );

                                        $datar = implode(";",$data);
                                        $datar .= ";" . $er;

                                        $infoFile[] = $datar;

                                        ++$errorData;
                                    } else {
                                        ++$saveData;
                                    }
                                }
                            } else {

                                $er = sprintf(
                                    (new Core())->msg["file"]["emptyRow"],
                                    intval($row)
                                );

                                $infoFile[] = implode(";",$data) . ";" . $er;

                                ++$errorData;
                            }
                        } else {

                            $er = sprintf(
                                (new Core())->msg["file"]["wrongColumn"],
                                intval($row),
                                intval($num),
                                intval($csvl)
                            );

                            $infoFile[] = implode(";",$data) . ";" . $er;

                            ++$errorData;
                        }
                    } else {
                        $infoFile[] = implode(";",$data) . ";Anomalia";
                    }

                    ++$row;
                }

                fclose($handle);
            }

            $argsData = array(
                "endWork",
                $drc["codeFile"],
                date("Y-m-d H:i:s"),
                1,
                (intval($row) - 1),
                intval($errorData),
                intval($saveData)
            );

            $rsp = (new Database())->saveDataFile($typeFile, $argsData);

            ini_set('auto_detect_line_endings', false);

            $m = sprintf(
                (new Core())->msg["file"]["endParse"],
                $nameFile,
                date("d/m/Y - H:i:s")
            );

            (new Report())->saveLog($m, "notice");

            if ($rsp) {
                $fileDirAct = $this->rootDir . "/public/import/" . $typeFile;
                $fileStart = $fileDirAct . "/" . $nameFile;

                $fileDirNew = $this->rootDir . "/public/archive/" . $typeFile;
                $fileEnd = $fileDirNew . "/" . $nameFile;

                if (rename($fileStart, $fileEnd) === false) {
                    $m = sprintf(
                        (new Core())->msg["file"]["noArchive"],
                        $nameFile
                    );

                    (new Report())->saveLog($m, "error");
                }
            }

            if (intval($errorData) > 0 && count($infoFile) > 1) {
                $anmFile = "anomalie_" . $nameFile;
                $anmContent = implode("\n",$infoFile);
                $anmPath = $this->rootDir . "/public/archive/anomalie/" . $anmFile;
                if (file_put_contents($anmPath,$anmContent) === false) {
                    $m = sprintf(
                        (new Core())->msg["system"]["noAnomalyFile"],
                        $anmFile
                    );

                    (new Report())->saveLog($m, "error");
                }
            }
        }
    }

    private function checkFileContent(string $fpath)
    {
        $file = fopen($fpath, "r");

        $doz = array();
        if ($file) {
            while (($line = fgets($file)) !== false) {
                if ((empty(trim($line))) OR (preg_match('/^#/', $line) > 0)) {
                    continue;
                }

                preg_match_all('/(?<!\\\\)["]/', $line, $matches);
                if (count($matches[0]) === 1) {
                    $line = str_replace("\"","",$line);
                }

                $line = str_replace("&#039;", "'", $line);

                $doz[] = $line;
            }

            fclose($file);
        }

        if (!empty($doz)) {
            if (file_put_contents($fpath, implode("",$doz)) !== false) {
                $msg = (new Core())->msg["file"]["cleanSuccess"];
                (new Report())->saveLog($msg, "notice");

                return true;
            }
        }

        return false;
    }

    public function checkAndDownloadFile(string $typeFile)
    {
        switch ($typeFile) {
            case "impianti":
                $ckey = "activestation";
                $folder = "impianti";
                $nameFile = "anagrafica_impianti_attivi";   //  Nome file MiSe
                $nameFile .= "_" . date("d-m-Y_His") . ".csv";
                break;
            case "prezzi":
                $ckey = "fuelprice";
                $folder = "prezzi";
                $nameFile = "prezzo_alle_8";    //  Nome file MiSe
                $nameFile .= "_" . date("d-m-Y_His") . ".csv";
                break;
        }

        $url = (new Database())->config["extLink"][$ckey];

        if (empty($url)) {
            $m = sprintf(
                (new Core())->msg["config"]["noUrlDownload"],
                $folder
            );

            (new Report())->saveLog($m, "error");
            return false;
        }

        $fileDir = $this->rootDir . "/public/import/" . $folder;
        if (is_writable($fileDir) === false) {
            $m = sprintf(
                (new Core())->msg["file"]["notFoundNoPermission"],
                $nameFile
            );

            (new Report())->saveLog($m, "error");
            return false;
        }

        set_time_limit(0);
        $fp = fopen ($fileDir . '/' . $nameFile, 'w+');

        $ch = curl_init(str_replace(" ","%20",$url));
        curl_setopt($ch, CURLOPT_TIMEOUT, 600);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($ch);

        $errc = false;

        if (curl_errno($ch)) {
            $errc = curl_error($ch);

            $m = sprintf(
                (new Core())->msg["system"]["cUrlError"],
                $errc
            );

            (new Report())->saveLog($m);

            $errc = true;
        } else {
            $sttDownload = curl_getinfo($ch);
            if ($sttDownload["http_code"] !== 200) {
                $m = sprintf(
                    (new Core())->msg["system"]["downloadError"],
                    $sttDownload["http_code"]
                );

                (new Report())->saveLog($m, "error");

                $errc = true;
            }
        }

        curl_close($ch);

        fclose($fp);

        if ($errc !== false) {
            return false;
        }

        if (file_exists($fileDir . '/' . $nameFile) === false) {
            $m = sprintf(
                (new Core())->msg["system"]["noFileDownload"],
                $nameFile
            );
            
            (new Report())->saveLog($m, "error");
            return false;
        }

        $szf = $this->checkEmptyFile($fileDir . '/' . $nameFile);
        if ($szf === false OR empty($szf)) {
            $m = sprintf(
                (new Core())->msg["file"]["emptyDownload"],
                $nameFile
            );

            (new Report())->saveLog($m, "error");
            return false;
        }

        $msg = sprintf(
            (new Core())->msg["file"]["successDownload"],
            $nameFile,
            $folder
        );

        (new Report())->saveLog($msg, "notice");

        if ($typeFile === "impianti") {
            if (is_writable($fileDir . '/' . $nameFile) === false) {

                $m = "Impossibile effettuare operazioni di pulizia sul file ";
                $m .= "scaricato - Non si dispone dei permessi di scrittura per";
                $m .= " il file '%s'.";

                $m = sprintf(
                    (new Core())->msg["file"]["cleanError"],
                    $nameFile
                );

                (new Report())->saveLog($m, "error");
                return false;
            }

            $this->checkFileContent($fileDir . '/' . $nameFile);
        }

        //return $this->checkFile($typeFile);
        $df = (new Database())->checkImportList($typeFile, array($nameFile));
        if (!empty($df) && is_numeric($df)) {
            return true;
        }

        return false;
    }

}
