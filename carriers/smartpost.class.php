<?php
/**
 * Parses and returns SmartPost package details.
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

class SmartPostCarrier extends AbstractCarrier
{
    /**
     * Parse and return Smart Post tracking details.
     */
    public function parse($trackingNumber) {
        $link = 'http://spportal.fedex.com/sp/tracking.htm?PID=' . $trackingNumber;
        $html = $this->fetchUrl($link);

        if (!preg_match_all('#<td class="resultscell"[^>]+>([^>]+)</td>[^>]+<td class="resultscell"[^>]+>([^>]+)</td>[^>]+<td class="resultscell"[^>]+>([^>]+)</td>[^>]+<td class="resultscell"[^>]+>([^>]+)</td>#', $html, $matches, PREG_SET_ORDER)) {
            // Results not found
            return false;
        }

        $stats = array();
        $locations = array();

        $count = 0;
        $total = count($matches)-1;
        foreach ($matches as $match) {
            list($match, $date, $time, $status, $location) = $match;

            $row = array(
                'status' => $status,
                'time' => $date . ' ' . $time,
                'location' => $location
            );

            if ($count == 0) {
                $stats['status'] = $status;
                $stats['last_location'] = $location;

                if ($stats['status'] == 'Delivered') {
                    $stats['arrival'] = $date . ' ' . $time;
                }
            } elseif ($count == $total) {
                $stats['departure'] = $row['time'];
            }

            $locations[] = $row;
            $count++;
        }

        return array(
            'details' => $stats,
            'locations' => $locations
        );
    }
}
