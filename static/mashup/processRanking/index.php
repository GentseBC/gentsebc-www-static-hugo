<?php
include_once '../lib/epiphany-20130912/Epi.php';
include_once '../../wp-config.php';
Epi::setPath('base', '../lib/epiphany-20130912');
Epi::init('route','database');
EpiDatabase::employ('mysql',constant('DB_NAME'),constant('DB_HOST'),constant('DB_USER'),constant('DB_PASSWORD')); // type = mysql, database = mysql, host = localhost, user = root, password = [empty]
Epi::setSetting('exceptions', true);

//Epi::init('base','cache','session');
// Epi::init('base','cache-apc','session-apc');
// Epi::init('base','cache-memcached','session-apc');

/*
 * This is a sample page whch uses EpiCode.
 * There is a .htaccess file which uses mod_rewrite to redirect all requests to index.php while preserving GET parameters.
 * The $_['routes'] array defines all uris which are handled by EpiCode.
 * EpiCode traverses back along the path until it finds a matching page.
 *  i.e. If the uri is /foo/bar and only 'foo' is defined then it will execute that route's action.
 * It is highly recommended to define a default route of '' for the home page or root of the site (yoursite.com/).
 * 
 * /50022666/Thomas Dekeyser/2013/37/200/480/HE
 */
//getRoute()->get('/(\w+)/(\w+)/(\w+)/(\w+)/(\w+)/(\w+)', 'handleRankingChange');
getRoute()->get('/playerpoints/(\w+)/([\w+-]+)','playerPoints');
getRoute()->post('/(\w+)/(\w+)/(\w+)/(\w+)/(\w+)/(\w+)', 'handleRankingChange');
getRoute()->get('/', 'usage');
getRoute()->run(); 

/*
 * ******************************************************************************************
 * Define functions and classes which are executed by EpiCode based on the $_['routes'] array
 * ******************************************************************************************
 */
 
function usage() {
	echo "Adding new ranking information into the system <br>";
	echo "Usage:<br>";
	echo "HTTP POST /processRanking/%vblId%/%year%/%weekOfYear%/%ranking%/%points%/%discipline% <br>";
	echo "HTTP POST-PARAM playerName=%name%";
	echo "Example:<br>";
	echo "processRanking/50022666/2013/37/200/480/HE <br>";
	echo "HTTP POST-PARAM playerName=Thomas <br>";
}	
 
function handleRankingChange($vblId,$year,$weekOfYear,$ranking,$points,$discipline) {
  //STEP1: Check if this is a new player
  $user = getDatabase()->one('SELECT * FROM mashup_spelers WHERE vbl_id=:vbl_id', array(':vbl_id' => $vblId));
  if (empty($user['vbl_id'])) {
	  //New player, first need to add this player	
	  //echo "{$_POST['playerName']}";
	  getDatabase()->execute('INSERT INTO mashup_spelers(vbl_id, name) VALUES(:vbl_id, :name)', array(':vbl_id' => $vblId, ':name' => $_POST['playerName']));	 
  }
  
  //STEP2: Add ranking if not yet in DB
  $toDate = "STR_TO_DATE('".$year."/".$weekOfYear."/1','%Y/%u/%w')";//using a function like str_to_date seems not to be working with bind variables...strange
  $rankingR = getDatabase()->one('SELECT * FROM mashup_ranking WHERE vbl_id=:vbl_id and discipline=:discipline and date='.$toDate, array(':vbl_id' => $vblId,':discipline' =>$discipline))	;
  if (empty($rankingR['vbl_id'])) {
	  //New ranking, adding
	  $id = getDatabase()->execute('INSERT INTO mashup_ranking(vbl_id, date,ranking,points,discipline) VALUES(:vbl_id, '.$toDate.',:ranking, :points,:discipline)', array(':vbl_id' => $vblId,':ranking' => $ranking, ':points' => $points, ':discipline' => $discipline));	 
  }  
  echo "OK";
}

function playerPoints($discipline,$vblIdsString){

/* Basic query example
SELECT
  date_format(date,'%Y%m%d') as date,
  MAX(IF(vbl_id = 50022666, ranking, '')) AS 'Thomas Dekeyser',
  MAX(IF(vbl_id = 50086874, ranking, '')) AS 'Bram Demeester',
  MAX(IF(vbl_id = 50069074, ranking, '')) AS 'Tim Rombout'
FROM mashup_ranking  r
WHERE vbl_id in ('50022666','50086874','50069074')
and discipline='HE'
GROUP BY
  date_format(date,'%Y%m%d')
order by date asc;	
*/
	
	//STEP1: Build up array list for prepared statement + where clause base on vblid's
	$vblIds = strtok($vblIdsString, "-");
	//$prepareStmntArgArray = [];
	$counter = 0;
	$whereClause = 'vbl_id in (0';
	while ($vblIds !== false) {
		$prepareStmntArgArray[':vblid'.$counter] = $vblIds;
		$whereClause = $whereClause.',:vblid'.$counter;	
		$counter++;
		//echo "vblid=$vblIds<br />";
		$vblIds = strtok("-");
	}
	
	$whereClause.=')';
	//echo print_r($prepareStmntArgArray)."<br>";
	//echo "whereC:".$whereClause."<br>";
	
	//STEP2: Get players names+build up fields to select
	$selectedPlayers = getDatabase()->all('SELECT vbl_id, name FROM mashup_spelers WHERE '.$whereClause.' order by vbl_id asc',$prepareStmntArgArray);
	//echo $selectedPlayers;



	$counter=0;
	$tsvHeaderElements = array(0 => 'date');
   foreach($selectedPlayers as $key => $player)
   {
	   $selectFields[$counter] = 'MAX(IF(vbl_id = '.$player['vbl_id'].', points, \'\')) AS \''.$player['vbl_id'].'\'';
	   $counter++;
	   $tsvHeaderElements[$counter] = $player['name'];
	   
   }   
  //echo print_r($selectFields).'<br>';
  //echo implode(", ",$selectFields) . '<br>';
	
	//STEP3: Get Rankings
	$disciplines['SINGLE'] = array('HE','DE');
	$disciplines['DOUBLE'] = array('DD','HD');
	$disciplines['MIX'] = array('GDH','GDD');
	$prepareStmntArgArray[':discipline1'] = $disciplines[$discipline][0];
	$prepareStmntArgArray[':discipline2'] = $disciplines[$discipline][1];
	$selectedPoints = getDatabase()->all('SELECT  date_format(date,\'%Y%m%d\') as date,' . implode(", ",$selectFields) . ' FROM mashup_ranking  r where ' . $whereClause . 'and r.discipline in (:discipline1,:discipline2) GROUP BY date_format(date,\'%Y%m%d\') order by date asc',$prepareStmntArgArray);

	header("Content-type: text/html");
	header("Content-Disposition: attachment; filename=data.csv");
	header("Pragma: no-cache");
	header("Expires: 0");

   echo implode("\t",$tsvHeaderElements)."\n";
   $foundSingleRow=false;
   foreach($selectedPoints as $key => $point)
   {
	   $foundSingleRow = true;
	   echo $point['date'];
	   foreach($selectedPlayers as $key => $player)
	   {
		   echo "\t".$point[$player['vbl_id']];
	   }
	   echo "\n";
   }
}

function giveName($vblId,$players) {
   foreach($selectedPlayers as $key => $player)
   {
	   if ($player['vbl_id'] == $vblId) {
		   return $player['name'];
	   }
   }
   return "unknown-name";  	
}
