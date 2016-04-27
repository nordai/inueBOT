<?php
/**
 * Telegram Bot for mapping by Nordai s.r.l.
 * @author originale motore Telegram: Gabriele Grillo <gabry.grillo@alice.it> con riadattamento da parte di Matteo Tempestini e Piersoft 
 
 	Componenti base BOT
 	- DB POSTGIS (http://postgis.net/)
	
	Funzionamento base
	- invio location
	- invio toponimo come risposta
	
 */
 
include("Telegram.php");

class mainloop{
 
 function start($telegram,$update)
	{

		date_default_timezone_set('Europe/Rome');
		$today = date("Y-m-d H:i:s");
		
		/* If you need to manually take some parameters
		*  $result = $telegram->getData();
		*  $text = $result["message"] ["text"];
		*  $chat_id = $result["message"] ["chat"]["id"];
		*/
		
		$text = $update["message"] ["text"];
		$chat_id = $update["message"] ["chat"]["id"];
		$user_id=$update["message"]["from"]["id"];
		$location=$update["message"]["location"];
		$reply_to_msg=$update["message"]["reply_to_message"];
		
		$this->shell($telegram,$text,$chat_id,$user_id,$location,$reply_to_msg);

	}

	//gestisce l'interfaccia utente
	function shell($telegram,$text,$chat_id,$user_id,$location,$reply_to_msg)
	{
		date_default_timezone_set('Europe/Rome');
		$today = date("Y-m-d H:i:s");
		
		$db = $this->getdb();
		
		//CHECK umap utente
		$id_map = $this->check_setmap($telegram,$chat_id);
		
		$sql =  "SELECT id_map, umap_id, name_map FROM ".DB_TABLE_MAPS ." WHERE id_map=".$id_map;
				
		$ret = pg_query($db, $sql);
		   if(!$ret){
		      echo pg_last_error($db);
		      exit;
		   } 
				  
		$row = array();
		
		while($res = pg_fetch_row($ret)){
		  	if(!isset($res[0])) continue;
		  		$umap_id = $res[1];
		   		$name_map = $res[2];
		}
		
		$shortUrl= UMAP_URL ."/m/". $umap_id;
          		

			if ($text == "/start") {
				$log=$today. ";new chat started;" .$chat_id. "\n";
				$reply = "Benvenuto. Per conoscere il toponimo sardo, clicca [Invia posizione] dall'icona a forma di graffetta e aspetta una decina di secondi.
				
In qualsiasi momento scrivendo /start ti ripeterò questo messaggio di benvenuto. Usa /help per la guida.

Decliniamo ogni responsabilità dall'uso improprio di questo strumento e dei contenuti degli utenti. 
				
Tutte le info sono sui server Telegram, mentre in un database locale c'è traccia degli eventuali feedback da te inviati";
				$content = array('chat_id' => $chat_id, 'text' => $reply);
				$telegram->sendMessage($content);
			}
			//gestione segnalazioni georiferite
			elseif($location!=null)
			{
				// in modalità manutenzione decommentare sendMessage e commentare location_manager.
				$reply = "TopoBOT work in progress. Stay tuned! :)";
				$content = array('chat_id' => $chat_id, 'text' => $reply);
				//$telegram->sendMessage($content);

				$this->location_manager($telegram,$user_id,$chat_id,$location);
				exit;	
			}
			// aiuto per gli utenti
			elseif ($text == "/help" || $text == "help") {
					 
					 $reply = ("inue BOT e' un servizio per cercare i toponimi nel territorio sardo grazie al riuso degli opendata messi a disposizione dalla Regione Sardegna, realizzato dalla startup Nordai con GeoNue (www.geonue.com)

Per conoscere un toponimo, clicca [Invia posizione] dall'icona a forma di graffetta e aspetta una decina di secondi.

Per impostare il raggio di ricerca: /setdistance
Mappa ricerche: ".URL_UMAP	
	          );
					 $content = array('chat_id' => $chat_id, 'text' => $reply,'disable_web_page_preview'=>true);
					 $telegram->sendMessage($content);
					 $log=$today. ",crediti sent," .$chat_id. "\n";
					 
			}
			// aiuto per gli utenti
			elseif ($text == "/setdistance") {
				
				//nascondo la tastiera e forzo l'utente a darmi una risposta
				$forcehide=$telegram->buildForceReply(true);
					 
				//chiedo di indicare la distanza in metri per la ricerca
				$content = array('chat_id' => $chat_id, 'text' => "[Dimmi la distanza in metri]", 'reply_markup' =>$forcehide, 'reply_to_message_id' =>$bot_request_message_id);
				$bot_request_message=$telegram->sendMessage($content);
					 
			}
			// inserimento contenuto segnalazione
			elseif($reply_to_msg["text"] == "[Dimmi la distanza in metri]")
			{
			    $response=$telegram->getData();
			    $text =$response["message"]["text"];
			    
			    if (is_numeric($text) && is_int(intval($text)) && intval($text) > 1 && intval($text) < MAX_RADIUS+1) {
			    
				    $sql = "UPDATE ". DB_TABLE_USER ." SET distance=".$text." WHERE user_id ='".$chat_id."'";
				    
				    file_put_contents(LOG_FILE, $sql, FILE_APPEND | LOCK_EX);
				    
					$ret = pg_query($db, $sql);
					   
					if(!$ret){
					   echo pg_last_error($db);
					   $reply = pg_last_error($db);
					   exit;
					} else {
						$reply = "La distanza è stata impostata a ".$text." metri.";
					}
				    
			    }
			    else {
			    	
			    	$reply = "La distanza deve essere maggiore di 1 e minore di ".MAX_RADIUS;
			    
			    }
				   
			    $content = array('chat_id' => $chat_id, 'text' => $reply);
				$telegram->sendMessage($content);
			
				
			}
			//comando errato
			else{
				 $reply = "Hai selezionato un comando non previsto";
				 $content = array('chat_id' => $chat_id, 'text' => $reply);
				 $telegram->sendMessage($content);
				 $log=$today. ";wrong command sent;" .$chat_id. "\n";
			 }
						
			//aggiorna tastiera
			//$this->create_keyboard($telegram,$chat_id);
			//log			
			file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);
			
