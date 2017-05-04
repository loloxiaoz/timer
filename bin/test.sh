env=$1

if test -e $env
then
    env='dev'
fi

/data/x/tools/rigger-ng/rg reconf,restart  -s api,console -e $env
/data/x/tools/rigger-ng/rg reconf,restart  -s test -e $env
