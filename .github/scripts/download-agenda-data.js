module.exports = ({github, context, io}) => {
    console.log("Starting downloading from google");

    function dateToYMD(date) {
        var d = date.getDate();
        var m = date.getMonth() + 1; //Month from 0 to 11
        var y = date.getFullYear();
        return '' + y + '-' + (m<=9 ? '0' + m : m) + '-' + (d <= 9 ? '0' + d : d);
    }

    const numberOfDaysToDisplay =7;
    var fromDate = new Date();
    var toDate = new Date();
    toDate.setDate(fromDate.getDate() + numberOfDaysToDisplay);

    console.log(dateToYMD(fromDate) + "->" + dateToYMD(toDate));



    return context;
}