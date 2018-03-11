<?php

use Zeus\QoS\Throttle;
use Zeus\QoS\ThrottleConfig;

require("./throttler.php");

// create new config
$config = new ThrottleConfig();
// enable burst rate for 30 seconds
$config->burstTimeout = 30;
// set burst transfer rate to 50000 bytes/second
$config->burstLimit = 50000;
// set standard transfer rate to 15.000 bytes/second (after initial 30 seconds of burst rate)
$config->rateLimit = 15000;
// enable module (this is a default value)
$config->enabled = true;

// start throttling
$x = new Throttle($config);

header("Content-type: application/force-download");
header("Content-Disposition: attachment; filename=\"test.txt\"");
header("Content-Length: 60000000");

// generate 60.000.000 bytes file.  
for($i = 0; $i < 60000000; $i++) {
	echo "A";
}
