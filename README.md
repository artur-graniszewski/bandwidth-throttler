# PHP Bandwidth Throttler

## Introduction

This library can be used to limit (throttle) the speed of files served for download.

It intercepts the PHP script output by setting a buffering handler that is called every time a given number of bytes are served to the browser.

The library measures the time since the last time the PHP output buffer was flushed and hold on PHP for a while if the average download speed is above a given limit.

![Example](http://php.webtutor.pl/throttler/speed_decrease.PNG)

## Features

There are three different bandwidth shaping mechanisms to ensure adequate Quality of Service:

* burst transfer rate will be switched off after sending X bytes to the user (this can be helpful to send small images quickly, and limit the download speed of huge files)
* burst transfer rate will be switched off after given period of time in seconds, then it will revert to the standard throttle speed
* burst transfer rate will not be activated at all, the download speed limit will be constant during the whole downloading process (in this case $config->burstLimit must be equal to the $config->rateLimit)

## Sample usage

### Example #1
Throttling by download duration (send files at the speed of 50.000 bytes for the first 30 seconds, then slow down to the 15.000 bytes per second)

```php
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

?>
```

### Example #2: 

Throttling by file size (send small files at the speed of 50.000 bytes per second, and bigger ones at the speed of 15.000 bytes per second)

```php
<?php
	use Zeus\QoS\Throttle;
	use Zeus\QoS\ThrottleConfigBySize;
	
	require("./throttler.php");

	// create new config
	$config = new ThrottleConfigBySize();
	// enable burst rate for first 500000 bytes, after that revert to the standard transfer rate
	$config->burstSize = 500000;
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
?>
```
            
See example.php and example2.php for working implementation.

## Details

### Terms definitions: Throttling process, Quality of Service

#### Throttling process (computing) 

In software, a throttling process, or a throttling controller as it is sometimes called, 
is a process responsible for regulating the rate at which application processing is conducted, 
either statically or dynamically.

For example, in high throughput processing scenarios, as may be common in online transactional 
processing (OLTP) architectures, a throttling controller may be embedded in the application hosting 
platform to balance the application's outbound publishing rates with its inbound consumption rates, 
optimize available system resources for the processing profile, and prevent eventually unsustainable 
consumption. In, say, an enterprise application integration (EAI) architecture, a throttling process 
may be built into the application logic to prevent an expectedly slow end-system from becoming 
overloaded as a result of overly aggressive publishing from the middleware tier.

#### Quality of Service

In the field of computer networking and other packet-switched telecommunication networks, the traffic 
engineering term quality of service (QoS) refers to resource reservation control mechanisms rather than 
the achieved service quality. Quality of service is the ability to provide different priority to different 
applications, users, or data flows, or to guarantee a certain level of performance to a data flow. 
For example, a required bit rate, delay, jitter, packet dropping probability and/or bit error rate may 
be guaranteed. Quality of service guarantees are important if the network capacity is insufficient, 
especially for real-time streaming multimedia applications such as voice over IP, online games and IP-TV, 
since these often require fixed bit rate and are delay sensitive, and in networks where the capacity is 
a limited resource, for example in cellular data communication.

### Technical aspects and caveats of Bandwidth throttling using PHP

First of all, the PHP throttling mechanism is not a good equivalent of a low-level system traffic shaping.
The major problem is that PHP takes more system resources to handle the user request than a system firewall 
(standard PHP process can take as much as 15-30MB of the system memory just to send a file over a network).

Another problem is that on shared hostings support for set_time_limit() function in PHP is limited (due to 
the safe mode or other system configuration), so when the downloaded file is too big, it has to be send to the 
user in more than 30 seconds (a default PHP time limit after which request is automatically aborted). In this 
case to send something bigger, you have to ask your system administrator to increase your time limit above 30 
seconds.

The last thing to remember is that PHP can be executed on many different OS'es and APIs. For example PHP can be
installed as an Apache module (mod_php.so), or as a FastCGI module (mod_fcgi + suexec), etc. In any of this
scenarios there is a potential risk, that bandwidth throttling may be unsuccesfull because of the internal 
output buffering (see for example Apache directive "SendBufferSize" in httpd.conf, or FastCGI output buffering) 
which sometimes cannot be properly controlled in the PHP. In this case you have to ask your system administrator 
to alter configuration files respectively (this ofcourse can be problematic on shared hostings).

### Pros and cons of this library

#### PROS:

* small memory footprint - output is sent realtime, so even 12GB of generated data can be sent using ~8MB of RAM.
* low CPU usage - every system call (like microtime()) is done only once per packet sent, every mathematical calculation result is cached when necessary.
* nonintrusive throttling - you just need to do one include() in your index.php or master file to use this class (usually there is no need to change your own code, like phpBB or Drupal)
* working with PHP 5.0, 5.2, 5.3+, as an Apache module, FastCGI or even CLI process on all known Operating Systems.
* working out of the box!

#### CONS:
* usually needs more time than a default 30 seconds timeout set in php.ini file (for bigger files or slow transfers)
* can be scrambled by an unusual system configuration (see output buffering in caveats section)
* higher memory usage than in firewall equivalent
* can easily exhaust limit of user PHP processes set in FastCGI configuration (for example FCGI_MAX_CHILDREN), when used simultaneously by many downloaders.