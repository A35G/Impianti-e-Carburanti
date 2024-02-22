<?php

namespace App\Management;

use App\Error\Report;
use mysqli;

class Database
{
    public $config = array();

    private $msg = array();

    private $db;
    private $dbs;

    private $rootDir;

    public function __construct()
    {
        $this->rootDir = realpath(__DIR__ . "/../../");

        $this->msg = $this->loadText();

        $this->config = $this->get_config();

        $this->connectDB($this->config["appDB"]);

        $darm = ($this->config["use2db"]) ? $this->config["syncDB"] : $this->config["appDB"];

        $this->connectDB2Sync($darm);
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

    private function get_config()
    {
        static $config;

        if (empty($config)) {
            $appDir = realpath(__DIR__ . "/../");
            $file_path = $appDir . '/Config/Config.php';
            $found = false;
            if (file_exists($file_path)) {
                $found = true;
                require $file_path;
            }

            if (!$found) {
                $err = $this->msg["config"]["noFile"];
                (new Report())->saveLog($err, "error");
                exit(0);
            }

            if (!isset($config) OR !is_array($config)) {
                $err = $this->msg["config"]["errorFile"];
                (new Report())->saveLog($err, "error");
                exit(0);
            }
        }

        return $config;
    }

    private function connectDB(array $params = [])
    {
        if (empty($params)) {
            $err = $this->msg["db"]["noparams"];
            (new Report())->saveLog($err, "error");
        }

        $hostname = $params["hostname"];
        $username = $params["username"];
        $password = $params["password"];
        $database = $params["database"];

        if ($params["debug"]) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        }

        $this->db = new mysqli($hostname, $username, $password, $database);
        if ($this->db->connect_error) {
            $err = $this->msg["db"]["noconnect"];
            (new Report())->saveLog($err, "error");
            exit(0);
        }
    }

    private function connectDB2Sync(array $params = [])
    {
        if (empty($params)) {
            $err = $this->msg["db"]["noparams"];
            (new Report())->saveLog($err, "error");
        }

        $hostname = $params["hostname"];
        $username = $params["username"];
        $password = $params["password"];
        $database = $params["database"];

        if ($params["debug"]) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        }

