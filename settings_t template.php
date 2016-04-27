<?php
//modulo delle KEYs per funzionamento dei bot (Template)

// Telegram
define('TELEGRAM_BOT','');
define('BOT_WEBHOOK', '');
define('LOG_FILE', 'telegram.log');

// DB BOT Telegram
define('DB_GEO_HOST', "127.0.0.1");
define('DB_GEO_PORT', "5432");
define('DB_GEO_NAME', "geonue_bot");
define('DB_GEO_USER', "");
define('DB_GEO_PASSWORD', "");

define('DB_TABLE_USER',"utenti");
define('DB_TABLE_GEO',"segnalazioni");
define('DB_TABLE_MAPS',"mappe");
define('DB_TABLE_STATE',"stato");
define('DB_ERR', "errore database POSTGIS");

// DB TOPO
define('DB_TOPO_HOST', "127.0.0.1");
define('DB_TOPO_PORT', "5432");
define('DB_TOPO_NAME', "geonue");
define('DB_TOPO_USER', "");
define('DB_TOPO_PASSWORD', "");

define('DB_TABLE_TOPO',"");
define('DB_TABLE_TOPO_MACRO',"");
define('DB_TABLE_TOPO_SUBREGION',"");

define('URL_UMAP',"");
define('URL_UMAP_ORI',"");
define('UMAP_ZOOM',"");

?>
