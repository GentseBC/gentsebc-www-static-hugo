module.exports = async ({github, context, io, fetch, dayjs}) => {
    console.log("Starting downloading from google");

    function dateToYMD(date) {
        var d = date.getDate();
        var m = date.getMonth() + 1; //Month from 0 to 11
        var y = date.getFullYear();
        return '' + y + '-' + (m<=9 ? '0' + m : m) + '-' + (d <= 9 ? '0' + d : d);
    }

    async function fetchAsync (url) {
        let response = await fetch(url);
        let data = await response.json();
        return data;
      }
      

    const numberOfDaysToDisplay =7; 

    function resolveEventType(summary) {
        if (summary === undefined) {
            return undefined
        }
        lowerSummary = summary.toLowerCase();

        if (lowerSummary.indexOf("jeugd") !== -1) {
            return "youthCalItems";
        } else if (lowerSummary.indexOf("volwassenen") !== -1) {
            return "adultCalItems";
        } else if (lowerSummary.indexOf("g-sport") !== -1) {
            return "gSportCalItems";
        } 
        return undefined;
    }

    function resolveLocationCode(location) {
        if (location === undefined) {
            return "other";
        }
        lowerLocation = location.toLowerCase();

        if (lowerLocation.indexOf("merckx") !== -1) {
            return "wielerpiste";
        } else if (lowerLocation.indexOf("bourgoyen") !== -1) {
            return "bourgoyen";
        } else if (lowerLocation.indexOf("topsporthal") !== -1) {
            return "topsporthal";
        } else {
            return "other";
        }
    }
    
    result = [];
    dayjs.locale('nl-be');
    const dateToIndex= new Map();
    for(let i = 0; i < numberOfDaysToDisplay; i++) {
        const aDay = {"adultCalItems":[],"youthCalItems":[],"gSportCalItems":[]};
        const myDay = dayjs().add(i, 'day');
        aDay.day =  myDay.format('dd D MMM')
        dateToIndex.set(myDay.format('YYYY-MM-DD'), i);
        result.push(aDay);
    }

    var fromDate = new Date();
    var toDate = new Date();
    toDate.setDate(fromDate.getDate() + numberOfDaysToDisplay);   
    URL = 'https://www.googleapis.com/calendar/v3/calendars/gentsebc%40gmail.com/events?orderBy=startTime&q=speelmoment&singleEvents=true&timeMax=' + dateToYMD(toDate) +'T00%3A00%3A00-00%3A00&timeMin='+ dateToYMD(fromDate) + 'T00%3A00%3A00-00%3A00&key=AIzaSyBRQRMJ_sZC4vIiPbtvyscTaXWknlp7Pak';
    console.log(URL);
    
    fetchAsync(URL).then(calendarData => {
        calendarData.items
        .filter(item => item.start !== undefined && item.start.dateTime !== undefined && item.end !== undefined && item.end.dateTime !== undefined  && item.summary !== undefined)
        .forEach(item => {
            const dateIndex = dateToIndex.get(item.start.dateTime.substring(0,10));
            const evenType = resolveEventType(item.summary);
            if (dateToIndex !== undefined && evenType !== undefined)  {
                result[dateIndex][evenType].push({
                    "startDateTime": dayjs(item.start.dateTime).format("YYYY-MM-DD HH:mm:ss"),
                    "endDateTime": dayjs(item.end.dateTime).format("YYYY-MM-DD HH:mm:ss"),
                    "location": item.location,
                    "locationCode": resolveLocationCode(item.location)
                });
            }
        });

        return $result;
    });
}