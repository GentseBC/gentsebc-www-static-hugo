#!/bin/sh

echo "hello world"
FROM_DATE=$(date +%Y-%m-%d)
TO_DATE=$(date +%Y-%m-%d -d "$DATE + 7 day")
echo $FROM_DATE
echo $TO_DATE

URL='https://www.googleapis.com/calendar/v3/calendars/gentsebc%40gmail.com/events?orderBy=startTime&q=speelmoment&singleEvents=true&timeMax='${TO_DATE}'T00%3A00%3A00-00%3A00&timeMin='${FROM_DATE}'T00%3A00%3A00-00%3A00&key=AIzaSyBRQRMJ_sZC4vIiPbtvyscTaXWknlp7Pak';
echo $URL

wget -O cdata.json ${URL}
cat cdata.json | jq '[.items[] | {"day": .start.dateTiem ,"start": .start.dateTime, "end": .end.dateTime, "locatie": .location, "summary": .summary }]' > data/calendar/short-term-data.json


#TODO: for now using existing epiphany PHP implementation
# https://lzone.de/cheat-sheet/jq