			pg_close($db);
			
	}


	// Crea la tastiera
 	function create_keyboard($telegram, $chat_id)
	{
		$forcehide=$telegram->buildKeyBoardHide(true);
		$content = array('chat_id' => $chat_id, 'text' => "", 'reply_markup' =>$forcehide, 'reply_to_message_id' =>$bot_request_message_id);
		$bot_request_message=$telegram->sendMessage($content);
	}

      //salva la posizione
		function location_manager($telegram,$user_id,$chat_id,$location)
		{
				$lng=$location["longitude"];
				$lat=$location["latitude"];
				$map = "";
				
				//rispondo
				$response=$telegram->getData();
				$bot_request_message_id=$response["message"]["message_id"];
				$time=$response["message"]["date"]; //registro nel DB anche il tempo unix
				
				$h = "1";// Hour for time zone goes here e.g. +7 or -4, just remove the + or -
  				$hm = $h * 60;
  				$ms = $hm * 60;
  				$timec=gmdate("Y-m-d\TH:i:s\Z", $time+($ms));
  				$timec=str_replace("T"," ",$timec);
  				$timec=str_replace("Z"," ",$timec);
  				
  				
					
	  				$db = $this->getdb_topo();
	  				
	  				$sql = "SELECT topoitalia, toposardo FROM ". DB_TABLE_TOPO_MACRO ." WHERE ST_DWithin(ST_Transform(ST_PointFromText('POINT(".$lng." ".$lat.")',4326),900913), geom, 20);";
	  				$ret = pg_query($db, $sql);
					if(!$ret){
						echo pg_last_error($db);
						exit;
					} 
									   
					if (pg_num_rows($ret)) {
						$reply = "\nSei nel comune di ";
						$row = array();
									
					    while($res = pg_fetch_row($ret)){
					    	if(!isset($res[0])) continue;
					    	$reply .= $res[0]."\n\n";
					    	$reply .= "Nome in sardo: ".$res[1]."\n";
					    	break;
					    	//$reply .= "fonte: ".$res[2]."\n\n";
					   	}	
		
					}
					else {
						$reply = "\nOps... mi sa non ti trovi in Sardegna :) \n";
						$content = array('chat_id' => $chat_id, 'text' => $reply);
						$bot_request_message=$telegram->sendMessage($content);
		
						exit;
					}
					
					$sql = "SELECT subregion FROM ". DB_TABLE_TOPO_SUBREGION ." WHERE ST_DWithin(ST_Transform(ST_PointFromText('POINT(".$lng." ".$lat.")',4326),900913), geom, 20);";
					$ret = pg_query($db, $sql);
					if(!$ret){
						echo pg_last_error($db);
						exit;
					} 
									   
					if (pg_num_rows($ret)) {
						$reply .= "Regione storica: ";
						$row = array();
									
					    while($res = pg_fetch_row($ret)){
					    	$reply .= $res[0]."\n";
					    	//$reply .= "fonte: ".$res[2]."\n\n";
					   	}	
		
					}
					
					$rec_user = $this->check_user($chat_id);
						
					$sql = "SELECT testo, descr, fonte FROM ".DB_TABLE_TOPO." WHERE ST_DWithin(ST_Transform(ST_PointFromText('POINT(".$lng." ".$lat.")',4326),3003), geom, ".$rec_user[7].");";
						file_put_contents(LOG_FILE, $sql."\n", FILE_APPEND | LOCK_EX);
				
					$ret = pg_query($db, $sql);
					if(!$ret){
						echo pg_last_error($db);
						exit;
				   	} 
								   
					if (pg_num_rows($ret)) {
						$reply .= "\nEcco i toponimi nel raggio di ".$rec_user[7]." metri:\n\n";
						$row = array();
								
					    while($res = pg_fetch_row($ret)){
					    	$reply .= $res[0]."\n";
					    	$reply .= "[".$res[1]."]\n\n";
					    	//$reply .= "fonte: ".$res[2]."\n\n";
					   	}	
					   	
						$shortUrl = URL_UMAP_ORI ."#".UMAP_ZOOM ."/".$lat."/".$lng;
						$map = "\nMappa: ".$shortUrl;

	
					}
					else {
						
							$reply .= "\nNon sono stati trovati toponimi nel punto specificato\n";
						 	
					}
				

				$content = array('chat_id' => $chat_id, 'text' => $reply."".$map);
				$bot_request_message=$telegram->sendMessage($content);
				
				//memorizzare nel DB
				$obj=json_decode($bot_request_message);
				$id=$obj->result;
				$id=$id->message_id;
				
				$db = $this->getdb();
				
				//CHECK mappa utente
				$id_map = $this->check_setmap($telegram,$user_id);
				
				$text = str_replace('\'', '', str_replace('/\n', '<br/>', $reply));
								
				$sql = "INSERT INTO ". DB_TABLE_GEO. "(lat,lng, iduser,text_msg,bot_request_message,data_time,file_id,file_path,file_type,geom,state,map,distance) VALUES (".$lat.",".$lng.",'".$user_id."','".$text."','".$id."','".$timec."',' ',' ',' ',ST_GeomFromText('POINT(".$lng." ".$lat.")', 4326),0, ".$id_map.", ".$rec_user[7].")";
				
				file_put_contents(LOG_FILE, $sql."\n", FILE_APPEND | LOCK_EX);
				$ret = pg_query($db, $sql);
				if(!$ret){
				   echo pg_last_error($db);
				   $log = $timec.";query;errore inserimento posizione user_id:".$user_id."\n";
				   file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);
				} else {
				   $log = $timec.";query;posizione inserita user_id:".$user_id."\n";
				   file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);
   				}
				
				pg_close($db);

		}
		
		//connessione al DB
		function getdb() {
			// Instances the class		
			$host        = "host=".DB_GEO_HOST;
			$port        = "port=".DB_GEO_PORT;
			$dbname      = "dbname=". DB_GEO_NAME;
			$credentials = "user=".DB_GEO_USER." password=".DB_GEO_PASSWORD;
				
			$db = pg_connect("$host $port $dbname $credentials");
		    return $db;
		}
		
		//connessione al DB TOPO
		function getdb_topo() {
			// Instances the class		
			$host        = "host=".DB_TOPO_HOST;
			$port        = "port=".DB_TOPO_PORT;
			$dbname      = "dbname=". DB_TOPO_NAME;
			$credentials = "user=".DB_TOPO_USER." password=".DB_TOPO_PASSWORD;
				
			$db = pg_connect("$host $port $dbname $credentials");
		    return $db;
		}
		
		// estrapolazione stringa per procedura approvazione segnalazioni
		function get_string_between($string, $start, $end){
		    $string = ' ' . $string;
		    $ini = strpos($string, $start);
		    if ($ini == 0) return '';
		    $ini += strlen($start);
		    $len = strpos($string, $end, $ini) - $ini;
		    return substr($string, $ini, $len);
		}
		
		// verifica se amministratore
		function check_admin($id_user){
			
			$db = $this->getdb();
			
		    $sql =  "SELECT * FROM ".DB_TABLE_USER ." WHERE type_role = 'admin' and user_id = '".$id_user."'";
			
			$ret = pg_query($db, $sql);
			   if(!$ret){
			      echo pg_last_error($db);
			      return false;
			   }
			   
			if (pg_num_rows($ret))
		   		return true;
		    else
		    	return false;
		
		}

		//verifica esistenza utente
		function check_user($id_user){
			
			$db = $this->getdb();
			
		    $sql =  "SELECT * FROM ".DB_TABLE_USER ." WHERE user_id = '".$id_user."'";
			
			$ret = pg_query($db, $sql);
			   if(!$ret){
			      echo pg_last_error($db);
			      return false;
			   }
			   
			if (pg_num_rows($ret)) {
				while($res = pg_fetch_row($ret)){
			    	if(!isset($res[0])) continue;
			    		return $res;
			    }
			}
		    else
		    	return false;
		
		}
		
		//per impostare nuovo utente e sapere l'id della mappa attiva
		function check_setmap($telegram, $id_user, $def_map) {
			
			$db = $this->getdb();
			
			$response=$telegram->getData();
			
			if ($id_user == ID_ADMIN) {
				$username= USER_ADMIN;
				$first_name="";
				$last_name="";
			}
			else {				
				$username=$response["message"]["from"]["username"];
				$first_name=$response["message"]["from"]["first_name"];
				$last_name=$response["message"]["from"]["last_name"];
			}
			
			if (!$this->check_user($id_user)) {
				
				//check mappa di default
				$map_id = $this->check_defaultmap();				
				$sql = "INSERT INTO ". DB_TABLE_USER. "(user_id,type_role,map,alert,first_name,last_name,username,distance) VALUES ('".$id_user."','user',".$map_id.",true,'".$first_name."','".$last_name."','".$username."',200)";
				
				$ret = pg_query($db, $sql);
				if(!$ret){
				   echo pg_last_error($db);
				   $log = $timec.";query;errore inserimento utente user_id:".$id_user."\n";
				   file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);
				} else {
				   $log = $timec.";query;utente inserito user_id:".$id_user."\n";
				   file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);
   				}
   				
   				return $map_id;
			
			}
			else {
				
				$sql = "UPDATE ". DB_TABLE_USER ." SET first_name = '".$first_name."', last_name = '".$last_name."', username = '".$username."'  WHERE user_id ='".$id_user."'";
				
				$ret = pg_query($db, $sql);
				   if(!$ret){
				      echo pg_last_error($db);
				      exit;
				   } 

				$sql =  "SELECT map FROM ". DB_TABLE_USER ." WHERE user_id ='".$id_user."'";
			
				$ret = pg_query($db, $sql);
				   if(!$ret){
				      echo pg_last_error($db);
				      exit;
				   } 
				  
			    $row = array();
			    $i=0;
			    
			    while($res = pg_fetch_row($ret)){
			    	if(!isset($res[0])) continue;
			    		return $res[0];
			    }
		
			}			
			
		}
		
		// controlla quale è la mappa di default
		function check_defaultmap() {
			$db = $this->getdb();
			
			//check mappa di default
				$sql =  "SELECT id_map, umap_id, name_map FROM ".DB_TABLE_MAPS ." WHERE def=true";
						
				$ret = pg_query($db, $sql);
				   if(!$ret){
				      echo pg_last_error($db);
				      exit;
				   } 
						  
				$row = array();
				
				while($res = pg_fetch_row($ret)){
				  	if(!isset($res[0])) continue;
				  		return $res[0];
				}
				
				pg_close($db);

		}
		
		// avvisa gli utenti di una mappa
		function alert_usermap($telegram, $msg, $map_id, $all = false) {
			
			$db = $this->getdb();
			
			$where = "";
			if (!$all) $where = " AND map=".$map_id;
					
			$sql = "SELECT * FROM ".DB_TABLE_USER ." WHERE alert=true ".$where;
				
			$ret = pg_query($db, $sql);
			if(!$ret){
			   echo pg_last_error($db);
			     exit;
			} 
					  
			$bot_request_message_id=$response["message"]["message_id"];
			$forcehide=$telegram->buildForceReply(true);
			while($res = pg_fetch_row($ret)){
			   	$content = array('chat_id' => $res[0], 'reply_markup' => $forcehide, 'text' => $msg);
				$telegram->sendMessage($content);
			}	
			
			pg_close($db);
				    
		}
		
		// avvisa gli utenti di una mappa
		function get_user($user_id) {
			
			$db = $this->getdb();
			
			$sql = "SELECT * FROM ".DB_TABLE_USER ." WHERE user_id= '".$user_id."'";
				
			$ret = pg_query($db, $sql);
			if(!$ret){
			   echo pg_last_error($db);
			     exit;
			} 
				
			while($res = pg_fetch_row($ret)){
			   	if(!isset($res[0])) continue;
			    	return $res;
			}	  
			
			pg_close($db);
				    
		}

		
		// Remove all special characters from a string
		function clean($string) {
		   $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
		   return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
		}
				
		
}

?>
