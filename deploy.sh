#!/bin/bash

if [ $# -ne 1 ]; then
    echo "Usage: $0 <realm>"
    exit
fi

REALM="$1"

# rsync command
RSYNC="rsync -avzq --progress"

# nginx load balancer
NGINX_BALANCER="root@mcstats.org"

if [ "$REALM" == "live" ]; then
    REMOTE_LOCATION="/var/www/servers/mcstats.org/"
else
    REMOTE_LOCATION="/var/www/servers/test.mcstats.org/"
fi

cd src
chmod -R 755 .

echo -e "Realm: \e[0;32m$REALM\e[00m"

# First deploy to the load balancer
echo "Deploying to nginx load balancer"

# First the main website
$RSYNC  --exclude 'static/' --exclude 'config.php' ./ $NGINX_BALANCER:"$REMOTE_LOCATION"

echo -e " \e[0;32m=>\e[00m Main content"

# Static content
cd static
$RSYNC ./ $NGINX_BALANCER:/var/www/servers/static.mcstats.org
cd ..

echo -e " \e[0;32m=>\e[00m Static content"
