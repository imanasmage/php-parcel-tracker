<?php
/**
 * Parses and returns FedEx package details.
 *
 * @package PHP_Parcel_Tracker
 * @subpackage Carrier
 * @author Brian Stanback <stanback@gmail.com>
 * @author Thom Dyson <thom@tandemhearts.com>
 * @copyright Copyright (c) 2008, Brian Stanback, Thom Dyson
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2.0
 * @version 3.0 <27 July 2010>
 * @filesource
 * @inheritdoc
 * @todo: Consider extracting more details from the JSON data (reference number,
 *     purchase order number shipment ID, invoice number, department number, status
 *     description, exceptions)
 */

/****************************************************************************
 * Copyright 2008 Brian Stanback, Thom Dyson
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

class FedExCarrier extends AbstractCarrier
{
    /**
     * Parse and return FedEx tracking details.
     *
     * @inheritdoc
     */
    public function fetchData($trackingNumber) {
        $link = 'http://www.fedex.com/Tracking/Detail?template_type=detail&trackNum=' . $trackingNumber;
        $html = $this->fetchUrl($link);

        if (!preg_match("#var detailInfoObject = ([^\n]+);\n#", $html, $jsonString)) {
            // Javascript-based package details object not found
            return false;
        }

        if (($data = json_decode($jsonString[1])) == false) {
            // Unable to decode expected JSON string
            return false;
        }

        // DEBUG
        //print_r($data);
        //exit;

        $stats = array();
        $locations = array();

        // Gather shipment statistics
        $stats = array(
            'status' => $data->status,
            'destination' => $data->deliveryLocation,
            'service' => $data->serviceType,
            'details' => $data->weight,
            'departure' => $data->shipDate,
            'arrival' => $data->delivered,
            'est_arrival' => $data->estimatedDeliveryDate
        );

        // Gather details for each scan along the route
        foreach ($data->scans as $scan) {
            $row = array(
                'status' => $scan->scanStatus,
                'time' => $scan->scanDate . ' ' . $scan->scanTime . ' ' . $scan->GMTOffset,
                'location' => $scan->scanLocation,
                'details' => ($scan->showReturnToShipper) ? 'Show return to shipper: ' . $scan->showReturnToShipper : ''
            );

            $locations[] = $row;
        }

        if (count($locations)) {
            // Set the last location
            $stats['last_location'] = $locations[0]['location'];

            // Set the delivery date if applicable
            if ($stats['arrival']) {
                $stats['arrival'] = $locations[0]['time'];
            }
        }

        return array(
            'details' => $stats,
            'locations' => $locations
        );
    }

    /**
     * Validate a FedEx tracking number (accepts express and ground formats).
     *
     * @inheritdoc
     */
    public function isTrackingNumber($trackingNumber) {
        return ($this->isGround($trackingNumber) ||
                $this->isExpress($trackingNumber));
    }

    /**
     * Validate a FedEx (Ground) tracking number by doing an initial sanity check, determining
     * the type, and verifying the check bit for the detected target type.
     *
     * @link http://fedex.com/us/solutions/ppe/FedEx_Ground_Label_Layout_Specification.pdf
     * @inheritdoc
     */
    public function isGround($trackingNumber) {
        if (strlen($trackingNumber) < 15 || !is_numeric($trackingNumber)) {
            return false;
        }

        $trackingNumber = strrev($trackingNumber);

        if (substr($trackingNumber, -2) == '00') {
            // Possible SSCC-18
            $numDigits = 16;
            $testDigits = substr($trackingNumber, 1, $numDigits);
        } else {
            // Possible 96 (with or without service/ucc/ean/scnc identifiers)
            $numDigits = 14;
            $testDigits = substr($trackingNumber, 1, $numDigits);
        }

        $oddSum = 0;
        $evenSum = 0;
        for ($i=0; $i<$numDigits; $i++) {
            if ($i % 2) {
                $oddSum += $testDigits[$i];
            } else {
                $evenSum += $testDigits[$i];
            }
        }

        $sum = ($oddSum + ($evenSum * 3));
        $checkDigit = ((ceil($sum / 10) * 10) - $sum);

        return ($checkDigit == $trackingNumber[0]);
    }

    /**
     * Validate a FedEx (Express) tracking number by doing an initial sanity check, determining
     * the type, and verifying the check bit for the detected target type.
     *
     * @link http://answers.google.com/answers/threadview/id/207899.html
     * @inheritdoc
     */
    public function isExpress($trackingNumber) {
        if (strlen($trackingNumber) != 12 || !is_numeric($trackingNumber)) {
            return false;
        }

        $checkDigits = array(1, 3, 7);
        $numCheckDigits = 3;

        $sum = 0;
        for ($i=10; $i>=0; $i--) {
            $sum += ($checkDigits[(10 - $i) % $numCheckDigits] * $trackingNumber[$i]);
        }
        $remainder = (($sum % 11) % 10);

        return ($remainder == $trackingNumber[11]);
    }
}
