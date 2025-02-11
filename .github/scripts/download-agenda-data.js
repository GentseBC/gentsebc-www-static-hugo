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
        console.log('https://www.googleapis.com/calendar/v3/calendars/2l08prgcjs85td5d62pbi2snqm0koj6q%40import.calendar.google.com/events?orderBy=startTime&q=speelmoment&singleEvents=true&timeMax=' + dateToYMD(toDate) +'T00%3A00%3A00-00%3A00&timeMin='+ dateToYMD(fromDate) + 'T00%3A00%3A00-00%3A00&key=AIzaSyBRQRMJ_sZC4vIiPbtvyscTaXWknlp7Pak');
        console.log('https://www.googleapis.com/calendar/v3/calendars/28slvkett57tcftbhfg6afl5dioluboh%40import.calendar.google.com/events?orderBy=startTime&q=speelmoment&singleEvents=true&timeMax=' + dateToYMD(toDate) +'T00%3A00%3A00-00%3A00&timeMin='+ dateToYMD(fromDate) + 'T00%3A00%3A00-00%3A00&key=AIzaSyBRQRMJ_sZC4vIiPbtvyscTaXWknlp7Pak');
        console.log('https://www.googleapis.com/calendar/v3/calendars/a8d5rv4mn09nr97soatg64kfe01bq3ld%40import.calendar.google.com/events?orderBy=startTime&q=speelmoment&singleEvents=true&timeMax=' + dateToYMD(toDate) +'T00%3A00%3A00-00%3A00&timeMin='+ dateToYMD(fromDate) + 'T00%3A00%3A00-00%3A00&key=AIzaSyBRQRMJ_sZC4vIiPbtvyscTaXWknlp7Pak');
        const [adultResponse, youthResponse, gsportResponse] = await Promise.all([
                                                                           fetch('https://www.googleapis.com/calendar/v3/calendars/2l08prgcjs85td5d62pbi2snqm0koj6q%40import.calendar.google.com/events?orderBy=startTime&q=speelmoment&singleEvents=true&timeMax=' + dateToYMD(toDate) +'T00%3A00%3A00-00%3A00&timeMin='+ dateToYMD(fromDate) + 'T00%3A00%3A00-00%3A00&key=AIzaSyBRQRMJ_sZC4vIiPbtvyscTaXWknlp7Pak'),
                                                                           fetch('https://www.googleapis.com/calendar/v3/calendars/28slvkett57tcftbhfg6afl5dioluboh%40import.calendar.google.com/events?orderBy=startTime&q=speelmoment&singleEvents=true&timeMax=' + dateToYMD(toDate) +'T00%3A00%3A00-00%3A00&timeMin='+ dateToYMD(fromDate) + 'T00%3A00%3A00-00%3A00&key=AIzaSyBRQRMJ_sZC4vIiPbtvyscTaXWknlp7Pak'),
                                                                           fetch('https://www.googleapis.com/calendar/v3/calendars/a8d5rv4mn09nr97soatg64kfe01bq3ld%40import.calendar.google.com/events?orderBy=startTime&q=speelmoment&singleEvents=true&timeMax=' + dateToYMD(toDate) +'T00%3A00%3A00-00%3A00&timeMin='+ dateToYMD(fromDate) + 'T00%3A00%3A00-00%3A00&key=AIzaSyBRQRMJ_sZC4vIiPbtvyscTaXWknlp7Pak')
                                                                       ]);

        const adult = await adultResponse.json();
        const youth = await youthResponse.json();
        const gsport = await gsportResponse.json();

        console.log("Fetched calendar data");
        return [adult, youth, gsport];
    }

    function resolveLocationCode(location) {
        if (location === undefined) {
            return "other";
        }
        lowerLocation = location.toLowerCase();

        if (lowerLocation.indexOf("merckx") !== -1 || lowerLocation.indexOf("wielerpiste") !== -1) {
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
                if (dateToIndex !== undefined)  {
                    result[dateIndex][evenType].push({
                                                         "startDateTime": dayjs(item.start.dateTime).tz("Europe/Brussels").format("YYYY-MM-DD HH:mm:ss"),
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
            //const myDay = dayjs("2023-06-12T00:00:01.000Z").add(i, 'day'); // TESTING
            const myDay = dayjs().add(i, 'day'); // TESTING
            aDay.day =  myDay.format('dd D MMM')
            dateToIndex.set(myDay.format('YYYY-MM-DD'), i);
            result.push(aDay);
        }
    
        var fromDate = new Date();
        //var fromDate = new Date("2023-06-12T00:00:01.000Z");//TESTING!
        var toDate = new Date();
        toDate.setDate(fromDate.getDate() + numberOfDaysToDisplay);   

        fetchAll(fromDate, toDate).then(([volwassenen, jeugd, gsport]) => {
            console.log("Processing calendar items.")
            mapCalendarData(volwassenen, "adultCalItems", result, dateToIndex);
            console.log("youth");
            mapCalendarData(jeugd, "youthCalItems", result, dateToIndex);
            console.log("gsport");
            mapCalendarData(gsport, "gSportCalItems", result, dateToIndex);
            console.log("done")

            //console.log(result);
            core.setOutput(outputName, JSON.stringify(result));
        }).catch(error => {
            console.log("Failed to fetch:" + error);
        })
    }

    downloadShortTermCalendar(7, 'calendar-json');
   
}