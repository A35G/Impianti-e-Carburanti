# Impianti e Carburanti
Elaborazione e Visualizzazione delle informazioni relative agli Impianti di distribuzione Italiani e ai prezzi praticati dei carburanti per autotrazione.

Le informazioni sulle Anagrafiche degli Impianti di distribuzione e sui prezzi, sono fornite quotidianamente tramite i dataset pubblicati dal [Ministero delle Imprese e del Made in Italy](https://www.mimit.gov.it/it/open-data/elenco-dataset/carburanti-prezzi-praticati-e-anagrafica-degli-impianti, "Ministero delle Imprese e del Made in Italy") e sono in vigore alle ore 8 del giorno precedente a quello di pubblicazione.

Per la ricerca e la consultazione in tempo reale dei prezzi praticati e la ricerca degli impianti è possibile consultare il sito dell’[Osservatorio prezzi carburanti](https://carburanti.mise.gov.it/ospzSearch/home "Osservatorio prezzi carburanti").

# Utilizzo
Anche se in fase di sviluppo, l'Ambiente è già funzionante e può essere implementato in locale o su un server di Test su cui effettuare interrogazioni semplici oltre che abilitare processi Cron per il download e l'elaborazione automatica dei file.

- Installa [Composer](https://getcomposer.org/ "Composer's Homepage")

- Esegui il seguente comando nella **root** del progetto:
  ```
  composer install
  ```

- Modifica le impostazioni all'interno del file **Config.php**

# Endpoint

- `/download/{tipologia}`

Download dei File suddivisi per Tipologia:

 	- impianti
    Per ottenere il file contenente le informazioni sugli Impianti di Rifornimento

	- prezzi
 	Per ottenere il file contenente i prezzi praticati all'interno delle Stazioni di Servizio suddivisi per tipo di carburante e servizio
