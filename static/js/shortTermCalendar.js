function giveHourMin(dateTime1,dateTime2) {
    return dateTime1.substring(11,16)+"-"+dateTime2.substring(11,16);
}

function calItemsHtmlHorizonal($, calItems, isIntromoment) {
    if (calItems.length === 0) {
        return "<td>-</td>";
    } else {
        divs = "";
        $.each(calItems, function(index){
            myContent=giveHourMin(calItems[index].startDateTime.date,calItems[index].endDateTime.date);
            myLocationCode=calItems[index].locationCode;
            myLocation=calItems[index].location;

            if (myLocationCode=='other') {
                myContent = myContent+"<br>"+"Uitzonderlijke locatie!"+"<br>"+myLocation;
            }
            divs = divs +"<div class='"+myLocationCode+"'>"+myContent+"</div>"

        })
        result = "<td>" + divs + "</td>";
        return result;
    }
}

function calItemsHtmlVertical($, calItems) {
    if (calItems.length === 0) {
        return "<td>-</td>";
    } else {
        divs = "";
        $.each(calItems, function(index){
            myContent=giveHourMin(calItems[index].startDateTime.date,calItems[index].endDateTime.date);
            myLocationCode=calItems[index].locationCode;
            myLocation=calItems[index].location;

            divs = divs +"<div class='"+myLocationCode+"'>"+myContent+"</div>"

        })
        result = "<td>" + divs + "</td>";
        return result;
    }
}

function addShortTermCalendar($, baseURL){
    console.log("Adding short term calendar from:"+baseURL);
    $.get(baseURL+"mashup/processCalendar/shortTermCalendar", function( data ) {
        $.each(data,function(index) {
            //  console.log(data[index]);

            //Horizontal (big screens)
            $("#playingHoursHorizontal").append("<th>"+data[index].day+"</th>");
            $("#playingHoursHorizontalJeugd").append(calItemsHtmlHorizonal($, data[index].youthCalItems, false));
            $("#playingHoursHorizontalVolwassenen").append(calItemsHtmlHorizonal($, data[index].adultCalItems, false));
            $("#playingHoursHorizontalGSport").append(calItemsHtmlHorizonal($, data[index].gSportCalItems, true));

            //Vertical (small screens)
            $("#playingHoursVertical").append("<tr><td>"+data[index].day+"</td>"+calItemsHtmlVertical($, data[index].youthCalItems) + calItemsHtmlVertical($, data[index].adultCalItems) + calItemsHtmlVertical($, data[index].gSportCalItems) + "</tr>");

        });
    });
}
