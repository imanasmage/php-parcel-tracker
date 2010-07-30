<?php
/**
 * PHP Parcel Tracker Test Driver
 *
 * A simple test driver for validating the detecting and data gathering
 * of different carriers/tracking numbers.
 *
 * @package PHP_Parcel_Tracker
 * @author Brian Stanback <email@brianstanback.com>
 * @copyright Copyright (c) 2008, Brian Stanback
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2.0
 * @version 3.0 <27 July 2010>
 * @todo Integrate tests with PHPUnit or SimpleUnit.
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

include_once('parceltracker.class.php');

$testNumbers = array(
    // Insert your tracking numbers to test here
);

testDetection($testNumbers);
//testDataRetrieval($testNumbers);

/**
 * Test detection of carriers.
 *
 * @param $testNumbers array An array of tracking numbers to test.
 */
function testDetection($testNumbers) {
    $tracker = new ParcelTracker();

    foreach ($testNumbers as $number) {
        $carrier = $tracker->detectCarrier($number);

        if (!$carrier) {
            // Assert false
            echo "FAIL $number\n";
        } else {
            // Assert true
            echo "PASS $number ($carrier)\n";
        }
    }
}

/**
 * Test tracking data retrieval.
 *
 * @param $testNumbers array An array of tracking numbers to test.
 */
function testDataRetrieval($testNumbers) {
    $tracker = new ParcelTracker();

    foreach ($testNumbers as $number) {
        $parcel = $tracker->getDetails($number);

        if (!$parcel) {
            // Assert false
            echo "FAIL $number\n";
        } else {
            // Assert true
            echo "PASS $number\n";
            //print_r($parcel);
        }
    }
}
