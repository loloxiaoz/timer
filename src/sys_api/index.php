<?php
require_once('pylon/pylon.php');

XSetting::$logMode  = XSetting::LOG_DEBUG_MODE ;
XSetting::$prjName   = "mara_cron" ;
XSetting::$logTag    = XSetting::ensureEnv("USER") ;
XSetting::$runPath   = XSetting::ensureEnv("RUN_PATH") ;
XSetting::setupModel();
XPylon::serving();
