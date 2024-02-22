<?php

/**
 * Web App URL
 * -----------
 * 
 * URL base dell'Ambiente di elaborazione e visualizzazione delle informazioni.
 * 
 */
$config["appUrl"] = "";

/**
 * Database Settings
 * -----------------
 * 
 * Impostazioni per la connessione al Database principale dell'Applicazione Web
 * in cui saranno memorizzati i dati utili alla consultazione.
 * 
 */
$config["appDB"]["hostname"] = "";
$config["appDB"]["username"] = "";
$config["appDB"]["password"] = "";
$config["appDB"]["database"] = "";
$config["appDB"]["debug"] = true;

/**
 * Use another database to handle file processing
 * ----------------------------------------------
 * 
 * Impostando a "true" questo parametro, l'Applicazione utilizzerà un secondo
 * Database per l'elaborazione delle informazioni prelevate dal Portale del 
 * Ministero prima di sovrascrivere i dati già presenti ottenuti
 * dall'elaborazione precedente.
 * 
 */
$config["use2db"] = false;

/**
 * Database Settings
 * -----------------
 * 
 * Impostazioni per la connessione al Database secondario dell'Applicazione Web
 * in cui saranno salvati temporaneamente i dati ottenuti dall'elaborazione dei
 * file in formato CSV, prelevati dal Portale del Ministero.
 * 
 */
$config["syncDB"]["hostname"] = "";
$config["syncDB"]["username"] = "";
$config["syncDB"]["password"] = "";
$config["syncDB"]["database"] = "";
$config["syncDB"]["debug"] = true;

/**
 * External CSV
 * ------------
 * 
 * URL dei file prodotti dal Ministero e necessari a mantenere aggiornate le
 * informazioni all'interno dell'Applicazione Web.
 * 
 */
$config["extLink"]["fuelprice"] = "https://www.mimit.gov.it/images/exportCSV/prezzo_alle_8.csv";
$config["extLink"]["activestation"] = "https://www.mimit.gov.it/images/exportCSV/anagrafica_impianti_attivi.csv";

/**
 * Remove CSV archive history
 * --------------------------
 * 
 * Scegliere se svuotare o meno l'archivio dei file scaricati ed elaborati.
 * Impostando a "true" questo parametro, l'Applicazione tramite un'apposita
 * funzione permetterà di eseguire una pulizia all'interno dello Storico delle
 * operazioni di Download, Processing e Import dei dati.
 * 
 */
$config["rmvfiles"] = true;

/**
 * Historical Days Archive
 * -----------------------
 * 
 * Numero massimo di giorni utili a conservare i file scaricati dal Ministero
 * prima di procedere (se abilitata) alla loro rimozione.
 * 
 * Impostare la variabile a 0 (zero) o lasciarla vuota per rimuovere il file
 * (se abilita l'operazione) e le sue informazioni, successivamente al
 * download e all'elaborazione.
 * 
 */
$config["rmvdays"] = 7;

/**
 * Results for page
 * ----------------
 * 
 * Numero di risultati da visualizzare nella richiesta dell'elenco degli Impian-
 * ti di Distribuzione di Carburante.
 * 
 * Se la variabile non viene settata (lasciata vuota), di default saranno resti-
 * tuiti 20 (venti) risultati per pagina.
 * 
 */
$config["forpage"] = 20;
