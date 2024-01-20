<?php
include_once '../lib/epiphany-20130912/Epi.php';
include_once '../../wp-config.php';
Epi::setPath('base', '../lib/epiphany-20130912');
Epi::init('route','database');
EpiDatabase::employ('mysql',constant('DB_NAME'),constant('DB_HOST'),constant('DB_USER'),constant('DB_PASSWORD')); // type = mysql, database = mysql, host = localhost, user = root, password = [empty]
Epi::setSetting('exceptions', false);

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
 */
getRoute()->post('/contactus', 'contactUsPost');
getRoute()->post('/intro', 'introPost');
getRoute()->get('/intro', 'getIntroPost');
getRoute()->get('/', 'usage');
getRoute()->run(); 

/*
 * ******************************************************************************************
 * Define functions and classes which are executed by EpiCode based on the $_['routes'] array
 * ******************************************************************************************
 */
 
function usage() {
	echo "Up and running";
}	


/*
CREATE TABLE `form_contactus` (
  `id` int(11) NOT NULL,
  `creation_date_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `name` text,
  `email` text,
  `message` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `form_contactus`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `form_contactus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
*/
function contactUsPost() {
	$data = json_decode(file_get_contents('php://input'), true);

	$captcha_result = isValidReCaptha($data['g-recaptcha-response']);
	$process_result = array('success' => false);

	if ($captcha_result['success']) {
		$field_data = array(':name' => $data["name"], ':email' => $data["email"], ':message' => $data["message"]);
		getDatabase()->execute('INSERT INTO form_contactus (name, email, message) VALUES (:name, :email, :message)', $field_data); 
		sendContactNotificationMail($field_data);
		$process_result['success'] = true;
	}

	header("Content-type: application/json");
    header("Content-Disposition: attachment; filename=json.data");
    header("Pragma: no-cache");
    header("Expires: 0");
    header("Access-Control-Allow-Origin: *");
	print(json_encode($process_result));
}

/*
CREATE TABLE `form_intro` (
  `id` int(11) NOT NULL,
  `creation_date_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `name` text,
  `email` text,
  `intro_date` datetime
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `form_intro`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `form_intro`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
*/
function introPost() {
	$data = json_decode(file_get_contents('php://input'), true);

	$captcha_result = isValidReCaptha($data['g-recaptcha-response']);
	$process_result = array('success' => false);

	if ($captcha_result['success']) {
		$field_data = array(':name' => $data["name"], ':email' => $data["email"], ':intro_datetime' => $data["intro_datetime"]);
		getDatabase()->execute('INSERT INTO form_intro (name, email, intro_date) VALUES (:name, :email, :intro_datetime)', $field_data); 
		sendIntroNotificationMail($field_data);
		$process_result['success'] = true;
	}

	header("Content-type: application/json");
    header("Content-Disposition: attachment; filename=json.data");
    header("Pragma: no-cache");
    header("Expires: 0");
    header("Access-Control-Allow-Origin: *");
	print(json_encode($process_result));
}

function isValidReCaptha($g_recaptcha_response) {
	$url = "https://www.google.com/recaptcha/api/siteverify";

	//The data you want to send via POST
	$fields = [
		'secret' => constant('RECAPTCHA_SITE_SECRET'),
		'response' => $g_recaptcha_response
	];
	
	//url-ify the data for the POST
	$fields_string = http_build_query($fields);
	
	//open connection
	$ch = curl_init();
	
	//set the url, number of POST vars, POST data
	curl_setopt($ch,CURLOPT_URL, $url);
	curl_setopt($ch,CURLOPT_POST, true);
	curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
	
	//So that curl_exec returns the contents of the cURL; rather than echoing it
	curl_setopt($ch,CURLOPT_RETURNTRANSFER, true); 
	
	//execute post
	$result_encoded = curl_exec($ch);

	$result = json_decode($result_encoded, true);

	return $result;

}

function sendContactNotificationMail($field_data) {
	$to = 'info@gentsebc.be';

	// Subject
	$subject = 'Contact Gentse BC';

	// Additional headers
	$headers[] = 'From: Gentse Badmintonclub <info@gentsebc.be>';
	$headers[] = 'Cc: '.$field_data[':email'];

	// Message
	$message = "Beste ".$field_data[':name'].",\r\n\r\n";
	$message .= "Je vraag is goed ontvangen. We proberen zo snel mogelijk aan antwoord te geven.\r\n\r\n";
	$message .= "Met vriendelijke groeten,";
	$message .= "Gentse BC \r\n\r\n\r\n";
	$message .= "==========================\r\n";
	$message .= "Vraag:";
	$message .= $field_data[':message'];

	mail($to, $subject, $message, implode("\r\n", $headers));
}

function sendIntroNotificationMail($field_data) {
	$to = 'bestuur@gentsebc.be';

	// Subject
	$subject = 'Inschrijving introductie moment GENTSE BC';

	// Additional headers
	$headers[] = 'From: Gentse Badmintonclub <noreply@gentsebc.be>';
	$headers[] = 'Cc: '.$field_data[':email'];

	// Message
	$message = "Beste ".$field_data[':name'].",\r\n\r\n";
	$message .= "We hebben je inschrijving goed ontvangen. Deze wordt nu doorgestuurd naar de avondverantwoordelijke van je gevraagde introductiemoment.\r\n";
	$message .= "Op ".substr($field_data[':intro_datetime'], 0, 16)." word je verwacht in de sporthal, op het terrein. Wij gaan er dus vanuit dat je aanwezig zal zijn. Mocht je toch verhinderd zijn, gelieve iets te laten weten op avondverantwoordelijken@gentsebc.be.\r\n\r\n";
	$message .= "Breng indoor sportschoenen mee met witte zool, sportieve kledij en een racketje. Shuttles kunnen ter plaatse aangekocht worden indien nodig.\r\n\r\n";
	
	$message .= "Met vriendelijke groeten,";
	$message .= "Gentse BC";

	mail($to, $subject, $message, implode("\r\n", $headers));	
}

function getIntroPost() {
	$data = getDatabase()->all('select DATE_FORMAT(intro_date,"%a %d %b %Y") as intro_date, name from form_intro where intro_date > now() - INTERVAL 90 DAY ORDER BY intro_date ASC');

    header("Content-type: text/html");
    //header("Content-Disposition: attachment; filename=intro_data.html");
    header("Pragma: no-cache");
    header("Expires: 0");
    header("Access-Control-Allow-Origin: *");
	echo("<html><head><style>table, th, td {border: 1px solid black;}</style></head><body><table><tr><th>Datum</th><th>Naam</th></tr>");
    foreach($data as $key => $sub) {
		echo("<tr>");
		echo("<td>".$sub['intro_date']."</td>");
		echo("<td>".$sub['name']."</td>");
		echo("</tr>");
	}
	echo("</table></html>");
}
