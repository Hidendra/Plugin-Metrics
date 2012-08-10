#!/usr/local/bin/bash

# executed every 5 minutes

cd /data/www/mcstats.org/cron/

/usr/local/bin/php -q rank-plugins.php
