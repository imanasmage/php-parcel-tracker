<?php
/**
 * Abstract Carrier Class
 *
 * An abstract base class providing convienence methods for its concrete carriers.
 *
 * @package PHP_Parcel_Tracker
 * @author Brian Stanback <email@brianstanback.com>
 * @copyright Copyright (c) 2008, Brian Stanback
 * @license http://opensource.org/licenses/gpl-3.0.html GPLv3
 * @version 3.0 <27 July 2010>
 * @filesource
 * @abstract
 * @todo Add an abstract method for detecting whether a given tracking number
 *     if valid or not - this should make it possible to autodetect carriers
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

abstract class AbstractCarrier
{
    /**
     * Instance-specific configuration options.
     *
     * @var array
     */
    protected $config;

    /**
     * Class constructor: get and store passed formatting and retrieval settings.
     *
     * @param array $config Configuration settings passed by the ParcelTracker class.
     */
    public function __construct($config) {
        $this->config = $config;
    }

    /**
     * The abstract method (to be implemented by each specific carrier) which is
     * responsible for fetching and parsing tracking data into the target data
     * structure.
     *
     * The following associative array structure should be populated and returned
     * by this function:
     * 
     * array(
     *     'summary' => array(
     *         'service'       => [string],  // Service class
     *         'status'        => [string],  // Current status
     *         'destination'   => [string],  // Destination location
     *         'last_location' => [string],  // Last known location
     *         'departure'     => [string],  // Departure date/time
     *         'est_arrival'   => [string],  // Estimated arrival date/time
     *         'arrival'       => [string],  // Arrival date/time
     *         'details'       => [string]   // Miscellaneous details
     *     ),
     *     'locations' => array(
     *         [0] => array(
     *             'location'  => [string],  // Location name
     *             'status'    => [string],  // Status at location
     *             'time',     => [string],  // Date/time of package scan
     *             'details'   => [string]   // Package progress description
     *         ),
     *         [1] => array(
     *             ...
     *         ),
     *         ...
     *     )
     * )
     *
     * @abstract
     * @param string $trackingNumber The tracking number to retrieve details for.
     * @return array|boolean An associative array containing the 'details' and 'locations' or
     *    false if an error occured.
     */
    abstract function parse($trackingNumber);

    /**
     * Fetch data from a URL.
     *
     * @param string $url The url to fetch the HTML source for.
     */
    protected function fetchUrl($url) {
        if ($this->config['retrMethod'] == 'curl') {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $html = curl_exec($ch);
            curl_close($ch);

            if (function_exists('utf8_decode')) {
                $html = utf8_decode($html);
            }
        } else {
            $html = file_get_contents($url);
        }

        return $html;
    }
}
