#!/bin/bash

while true
do
    php /home/softice/public_html/apps.videoapps.club/all_bank_balance/artisan queue:work --sleep=3 --tries=3 >> /home/softice/public_html/apps.videoapps.club/all_bank_balance/storage/logs/queue.log 2>&1
    sleep 5
done
