<?php
/**
 * Parses and returns UPS package details.
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

class UPSCarrier extends AbstractCarrier
{
    /**
     * Get tracking data from UPS.
     */
    public function parse($trackingNumber) {
        $link = 'http://wwwapps.ups.com/WebTracking/detail?&showSpPkgProg=Show%20Package%20Progress&tracknum=' . $trackingNumber;
        $html = $this->fetchUrl($link);

        $stats = array();
        $locations = array();

        // Split the page into pieces to make it easier to handle (aka debug)
        $data = explode('</fieldset>', $html);

        foreach ($data as $datablock) {
            if (strstr($datablock, '<legend>Tracking Information</legend>')) {
                // This is the section with the tracking summary

                if (preg_match_all('#(<dt><label>.+?<\/dd>)#uis', $datablock, $matches, PREG_PATTERN_ORDER)) {
                    // Find all the entries in the tags
                    $row = array();

                    // Get the array
                    $cleanmatch = $matches[1];

                    // Strip out mulitple line feeds
                    $cleanmatch = preg_replace('/\s\s+/', ' ', $cleanmatch);

                    foreach ($cleanmatch as $singlematch) {
                        // Remove all the extra white space
                        $singlematch = preg_replace('/\s/', ' ', $singlematch);

                        // Get the label from the string
                        preg_match("#<label>(.+?)<\/label>#ui",$singlematch,$resultsline);
                        $name = strtolower(trim($resultsline[1]));

                        if ($name == "status:") {
                            // Status statements are formatted differently than the rest
                            preg_match("#<h3>(.+?)<\/h3>#ui", $singlematch, $resultsvalue);
                        } else {
                            // Get the values by finding the data elements 
                            preg_match("#<dd>(.+?)<\/dd>#ui", $singlematch, $resultsvalue);
                        }

                        $value = trim($resultsvalue[1]);

                        switch ($name) {
                            case 'status:':
                                $stats['status'] = $value;
                                break;
                            case 'shipped to:':
                                $stats['destination'] .= $value;
                                break;
                            case "tracking number:":
                                break;
                            case "weight:":
                                $stats['details'] = $value;
                                break;
                            case "type:":
                                break;
                            case 'location:':
                                $stats['destination'] .=  $value . ", ";
                                break;
                            case 'delivered to:':
                                $stats['destination'] .= $value;
                                break;
                            case 'scheduled delivery date:':
                                $stats['est_arrival'] = $value;
                                break;
                            case 'date:':
                                if (!isset($stats['arrival'])) {
                                    $stats['arrival'] = $value;
                                } else {
                                    $stats['est_arrival'] = $value;
                                }
                                break;
                            case 'delivered on:':
                                $stats['arrival'] .= ' ' . $value;
                                break;
                            case 'shipped&#047;billed on:':
                                $stats['departure'] = $value;
                                break;
                            case 'service:':
                            case 'servicetype':
                                $stats['service'] = ucwords(strtolower($value));
                                break;
                            case 'table_city':
                                $row['city'] = !empty($value) ? ucwords(strtolower(str_replace('"+",<br>', '', $value))) . ', ' : '';
                                $row['state'] = '';
                                $row['country'] = '';
                                break;
                            case 'table_state':
                                $row['state'] = !empty($value) ? str_replace('"+", ', '', $value) . ', ' : '';
                                break;
                            case 'table_country':
                                $row['country'] = $value;
                                break;
                            case 'data[1]':
                                $row['time'] = $value;
                                break;
                            case 'data[2]':
                                $row['time'] .= ' ' . $value;
                                break;
                            case 'data[3]':
                                $row['status'] = ucwords(strtolower($value));
                                break;
                            default:
                                //$output .= "LOST PROCESSING " . $name . "\n";
                        }
                    }
                } else {
                    return false;
                }
            } elseif (strstr($datablock, 'Hide Package Progress')) {
                // This is the section with the tracking detail
                $datablock = preg_replace('#[ \t]+#', ' ', $datablock);

                // Consolidate white space
                $datablock = preg_replace('#^ #', '', $datablock);

                // Consolidate multiple carrage returns
                $datablock = preg_replace('#\s\s+#', ' ', $datablock);

                if (preg_match_all("#(<td nowrap VALIGN=\"top\">.+?<\/tr>)#uis", $datablock, $matches, PREG_PATTERN_ORDER)) {
                    $row = array();
                    $cleanmatch = $matches[0];
                    $rowcount = 0;
                    foreach ($matches[0] as $singleline) {
                        if (preg_match_all("#(VALIGN=\"top\">(.+?)<\/td>)#uis", $singleline, $tinymatches, PREG_PATTERN_ORDER)) {
                            $rowcount++;									
                            // UPS leaves the location blank if repeated from above
                            if (strlen(trim($tinymatches[2][0])) <> 0) {
                                $row['location'] = trim($tinymatches[2][0]);
                            } 

                            $row['time'] = $tinymatches[2][1] . " " . $tinymatches[2][2];
                            $row['status'] = trim($tinymatches[2][3]);
                        }

                        if ($rowcount == 1) {
                            $stats['last_location'] = $row['location'] . ' (' . $row['status'] . ' at ' . $row['time'] . ")\n";
                        } else {
                            $locations[] = $row;
                        }
                    }
                }
            }
        }

        if (!count($stats)) {
            // No record of the package was found
            return false;
        }

        return array(
            'details' => $stats,
            'locations' => $locations
        );
    }
}
