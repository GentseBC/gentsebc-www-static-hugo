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
getRoute()->get('/updateCalendars','updateCalendars');
getRoute()->get('/updateShortTermCalendar','updateShortTermCalendar');
getRoute()->get('/updateLongerTermIntroMomentCalendar','updateLongerTermIntroMomentCalendar');
getRoute()->get('/shortTermCalendar','getShortTermCalendar');
getRoute()->get('/longerTermIntroCalendar','getLongerTermIntroCalendar');
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

class CalItem {
    public $startDateTime;
    public $startDateDutch;
    public $endDateTime;
    public $isYouth =false;
    public $isAdult=false;
    public $location="";
    public $locationCode="";
    public $isNoPlayTime=false;
    public $isGSport=false;
}

class DayItem {
    public $day;
    public $adultCalItems=array();
    public $youthCalItems=array();
    public $gSportCalItems=array();
}

function updateCalendars() {
    updateShortTermCalendar();
    updateLongerTermIntroMomentCalendar();
}

function updateShortTermCalendar() {
    $numberOfDaysToDisplay=7;
    $fromDate = date('Y-m-d');
    $toDate = date('Y-m-d',strtotime('+'.$numberOfDaysToDisplay.' days'));

    $calItems=array();
    $GMAIL_CAL_URL_SPEELMOMENTEN='https://www.googleapis.com/calendar/v3/calendars/gentsebc%40gmail.com/events?orderBy=startTime&q=speelmoment&singleEvents=true&timeMax='.$toDate.'T00%3A00%3A00-00%3A00&timeMin='.$fromDate.'T00%3A00%3A00-00%3A00&key=AIzaSyBRQRMJ_sZC4vIiPbtvyscTaXWknlp7Pak';

    processGCalenderItems($GMAIL_CAL_URL_SPEELMOMENTEN,$calItems);

    //print("<pre>");
    //print_r($calItems);
    //print("</pre>");

    saveCallItems($calItems,$numberOfDaysToDisplay,'1');

    echo "OK";
    //echo json_encode($mergedCalItems);
    //print("<pre>");
    //print_r($mergedCalItems);
    //print("</pre>");
}

function updateLongerTermIntroMomentCalendar(){
    $numberOfDaysToDisplay=90;
    $fromDate = date('Y-m-d');
    $toDate = date('Y-m-d',strtotime('+'.$numberOfDaysToDisplay.' days'));

    $calItems=array();
    $GMAIL_CAL_URL_INTROMOMENTEN='https://www.googleapis.com/calendar/v3/calendars/gentsebc%40gmail.com/events?orderBy=startTime&q=intro-moment&singleEvents=true&timeMax='.$toDate.'T00%3A00%3A00-00%3A00&timeMin='.$fromDate.'T00%3A00%3A00-00%3A00&key=AIzaSyBRQRMJ_sZC4vIiPbtvyscTaXWknlp7Pak';

    processGCalenderItems($GMAIL_CAL_URL_INTROMOMENTEN,$calItems);

    //print("<pre>");
    //print_r($calItems);
    //print("</pre>");

    getDatabase()->execute('UPDATE mashup_shorttermcalendar set json=:json where id=2', array(':json' => json_encode($calItems)));


    echo "OK";
    //echo json_encode($mergedCalItems);
    //print("<pre>");
    //print_r($mergedCalItems);
    //print("</pre>");

}

function saveCallItems($calItems,$numberOfDaysToDisplay,$IdInDB) {
    $day_count=0;
    $mergedCalItems=array();
    while ($day_count<$numberOfDaysToDisplay) {
        $date = strtotime('+'.$day_count.' days');

        $dayItem = new DayItem();
        $dayItem->day=giveDutchDateFormat($date);
        foreach($calItems as $calItem) {
            //echo($calItem->startDateTime);
            if(date('Y-m-d',$date) == $calItem->startDateTime->format('Y-m-d')) {
                if ($calItem->isYouth) {
                    array_push($dayItem->youthCalItems, $calItem);
                } else if ($calItem->isAdult) {
                    array_push($dayItem->adultCalItems, $calItem);
                } else if ($calItem->isGSport) {
                    array_push($dayItem->gSportCalItems, $calItem);
                }
                //TODO GEEN SPEELMOMENT, necessary??
            }
        }
        array_push($mergedCalItems,$dayItem);
        $day_count++;
    }

    getDatabase()->execute('UPDATE mashup_shorttermcalendar set json=:json where id=1', array(':json' => json_encode($mergedCalItems)));
}

