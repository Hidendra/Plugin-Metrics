#!/usr/local/bin/bash

# executed every 30 minutes

cd /data/www/mcstats.org/cron/

GENERATOR_FILE="../generator.txt"

# prepare
echo 1 > "$GENERATOR_FILE"
/usr/local/bin/php -q prepare-graph-generators.php
echo 8 > "$GENERATOR_FILE"

/usr/local/bin/php -q generators/players.php
echo 20 > "$GENERATOR_FILE"
/usr/local/bin/php -q generators/servers.php
echo 32 > "$GENERATOR_FILE"
/usr/local/bin/php -q generators/custom.php
echo 44 > "$GENERATOR_FILE"
/usr/local/bin/php -q generators/countries.php
echo 56 > "$GENERATOR_FILE"
/usr/local/bin/php -q generators/versions.php
echo 68 > "$GENERATOR_FILE"
/usr/local/bin/php -q generators/server-software.php
echo 80 > "$GENERATOR_FILE"
/usr/local/bin/php -q generators/minecraft-version.php
echo 92 > "$GENERATOR_FILE"

# finish !
/usr/local/bin/php -q finish-graph-generation.php
rm "$GENERATOR_FILE"