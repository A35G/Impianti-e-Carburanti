<?php

/**
 * List of message for Logs or User response
 * -----------------------------------------
 * 
 * Elenco personalizzabile delle risposte la-
 * to client o dei messaggi stampati all'int-
 * erno dei logs generati dal Sistema.
 * 
 * vers. 0.1.2
 */

$msg["folder"]["noReadPermission"] = "La cartella '%s' non esiste o non si hanno i permessi di lettura.";
$msg["folder"]["noWritePermission"] = "La cartella di destinazione del Download (%s) non ha i permessi di scrittura.";

$msg["file"]["notFoundNoPermission"] = "Il file '%s' non esiste o non si hanno i permessi di lettura.";
$msg["file"]["emptyRow"] = "La riga #%d, risulta vuota.";
$msg["file"]["wrongColumn"] = "La riga #%d, presenta un numero di colonne differenti da quanto previsto (%d invece di %d).";
$msg["file"]["noArchive"] = "Errore durante l'archiviazione del file '%s'.";
$msg["file"]["cleanSuccess"] = "Eseguita operazione di pulizia sul file.";
$msg["file"]["cleanError"] = "Impossibile effettuare operazioni di pulizia sul file scaricato - Non si dispone dei permessi di scrittura per il file '%s'.";
$msg["file"]["emptyDownload"] = "Il file scaricato '%s' risulta vuoto.";
$msg["file"]["successDownload"] = "File '%s' scaricato con successo nella cartella di destinazione (%s).";
$msg["file"]["initParse"] = "Inizio elaborazione file '%s': %s";
$msg["file"]["endParse"] = "Inizio elaborazione file '%s': %s";

$msg["system"]["cUrlError"] = "Errore cURL: %s";
$msg["system"]["downloadError"] = "Errore nel Download: %s";
$msg["system"]["noFileDownload"] = "Errore durante il Download del file '%s'.";
$msg["system"]["noAnomalyFile"] = "Errore durante la generazione del file '%s' con le Anomalie riscontrate";

$msg["config"]["noFile"] = "Il file di config non esiste.";
$msg["config"]["errorFile"] = "Il file di configurazione non sembra formattato correttamente.";
$msg["config"]["noUrlDownload"] = "Tipologia '%s': URL per il download del file non disponibile o non valorizzato all'interno del file di configurazione.";

$msg["db"]["noparams"] = "Non esistono parametri per la connessione al Database.";
$msg["db"]["noconnect"] = "Errore durante la connessione al Database.";
$msg["db"]["queryError"] = "Errore nella query: %s";
$msg["db"]["noArchiveClean"] = "Nessun file da rimuovere dall'Archivio digitale degli elaborati (%s).";
$msg["db"]["archiveClean"] = "Sono stati rimossi %d file dall'Archivio digitale degli elaborati (%s).";
$msg["db"]["noSaveRow"] = "Le informazioni della riga #%d, non sono state salvate/aggiornate all'interno del Database.";

$msg["res"]["nosez"] = json_encode(array("code" => 404, "text" => "Nessuna sezione specificata"));
$msg["res"]["notype"] = json_encode(array("code" => 422, "text" => "Nessuna Tipologia specificata"));
$msg["res"]["badtype"] = json_encode(array("code" => 401, "text" => "Tipologia di File non valida"));
$msg["res"]["errdown"] = json_encode(array("code" => 417, "text" => "Errore durante le operazioni di Download del file"));
$msg["res"]["sucdown"] = json_encode(array("code" => 200, "text" => "Download eseguito con successo"));
