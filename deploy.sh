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
    NGINX_BALANCER="root@10.10.1.16"
    REMOTE_LOCATION="/var/www/servers/test.mcstats.org/"
fi

cd src
chmod -R 755 .

echo -e "Realm: \e[0;32m$REALM\e[00m"

# First deploy to the load balancer
echo "Deploying to nginx load balancer"

# First the main website
if [ "$REALM" == "live" ]; then
    $RSYNC  --exclude 'static/' --exclude 'config.php' ./ $NGINX_BALANCER:"$REMOTE_LOCATION"
else
    $RSYNC -e "ssh root@zero.mcstats.org ssh" --exclude 'static/' --exclude 'config.php' ./ $NGINX_BALANCER:"$REMOTE_LOCATION"
fi

echo -e " \e[0;32m=>\e[00m Main content"

# Static content
cd static
if [ "$REALM" == "live" ]; then
    $RSYNC ./ $NGINX_BALANCER:/var/www/servers/static.mcstats.org
else
    $RSYNC -e "ssh root@zero.mcstats.org ssh" ./ $NGINX_BALANCER:/var/www/servers/static.mcstats.org
fi
cd ..

echo -e " \e[0;32m=>\e[00m Static content"
