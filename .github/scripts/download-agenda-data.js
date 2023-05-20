module.exports = async ({github, context, io, fetch}) => {
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
    var fromDate = new Date();
    var toDate = new Date();
    toDate.setDate(fromDate.getDate() + numberOfDaysToDisplay);    

    URL = 'https://www.googleapis.com/calendar/v3/calendars/gentsebc%40gmail.com/events?orderBy=startTime&q=speelmoment&singleEvents=true&timeMax=' + dateToYMD(toDate) +'T00%3A00%3A00-00%3A00&timeMin='+ dateToYMD(fromDate) + 'T00%3A00%3A00-00%3A00&key=AIzaSyBRQRMJ_sZC4vIiPbtvyscTaXWknlp7Pak';
    console.log(URL);
    let calendarData = await fetchAsync(URL);
    console.log(calendarData);


    return context;
}