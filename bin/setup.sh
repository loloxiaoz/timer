ENV=$1
RG=/data/x/tools/rigger-ng/rg

if test -e $ENV
then
    ENV='dev'
fi

$RG stop,clean -s api,console -e $ENV
$RG conf,start -s api,console -e $ENV
