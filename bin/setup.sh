env=$1
RG=/data/x/tools/rigger-ng/rg

$RG stop,clean -s api,console -e $env
$RG conf,start -s api,console -e $env
