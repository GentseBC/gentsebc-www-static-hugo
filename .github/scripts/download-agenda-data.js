module.exports = async ({github, core, context, io, fetch, dayjs}) => {
    console.log("Starting downloading from google");
    dayjs.locale('nl-be');

    function dateToYMD(date) {
        var d = date.getDate();
        var m = date.getMonth() + 1; //Month from 0 to 11
        var y = date.getFullYear();
        return '' + y + '-' + (m<=9 ? '0' + m : m) + '-' + (d <= 9 ? '0' + d : d);
    }

    async function fetchAll(fromDate,toDate) {
        const [volwassenenResponse, jeugdResponse, gsportResponse] = await Promise.all([
                                                                           fetch('https://www.googleapis.com/calendar/v3/calendars/gentsebc%40gmail.com/events?orderBy=startTime&q=speelmoment&singleEvents=true&timeMax=' + dateToYMD(toDate) +'T00%3A00%3A00-00%3A00&timeMin='+ dateToYMD(fromDate) + 'T00%3A00%3A00-00%3A00&key=AIzaSyBRQRMJ_sZC4vIiPbtvyscTaXWknlp7Pak'),
                                                                           fetch('https://www.googleapis.com/calendar/v3/calendars/gentsebc%40gmail.com/events?orderBy=startTime&q=speelmoment&singleEvents=true&timeMax=' + dateToYMD(toDate) +'T00%3A00%3A00-00%3A00&timeMin='+ dateToYMD(fromDate) + 'T00%3A00%3A00-00%3A00&key=AIzaSyBRQRMJ_sZC4vIiPbtvyscTaXWknlp7Pak'),
                                                                           fetch('https://www.googleapis.com/calendar/v3/calendars/gentsebc%40gmail.com/events?orderBy=startTime&q=speelmoment&singleEvents=true&timeMax=' + dateToYMD(toDate) +'T00%3A00%3A00-00%3A00&timeMin='+ dateToYMD(fromDate) + 'T00%3A00%3A00-00%3A00&key=AIzaSyBRQRMJ_sZC4vIiPbtvyscTaXWknlp7Pak')
                                                                       ]);

        const volwassenen = await volwassenenResponse.json();
        const jeugd = await jeugdResponse.json();
        const gsport = await gsportResponse.json();

        return [volwassenen, jeugd, gsport];
    }

    async function fetchAsync (url) {
        let response = await fetch(url);
        let data = await response.json();
        return data;
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

    function mapCalendarData(calendarData, evenType, result, dateToIndex) {
        calendarData.items
            .filter(item => item.start !== undefined && item.start.dateTime !== undefined && item.end !== undefined && item.end.dateTime !== undefined  && item.summary !== undefined)
            .forEach(item => {
                const dateIndex = dateToIndex.get(item.start.dateTime.substring(0,10));
                if (dateToIndex !== undefined && evenType !== undefined)  {
                    result[dateIndex][evenType].push({
                                                         "startDateTime": dayjs(item.start.dateTime).tz("Europe/Brussels").format("YYYY-MM-DD HH:mm:ss"),
                                                         "endDateTime": dayjs(item.end.dateTime).tz("Europe/Brussels").format("YYYY-MM-DD HH:mm:ss"),
                                                         "location": item.location,
                                                         "locationCode": resolveLocationCode(item.location)
                                                     });
                }
            });
    }
    
    function downloadShortTermCalendar(numberOfDaysToDisplay, outputName ) {
        result = [];
        const dateToIndex= new Map();
        for(let i = 0; i < numberOfDaysToDisplay; i++) {
            const aDay = {"adultCalItems":[],"youthCalItems":[],"gSportCalItems":[]};
            const myDay = dayjs().add(i, 'day');
            aDay.day =  myDay.format('dd D MMM')
            dateToIndex.set(myDay.format('YYYY-MM-DD'), i);
            result.push(aDay);
        }
    
        //var fromDate = new Date();
        var fromDate = new Date("2023-06-12T00:00:01.000Z");//TESTING!
        var toDate = new Date();
        toDate.setDate(fromDate.getDate() + numberOfDaysToDisplay);   

        fetchAll(fromDate, toDate).then(([volwassenen, jeugd, gsport]) => {
            mapCalendarData(volwassenen, "adultCalItems", result, dateToIndex);
            mapCalendarData(jeugd, "youthCalItems", result, dateToIndex);
            mapCalendarData(gsport, "gSportCalItems", result, dateToIndex);

            console.log(result);
            core.setOutput(outputName, JSON.stringify(result));
        }).catch(error => {
            console.log("Failed to fetch" + error);
        })
    }

    downloadShortTermCalendar(7, 'calendar-json');
   
}