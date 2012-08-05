#!/usr/local/bin/bash

# executed every 30 minutes

cd /data/www/mcstats.org/cron/

# prepare
/usr/local/bin/php -q prepare-graph-generators.php

/usr/local/bin/php -q generators/players.php
/usr/local/bin/php -q generators/servers.php
/usr/local/bin/php -q generators/custom.php
/usr/local/bin/php -q generators/countries.php
/usr/local/bin/php -q generators/versions.php
/usr/local/bin/php -q generators/server-software.php
/usr/local/bin/php -q generators/minecraft-version.php

# finish !
/usr/local/bin/php -q finish-graph-generation.php