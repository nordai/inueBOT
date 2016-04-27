CREATE TABLE mappe
(
  id_map serial NOT NULL,
  name_map text,
  enabled boolean,
  umap_id integer,
  def boolean,
  private boolean,
  author text,
  password text,
  mymap boolean,
  desc_mappa text,
  CONSTRAINT id_pk_map PRIMARY KEY (id_map)
)

CREATE TABLE segnalazioni
(
  iduser text,
  bot_request_message text NOT NULL,
  text_msg text,
  file_id text,
  file_type text,
  file_path text,
  lat double precision,
  lng double precision,
  geom geometry,
  state integer,
  id serial NOT NULL,
  data_time timestamp without time zone,
  map integer,
  CONSTRAINT id_pk PRIMARY KEY (id)
)

CREATE TABLE utenti
(
  user_id text NOT NULL,
  type_role text,
  map integer,
  alert boolean,
  first_name text,
  last_name text,
  username text,
  distance integer,
  CONSTRAINT user_pk PRIMARY KEY (user_id)
)

CREATE OR REPLACE VIEW topobot_v AS 
 SELECT (('Richiesta n. '::text || se.bot_request_message) || ' - '::text) || ut.username AS name,
    se.bot_request_message,
    se.data_time,
    ut.first_name,
    ut.username,
    se.text_msg,
    se.geom
   FROM segnalazioni se
     JOIN mappe mp ON mp.id_map = se.map AND mp.enabled = true
     JOIN utenti ut ON se.iduser = ut.user_id;
