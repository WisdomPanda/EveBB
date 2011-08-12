<?php
/**
 * 05/05/2011
 * bcmath.php
 * Panda
 */

//This file replaces our BCMath functions and allows us to wrap around it. Using BCMath for 32-bit, normal stuff for 64-bit.

//Lets do our 64bit/32bit check.
$bc_test = "9223372036854775807";
$bc_test = intval($bc_test);
if ($bc_test == 9223372036854775807) {
	define('64BIT', 1);
} //End if.

if (!defined('64BIT') && !function_exists('bcscale')) {
	die('You are trying to run EveBB on a 32-bit environment without BCMath.<br/>
	<br/>
	Please click <a href="http://www.php.net/manual/en/book.bc.php">here</a> to learn about how to configure BCMath.');
} //End if.

function bscale($x) {
	if (!defined('64BIT')) {
		return bcscale($x);
	} //End if.
	return true; //Ignore for 64bit.
} //End function ().

function badd($x, $y) {
	if (defined('64BIT')) {
		return intval($x) + intval($y);
	} else {
		return bcadd($x, $y);
	} //End if - else.
} //End function ().

function bsub($x, $y) {
	if (defined('64BIT')) {
		return intval($x) - intval($y);
	} else {
		return bcsub($x, $y);
	} //End if - else.
} //End function ().

function bdiv($x, $y) {
	if (defined('64BIT')) {
		return intval(intval($x) / intval($y));
	} else {
		return bcdiv($x, $y);
	} //End if - else.
} //End function ().


?>