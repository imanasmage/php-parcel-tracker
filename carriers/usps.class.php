<?php
/**
 * Parses and returns USPS package details.
 *
 * @package PHP_Parcel_Tracker
 * @subpackage Carrier
 * @author Brian Stanback <email@brianstanback.com>
 * @author Thom Dyson <thom@tandemhearts.com>
 * @copyright Copyright (c) 2008, Brian Stanback, Thom Dyson
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2.0
 * @version 3.0 <27 July 2010>
 * @filesource
 * @inheritdoc
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

class USPSCarrier extends AbstractCarrier
{
    /**
     * Parse and return USPS tracking details.
     */
    public function parse($trackingNumber) {
        $link = 'http://trkcnfrm1.smi.usps.com/PTSInternetWeb/InterLabelInquiry.do?strOrigTrackNum=' . urlencode($trackingNumber);
        $html = $this->fetchUrl($link);

        if (!preg_match('#Status: <span class="mainTextbold">([^<]+)</span><br><br>([^<]+)<br><br>#', $html, $matches)) {
            // No tracking results found
            return false;
        }

        $stats = array();
        $locations = array();

        $stats['status'] = $matches[1];
        $stats['details'] = trim($matches[2]);

        if (preg_match_all('#<INPUT TYPE=HIDDEN NAME="event([0-9]+)" VALUE="([^"]+)">#', $html, $matches, PREG_SET_ORDER)) {
            // Location details

            $count = 0;
            $total = count($matches)-1;

            foreach ($matches as $match) {
                if (preg_match('#^([^,;]+)(,|;) ([A-Za-z]+) ([0-9]{1,2}), ([0-9]{4})(, ([0-9:a-zA-Z ]{7,8})(, (.*))?)?$#', $match[2], $details)) {
                    $row = array(
                        'status' => $details[1],
                        'time' => $details[3] . ' ' . $details[4] . ', ' . $details[5]
                    );
                    if (isset($details[7])) {
                        $row['time'] .= ' ' . strtoupper($details[7]);
                    }
                    if (isset($details[9])) {
                        $row['location'] = $details[9];
                    }

                    if ($count == 0) {
                        $stats['last_location'] = isset($row['location']) ? $row['location'] : '';
                        if ($row['status'] == 'Delivered') {
                            $stats['arrival'] = $row['time'];
                        }
                    } elseif ($count == $total) {
                        $stats['departure'] = $row['time'];
                    }

                    $count++;
                } else {
                    $row = array('status' => $match[2]);
                }

                $locations[] = $row;
            }
        }

        return array(
            'details' => $stats,
            'locations' => $locations
        );
    }
}
