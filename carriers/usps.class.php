<?php
/**
 * Parses and returns USPS package details.
 *
 * @package PHP_Parcel_Tracker
 * @subpackage Carrier
 * @author Brian Stanback <email@brianstanback.com>
 * @author Thom Dyson <thom@tandemhearts.com>
 * @copyright Copyright (c) 2008, Brian Stanback, Thom Dyson
 * @license http://opensource.org/licenses/gpl-3.0.html GPLv3
 * @version 3.0 <27 July 2010>
 * @filesource
 * @inheritdoc
 */

/****************************************************************************
* This file is part of PHP Parcel Tracker
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************/

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
