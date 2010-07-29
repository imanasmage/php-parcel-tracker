<?php
/**
 * PHP Parcel Tracker
 *
 * Cache-enabled RSS gateway for tracking packages.
 * Requires PHP 5 or greater.
 *
 * @package PHP_Parcel_Tracker
 * @author Brian Stanback <email@brianstanback.com>
 * @copyright Copyright (c) 2008, Brian Stanback
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2.0
 * @version 3.0 <27 July 2010>
 * @filesource
 */

/****************************************************************************
 * Copyright 2008 Brian Stanback
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 ***************************************************************************/

$testNumbers = array(
	array('069537856437603', 'fedex'),
	array('069537856437603'),
);

include_once('parceltracker.class.php');

$tracker = new ParcelTracker();

foreach ($testNumbers as $test) {
	list($number, $carrier) = $test;

	$parcel = $tracker->getDetails($number, $carrier);

	if (!$parcel) {
		// Assert false

		echo "FAIL $number ($carrier)\n";
	} else {
		// Assert true
		//print_r($parcel);

		echo "PASS $number ($carrier)\n";
	}
}