        $this->dbs = new mysqli($hostname, $username, $password, $database);
        if ($this->dbs->connect_error) {
            $err = $this->msg["db"]["noconnect"];
            (new Report())->saveLog($err, "error");
            exit(0);
        }
    }

    /**
     * Generazione GUID/UUID
     */
    private function makeUUIDv4()
    {
        $data = random_bytes(16);
        assert(strlen($data) == 16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Controllo se il file indicato esiste all'interno del Database; in caso
     * negativo, lo salvo e lo inserisco in lista di elaborazione
     */
    private function checkIfExistsInDB(string $typeFile, string $nameImport)
    {
        switch ($typeFile) {
            case "impianti":
                $ntable = "stationfile";
                break;
            case "prezzi":
                $ntable = "fuelfile";
                break;
        }

        $sq1 = "SELECT codiceInterno FROM %s WHERE nomeFile = '%s'";

        $sq1 = vsprintf($sq1, [
            $ntable,
            $this->db->real_escape_string($nameImport)
        ]);

        $qr1 = $this->db->query($sq1);
        if ($qr1->num_rows === 0) {
            $fileCode = "";
            $genCode = true;
            while ($genCode) {
                $fileCode = $this->makeUUIDv4();
                $sq2 = "SELECT nomeFile FROM %s WHERE codiceInterno = '%s'";

                $sq2 = vsprintf($sq2, [
                    $ntable,
                    $this->db->real_escape_string($fileCode)
                ]);

                $qr2 = $this->db->query($sq2);
                if ($qr2->num_rows === 0) {
                    $genCode = false;
                }
            }

            $this->db->begin_transaction();

            $dbArgs = array(
                $ntable,
                $this->db->real_escape_string($nameImport),
                date("Y-m-d H:i:s"),
                $this->db->real_escape_string($fileCode)
            );

            $sq3 = "INSERT INTO %s(nomeFile,dataImport,codiceInterno) ";
            $sq3 .= "VALUES('%s','%s','%s')";

            $sq3 = vsprintf($sq3,$dbArgs);

            if (!$this->db->query($sq3)) {
                $errm = sprintf($this->msg["db"]["queryError"], $this->db->error);
                (new Report())->saveLog($errm, "error");

                $this->db->rollback();
            }

            $this->db->commit();

            return true;
        }

        return false;
    }

    /**
     * Controllo elenco dei file rilevati nella cartella di "import"
     * suddivisi per Tipologia.
     */
    public function checkImportList(string $typeFile, array $lsFile = [])
    {
        if (empty($lsFile)) {
            return false;
        }

        $newFile = 0;
        foreach ($lsFile as $impf)
        {
            if ($this->checkIfExistsInDB($typeFile, $impf)) {
                ++$newFile;
            }
        }

        return $newFile;
    }

    /**
     * Controllo la presenza di file CSV da elaborare in base alla Tipologia
     */
    public function checkToImportData(string $typeFile)
    {
        switch ($typeFile) {
            case "impianti":
                $sq1 = "SELECT nomeFile, codiceInterno FROM stationfile WHERE";
                $sq1 .= " statusFile = %d ORDER BY dataImport DESC LIMIT 1";

                $sq1 = vsprintf($sq1, [0]);
                break;
            case "prezzi":
                $sq1 = "SELECT nomeFile, codiceInterno FROM fuelfile WHERE";
                $sq1 .= " statusFile = %d ORDER BY dataImport DESC LIMIT 1";

                $sq1 = vsprintf($sq1, [0]);
                break;
        }

        $qr1 = $this->db->query($sq1);
        if ($qr1->num_rows > 0) {
            $rw1 = $qr1->fetch_array(MYSQLI_ASSOC);
            return array(
                "nameFile"  =>  $rw1["nomeFile"],
                "codeFile"  =>  $rw1["codiceInterno"]
            );
        }

        return false;
    }

    /**
     * Aggiorno lo "status" del File su cui sto operando
     */
    public function saveDataFile(string $typeFile, array $params = [])
    {
        if (!empty($params)) {
            switch ($typeFile) {
                case "impianti":
                    $nameTable = "stationfile";
                    break;
                case "prezzi":
                    $nameTable = "fuelfile";
                    break;
            }

            $dataUpdate = 0;
            switch ($params[0]) {
                case "error":
                    $dbArgs = array(
                        $nameTable,
                        $params[2],
                        $params[3],
                        $params[4],
                        $params[1]
                    );

                    $sq1 = "UPDATE %s SET dataElaborazione = '%s',";
                    $sq1 .= " statusFile = %d,erroreFile = '%s' WHERE ";
                    $sq1 .= "codiceInterno = '%s'";

                    $sq1 = vsprintf($sq1, $dbArgs);
                    break;
                case "endWork":
                    $dbArgs = array(
                        $nameTable,
                        $params[2],
                        $params[4],
                        $params[6],
                        $params[5],
                        $params[3],
                        $params[1]
                    );

                    $sq1 = "UPDATE %s SET dataElaborazione = '%s',totaleRighe = %d,";
                    $sq1 .= "totaleRigheElaborate = %d,totaleRigheConErrore = %d,";
                    $sq1 .= "statusFile = %d WHERE codiceInterno = '%s'";

                    $sq1 = vsprintf($sq1, $dbArgs);
                    break;
            }

            $this->db->query($sq1);
            $dataUpdate = $this->db->affected_rows;

            if (!empty($dataUpdate)) {
                $this->db->query("OPTIMIZE TABLE " . $nameTable);

                switch ($typeFile) {
                    case "impianti":
                        if ($params[0] === "error") {
                            $this->db->query("TRUNCATE TABLE datastation_temp");
                            $this->db->query("OPTIMIZE TABLE datastation_temp");
                        }

                        if ($params[0] === "endWork") {
                            $this->db->begin_transaction();

                            if (!$this->db->query("TRUNCATE TABLE datastation")) {
                                $errm = sprintf($this->msg["db"]["queryError"],
                                    $this->db->error);

                                (new Report())->saveLog($errm, "error");

                                $this->db->rollback();
                            }

                            $sqx = "INSERT datastation SELECT * FROM datastation_temp";
                            if (!$this->db->query($sqx)) {
                                $errm = sprintf($this->msg["db"]["queryError"],
                                    $this->db->error);

                                (new Report())->saveLog($errm, "error");

                                $this->db->rollback();
                            }

                            if (!$this->db->query("TRUNCATE TABLE datastation_temp")) {
                                $errm = sprintf($this->msg["db"]["queryError"],
                                    $this->db->error);

                                (new Report())->saveLog($errm, "error");

                                $this->db->rollback();
                            }

                            $this->db->commit();
                        }
                        break;
                    case "prezzi":
                        if ($params[0] === "error") {
                            $this->dbs->query("TRUNCATE TABLE datafuel_temp");
                            $this->dbs->query("OPTIMIZE TABLE datafuel_temp");
                        }

                        if ($params[0] === "endWork") {
                            $this->dbs->begin_transaction();

                            if (!$this->db->query("TRUNCATE TABLE datafuel")) {
                                $errm = sprintf($this->msg["db"]["queryError"],
                                    $this->db->error);

                                (new Report())->saveLog($errm, "error");

                                $this->db->rollback();
                            }

                            $sqy = "SELECT * FROM datafuel_temp";
                            $qry = $this->dbs->query($sqy);
                            if ($qry->num_rows > 0) {
                                while ($rwy = $qry->fetch_array(MYSQLI_ASSOC)) {
                                    $agm = array(
                                        $rwy["idImpianto"],
                                        $rwy["tipoCarburante"],
                                        $rwy["prezzo"],
                                        intval($rwy["self"]),
                                        $rwy["dataComunicazione"],
                                        $rwy["dataElaborazione"],
                                        $rwy["codiceInterno"]
                                    );

                                    $sqn = "INSERT INTO datafuel(idImpianto,";
                                    $sqn .= "tipoCarburante,prezzo,self,dataCom";
                                    $sqn .= "unicazione,dataElaborazione,codice";
                                    $sqn .= "Interno) VALUES('%s','%s','%s',%d,";
                                    $sqn .= "'%s','%s','%s')";

                                    $sqn = vsprintf($sqn, $agm);

                                    if (!$this->db->query($sqn)) {
                                        $errm = sprintf($this->msg["db"]["queryError"],
                                            $this->db->error);

                                        (new Report())->saveLog($errm, "error");

                                        $this->db->rollback();
                                    }
                                }
                            }

                            if (!$this->dbs->query("TRUNCATE TABLE datafuel_temp")) {
                                $errm = sprintf($this->msg["db"]["queryError"],
                                    $this->dbs->error);

                                (new Report())->saveLog($errm, "error");

                                $this->dbs->rollback();
                            }

                            $this->dbs->commit();
                        }
                        break;
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Salvataggio Informazioni del prezzo del Carburante nella Tabella
     * Temporanea durante l'elaborazione del contenuto del file CSV
     */
    public function saveDataRowPrice(string $dataPrice)
    {
        $dsm = json_decode($dataPrice, true);
        if (!empty($dsm)) {
            $this->dbs->begin_transaction();

            $sq0 = "CREATE TABLE IF NOT EXISTS datafuel_temp AS SELECT * FROM datafuel";
            if (!$this->dbs->query($sq0)) {
                $this->dbs->rollback();
            }

            $this->dbs->commit();

            $rowCode = "";
            $genCode = true;
            while ($genCode) {
                $rowCode = $this->makeUUIDv4();
                $sq3 = "SELECT COUNT(idImpianto) AS rwx FROM datafuel_temp ";
                $sq3 .= "WHERE codiceInterno = '%s'";

                $sq3 = vsprintf($sq3,[$this->dbs->real_escape_string($rowCode)]);

                $qr3 = $this->dbs->query($sq3);
                if ($qr3->num_rows === 0) {
                    $genCode = false;
                } else {
                    $rw3 = $qr3->fetch_array(MYSQLI_ASSOC);
                    if (empty($rw3["rwx"])) {
                        $genCode = false;
                    }
                }
            }

            $tx = list($datac, $orac) = explode(" ", $dsm["data"]);
            if (!empty($datac)) {
                $dtc = substr($datac, 6, 4) . "-" . substr($datac, 3, 2);
                $dtc .= "-" . substr($datac, 0,2);
                $dtc .= " " . $orac;
            } else {
                $dtc = "0000-00-00 00:00:00";
            }

            $argsDB = array(
                $this->db->real_escape_string($dsm["impianto"]),
                $this->db->real_escape_string($dsm["tipo"]),
                $this->db->real_escape_string($dsm["prezzo"]),
                $dsm["self"],
                $dtc,
                date("Y-m-d H:i:s"),
                $this->db->real_escape_string($rowCode)
            );

            $sq4 = "INSERT INTO datafuel_temp(idImpianto,tipoCarburante,";
            $sq4 .= "prezzo,self,dataComunicazione,dataElaborazione,";
            $sq4 .= "codiceInterno) VALUES('%s','%s','%s','%d','%s','%s','%s')";

            $sq4 = vsprintf($sq4,$argsDB);

            if (!$this->dbs->query($sq4)) {
                $errm = sprintf($this->msg["db"]["queryError"], $this->dbs->error);
                
                (new Report())->saveLog($errm, "error");

                $this->dbs->rollback();
            }

            $this->dbs->commit();

            return true;
        }

        return false;
    }

    /**
     * Salvataggio Informazioni dell'Impianto di Rifornimento nella Tabella
     * Temporanea durante l'elaborazione del contenuto del file CSV
     */
    public function saveDataRow(array $params)
    {
        $params = array_map('trim', $params);

        $this->db->begin_transaction();

        $sq0 = "CREATE TABLE IF NOT EXISTS datastation_temp AS SELECT * FROM datastation";
        if (!$this->db->query($sq0)) {
            $this->db->rollback();
        }

        $this->db->commit();

        $argsDB = array(
            $this->db->real_escape_string($params[0]),
            $this->db->real_escape_string($params[1]),
            $this->db->real_escape_string($params[2]),
            $this->db->real_escape_string($params[3]),
            $this->db->real_escape_string($params[4]),
            $this->db->real_escape_string($params[5]),
            $this->db->real_escape_string($params[6]),
            $this->db->real_escape_string($params[7]),
            $this->db->real_escape_string($params[8]),
            $this->db->real_escape_string($params[9]),
            $this->db->real_escape_string($params[8]),
            $this->db->real_escape_string($params[9])
        );

        $sq1 = "SELECT codiceInterno FROM datastation_temp WHERE idImpianto = '%s'";

        $sq1 = vsprintf($sq1,[$this->db->real_escape_string($params[0])]);

        $qr1 = $this->db->query($sq1);
        if ($qr1->num_rows > 0) {
            $rw1 = $qr1->fetch_array(MYSQLI_ASSOC);

            $argsDB[] = date("Y-m-d H:i:s");
            $argsDB[] = $rw1["codiceInterno"];

            $sq2 = "UPDATE datastation_temp SET idImpianto = '%s',nomeGestore = '%s',";
            $sq2 .= "labelImpianto = '%s',tipoImpianto = '%s',nomeImpianto = '%s',";
            $sq2 .= "indirizzoImpianto = '%s',comuneImpianto = '%s',";
            $sq2 .= "provinciaImpianto = '%s',latitudineImpianto = '%s',";
            $sq2 .= "longitudineImpianto = '%s',coordinate = POINT(%f, %f),dat";
            $sq2 .= "aUltimoAggiornamento = '%s' WHERE codiceInterno = '%s'";

            $sq2 = vsprintf($sq2, $argsDB);

            if (!$this->db->query($sq2)) {
                $errm = sprintf($this->msg["db"]["queryError"], $this->db->error);
                (new Report())->saveLog($errm, "error");

                $this->db->rollback();
            } else {
                $rs2 = $this->db->affected_rows;
            }

            $this->db->commit();

            if (is_numeric($rs2) && !empty($rs2)) {
                return true;
            }
        } else {
            $rowCode = "";
            $genCode = true;
            while ($genCode) {
                $rowCode = $this->makeUUIDv4();
                $sq2 = "SELECT idImpianto FROM datastation_temp WHERE";
                $sq2 .= " codiceInterno = '%s'";

                $sq2 = vsprintf($sq2, [$this->db->real_escape_string($rowCode)]);

                $qr2 = $this->db->query($sq2);
                if ($qr2->num_rows === 0) {
                    $genCode = false;
                }
            }

            $argsDB[] = date("Y-m-d H:i:s");
            $argsDB[] = $rowCode;

            $sq3 = "INSERT INTO datastation_temp(idImpianto,nomeGestore,labelImpianto";
            $sq3 .= ",tipoImpianto,nomeImpianto,indirizzoImpianto,comuneImpianto";
            $sq3 .= ",provinciaImpianto,latitudineImpianto,longitudineImpianto,";
            $sq3 .= "coordinate,dataInserimento,codiceInterno) VALUES('%s',";
            $sq3 .= "'%s','%s','%s','%s','%s','%s','%s','%s','%s',POINT(%f, %f";
            $sq3 .= "),'%s','%s')";

            $sq3 = vsprintf($sq3, $argsDB);

            if (!$this->db->query($sq3)) {
                $errm = sprintf($this->msg["db"]["queryError"], $this->db->error);
                (new Report())->saveLog($errm, "error");

                $this->db->rollback();
            }

            $this->db->commit();

            return true;
        }

        return false;
    }

    /**
     * Clean process
     */
    public function checkArchiveAndClean(int $ndays)
    {
        $waitDays = intval($ndays);
        if (is_numeric($waitDays) && !empty($waitDays)) {
            $sq0 = "SELECT codiceInterno, nomeFile FROM stationfile WHERE ";
            $sq0 .= "DATE(dataElaborazione) <= (DATE(NOW()) - INTERVAL %d DAY)";
            $sq0 .= " AND statusFile = %d AND isRemoved = %d";

            $sq0 = vsprintf($sq0, [$waitDays, 1, 0]);

            $qr0 = $this->db->query($sq0);
            if ($qr0->num_rows > 0) {
                $lsRem0 = array();
                while ($rw0 = $qr0->fetch_array(MYSQLI_ASSOC)) {
                    $pfl0 = $this->rootDir . "/public/archive/impianti/";
                    $pfl0 .= $rw0["nomeFile"];

                    if (file_exists($pfl0)) {
                        $dlt0 = @unlink($pfl0);
                        if ($dlt0 !== false) {
                            $lsRem0[] = $rw0["codiceInterno"];
                        }
                    }
                }

                if (!empty($lsRem0)) {
                    $nmRm0 = 0;
                    foreach ($lsRem0 as $cd0) {
                        $sq2 = "UPDATE stationfile SET isRemoved = %d WHERE ";
                        $sq2 .= "codiceInterno = '%s'";

                        $sq2 = vsprintf($sq2, [1, $cd0]);

                        $this->db->query($sq2);
                        if (!empty($this->db->affected_rows)) {
                            ++$nmRm0;
                        }
                    }

                    if (!empty($nmRm0)) {
                        $this->db->query("OPTIMIZE TABLE stationfile");

                        $msg = sprintf($this->msg["archiveClean"],
                            $nmRm0,
                            "impianti"
                        );

                        (new Report())->saveLog($msg);
                    }
                }
            } else {
                $msg = sprintf($this->msg["noArchiveClean"], "impianti");
                (new Report())->saveLog($msg);
            }

            #

            $sq1 = "SELECT codiceInterno, nomeFile FROM fuelfile WHERE ";
            $sq1 .= "DATE(dataElaborazione) <= (DATE(NOW()) - INTERVAL %d DAY)";
            $sq1 .= " AND statusFile = %d AND isRemoved = %d";

            $sq1 = vsprintf($sq1, [$waitDays, 1, 0]);

            $qr1 = $this->db->query($sq1);
            if ($qr1->num_rows > 0) {
                $lsRem1 = array();
                while ($rw1 = $qr1->fetch_array(MYSQLI_ASSOC)) {
                    $pfl1 = $this->rootDir . "/public/archive/prezzi/";
                    $pfl1 .= $rw1["nomeFile"];

                    if (file_exists($pfl1)) {
                        $dlt1 = @unlink($pfl1);
                        if ($dlt1 !== false) {
                            $lsRem1[] = $rw1["codiceInterno"];
                        }
                    }
                }

                if (!empty($lsRem1)) {
                    $nmRm1 = 0;
                    foreach ($lsRem1 as $cd1) {
                        $sq3 = "UPDATE fuelfile SET isRemoved = %d WHERE ";
                        $sq3 .= "codiceInterno = '%s'";

                        $sq3 = vsprintf($sq3, [1, $cd1]);

                        $this->db->query($sq3);
                        if (!empty($this->db->affected_rows)) {
                            ++$nmRm1;
                        }
                    }

                    if (!empty($nmRm1)) {
                        $this->db->query("OPTIMIZE TABLE fuelfile");

                        $msg = sprintf($this->msg["archiveClean"],
                            $nmRm1,
                            "prezzi"
                        );

                        (new Report())->saveLog($msg);
                    }
                }
            } else {
                $msg = sprintf($this->msg["noArchiveClean"], "prezzi");
                (new Report())->saveLog($msg);
            }
        }
    }

    /**
     * Service Station List
     */
    public function loadDataStation(int $fpage)
    {
        $response = array(
            "totalRow"      =>  0,
            "totalPages"    =>  0,
            "list"          =>  array()
        );

        $nmx = "SELECT COUNT(idImpianto) AS totr FROM datastation";
        $qrx = $this->db->query($nmx);
        if ($qrx->num_rows > 0) {
            $rwx = $qrx->fetch_array(MYSQLI_ASSOC);
            $response["totalRow"] = intval($rwx["totr"]);
        }

        $response["totalPages"] = ceil($response["totalRow"] / $this->config["forpage"]);

        $perpage = intval($this->config["forpage"]);
        $start = (($fpage - 1) * $perpage);

        $sq0 = "SELECT idImpianto,nomeGestore,nomeImpianto,labelImpianto,tipo";
        $sq0 .= "Impianto,indirizzoImpianto,comuneImpianto,provinciaImpianto,";
        $sq0 .= "latitudineImpianto,longitudineImpianto FROM datastation ";
        $sq0 .= "WHERE latitudineImpianto != 'NULL' AND longitudine";
        $sq0 .= "Impianto != 'NULL' ORDER BY nomeGestore ASC LIMIT %d,%d";

        $sq0 = vsprintf($sq0, [$start,$perpage]);

        $qr0 = $this->db->query($sq0);
        if ($qr0->num_rows > 0) {
            while ($rw0 = $qr0->fetch_array(MYSQLI_ASSOC)) {
                $map = "https://www.openstreetmap.org/#map=18/";
                $map .= $rw0["latitudineImpianto"] . "/";
                $map .= $rw0["longitudineImpianto"];

                $mapg = "https://www.google.it/maps/?q=";
                $mapg .= $rw0["latitudineImpianto"] . ",";
                $mapg .= $rw0["longitudineImpianto"];

                $fprice = array();

                $sq1 = "SELECT tipoCarburante,prezzo,self FROM datafuel";
                $sq1 .= " WHERE idImpianto = '%s'";

                $sq1 = vsprintf($sq1, [$rw0["idImpianto"]]);

                $qr1 = $this->db->query($sq1);
                if ($qr1->num_rows > 0) {
                    while ($rw1 = $qr1->fetch_array(MYSQLI_ASSOC)) {

                        $valp = number_format($rw1["prezzo"], 3, ',', '.');
                        $valp .= " &euro;";

                        $fprice[] = array(
                            "tipologia" =>  $rw1["tipoCarburante"],
                            "prezzo"    =>  $valp,
                            "isSelf"    =>  boolval($rw1["self"])
                        );
                    }
                }

                $response["list"][] = array(
                    "idImpianto"        =>  $rw0["idImpianto"],
                    "nomeGestore"       =>  $rw0["nomeGestore"],
                    "nomeImpianto"      =>  $rw0["nomeImpianto"],
                    "bandieraImpianto"  =>  $rw0["labelImpianto"],
                    "tipoImpianto"      =>  $rw0["tipoImpianto"],
                    "provinciaImpianto" =>  $rw0["provinciaImpianto"],
                    "latImpianto"       =>  $rw0["latitudineImpianto"],
                    "longImpianto"      =>  $rw0["longitudineImpianto"],
                    "openStreetMap"     =>  $map,
                    "GoogleMaps"        =>  $mapg,
                    "fuelPrice"         =>  $fprice,
                    "paginaImpianto"    =>  intval($fpage)
                );
            }
        }

        return json_encode($response);
    }

}
