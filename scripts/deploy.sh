#!/bin/bash

if [ $# -ne 1 ]; then
    echo "Usage: $0 <realm>"
    exit
fi

REALM="$1"

# rsync command
RSYNC="rsync -avzq --delete --progress"

# nginx load balancer
REMOTE_HOST="root@mcstats.org"

if [ "$REALM" == "live" ]; then
	REMOTE_HOST="root@mcstats.org"
    REMOTE_LOCATION="/var/www/servers/"
elif [ "$REALM" == "dev" ]; then
	REMOTE_HOST="root@192.168.1.30"
	REMOTE_LOCATION="/data/www/"
else
    REMOTE_HOST="root@10.10.1.30"
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

if [ "$REALM" == "live" ] || [ "$REALM" == "dev" ]; then
    $RSYNC  --exclude 'config.php' ./ $REMOTE_HOST:"$REMOTE_LOCATION"
else
    $RSYNC -e "ssh root@zero.mcstats.org ssh" --exclude 'config.php' ./ $REMOTE_HOST:"$REMOTE_LOCATION"
fi

echo -e " \033[0;32m=>\033[00m Done"