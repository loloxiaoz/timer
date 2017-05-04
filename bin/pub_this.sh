TAG=`cat ./version.txt`
echo $TAG ;
/data/x/tools/mara-pub/rocket_pub.sh  --plan mara_cron --prj mara_cron --tag $TAG --host $*
