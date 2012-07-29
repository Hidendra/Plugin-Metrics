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
    REMOTE_LOCATION="/var/www/servers/"
elif [ "$REALM" == "dev" ]; then
	NGINX_BALANCER="root@192.168.1.30"
	REMOTE_LOCATION="/data/www/"
else
    NGINX_BALANCER="root@10.10.1.16"
    REMOTE_LOCATION="/var/www/servers/"
fi

if [ -d "www" ]; then
	cd www/
else
	cd ../www/
fi

echo -e "Realm: \033[0;32m$REALM\033[00m"

# First deploy to the load balancer
echo "Deploying to nginx load balancer"

# First the main website
if [ "$REALM" == "live" ] || [ "$REALM" == "dev" ]; then
    $RSYNC  --exclude 'config.php' ./ $NGINX_BALANCER:"$REMOTE_LOCATION"
else
    $RSYNC -e "ssh root@zero.mcstats.org ssh" --exclude 'config.php' ./ $NGINX_BALANCER:"$REMOTE_LOCATION"
fi

echo -e " \033[0;32m=>\033[00m Done"