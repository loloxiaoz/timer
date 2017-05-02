<?php
require_once('pylon/pylon.php');  
/*
pylon_using_module("/home/q/php/svc_utls");
$xhprof = new XhprofSvc();
$xhprof->autoRecode(); 
 */

XPylon::rest_serving('mara_cron');