function processGCalenderItems($cURL,&$calItems) {
    $USER_AGENT='Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/41.0.2272.76 Chrome/41.0.2272.76 Safari/537.36';

    // create curl resource
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $cURL);
    curl_setopt($ch, CURLOPT_USERAGENT, $USER_AGENT);

    //return the transfer as a string
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $GMAIL_CAL_RAW_RESPONSE = curl_exec($ch);

    // close curl resource to free up system resources
    curl_close($ch);

    //Start parsing the response
    $GMAIL_CAL_DECODED = json_decode($GMAIL_CAL_RAW_RESPONSE);

    foreach($GMAIL_CAL_DECODED->items as $item) {
        //Example 2015-09-23T18:00:00+02:00
        $itemStartDateTime = DateTime::createFromFormat('Y-m-d\TH:i:sP', $item->start->dateTime);
        $itemEndDateTime = DateTime::createFromFormat('Y-m-d\TH:i:sP', $item->end->dateTime);

        $calItem = new CalItem();
        $calItem->startDateTime = DateTime::createFromFormat('Y-m-d\TH:i:sP', $item->start->dateTime);
        $calItem->startDateDutch = giveDutchDateFormat(strtotime($item->start->dateTime));
        $calItem->endDateTime = DateTime::createFromFormat('Y-m-d\TH:i:sP', $item->end->dateTime);
        $calItem->isYouth = ((stripos($item->summary,'jeugd')===false) ? false: true);
        $calItem->isAdult = ((stripos($item->summary,'volwassenen')===false) ? false: true);
        $calItem->isGSport = ((stripos($item->summary,'G-sport')===false) ? false: true);

        $calItem->location = $item->location;
        if (stripos($item->location, 'merckx') !== FALSE) {
            $calItem->locationCode = 'wielerpiste';
        } elseif (stripos($item->location, 'bourgoyen') !== FALSE)  {
            $calItem->locationCode = 'bourgoyen';
        } elseif (stripos($item->location, 'topsporthal') !== FALSE)  {
            $calItem->locationCode = 'topsporthal';
        } else {
            $calItem->locationCode = 'other';
        }

        $calItem->isNoPlayTime =((stripos($item->summary,'Geen speelmoment')===false) ? false: true);

        array_push($calItems,$calItem);

    }


}

function giveDutchDateFormat($date)
{
    //Don't want to set the locale because this can not be done for this single script and i wan tto avoid impact in wordpress
    $day = date('N', $date);
    switch ($day) {
        case 1:
            $day = 'Ma';break;
        case 2:
            $day = 'Di';break;
        case 3:
            $day = 'Woe';break;
        case 4:
            $day = 'Do';break;
        case 5:
            $day = 'Vr';break;
        case 6:
            $day = 'Za';break;
        case 7:
            $day = 'Zo';break;
    }

    $month = date('n',$date);
    switch ($month) {
        case 1:
            $month = 'Jan';break;
        case 2:
            $month = 'Feb';break;
        case 3:
            $month = 'Ma';break;
        case 4:
            $month = 'Apr';break;
        case 5:
            $month = 'Mei';break;
        case 6:
            $month = 'Jun';break;
        case 7:
            $month = 'Jul';break;
        case 8:
            $month = 'Aug';break;
        case 9:
            $month = 'Sep';break;
        case 10:
            $month = 'Okt';break;
        case 11:
            $month = 'Nov';break;
        case 12:
            $month = 'Dec';break;
    }

    return $day.' '.date('j', $date).' '.$month;
}

function getShortTermCalendar() {

    $shortTerm = getDatabase()->one('select json from mashup_shorttermcalendar where id=1');

    header("Content-type: application/json");
    header("Content-Disposition: attachment; filename=json.data");
    header("Pragma: no-cache");
    header("Expires: 0");
    header("Access-Control-Allow-Origin: *");
    echo $shortTerm['json'];
}

function getLongerTermIntroCalendar() {

    $shortTerm = getDatabase()->one('select json from mashup_shorttermcalendar where id=2');

    header("Content-type: application/json");
    header("Content-Disposition: attachment; filename=json.data");
    header("Pragma: no-cache");
    header("Expires: 0");
    header("Access-Control-Allow-Origin: *");
    echo $shortTerm['json'];
}




