# Magento 2 Tracking Console script and frontend controller #

Submit shipment tracking information via controller and shell script using order increment ID

# Install instructions #

`composer require dominicwatts/tracking`

`php bin/magento setup:upgrade`

`php bin/magento setup:di:compile`

# Usage instructions #

Either add tracking to shipment on order via URL or console script

##Url##

`/xigen_tracking/submit/index?oid=<ORDER_ID>&carrier=<CARRIER_CODE>&title=<CARRIER_TITLE>&number=<TRACKING_NUMBER_OR_URL>`

`/xigen_tracking/submit/index?oid=000000045&carrier=customer&title=Royal%20Mail&number=http://test.com/12345`

##Console script##

`xigen:tracking:addtracking [-o|--orderid ORDERID] [-c|--carrier [CARRIER]] [-t|--title [TITLE]] [-u|--number [NUMBER]]`
     
`php bin/magento xigen:tracking:addtracking -o 000000045 -c custom -t "Royal Mail" -u "http://test.com/12345"`




