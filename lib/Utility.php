<?php
/**
 * lib/Utility.php.
 *
 * This class contains a bunch of public static member functions with various uses
 * and have been collected from various sources over the years
 *
 * PHP version 5
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3.0 of the License, or (at your option) any later version.
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category  default
 *
 * @author    ohtarr
 * @copyright 2016 @authors
 * @license   http://www.gnu.org/copyleft/lesser.html The GNU LESSER GENERAL PUBLIC LICENSE, Version 3.0
 */
namespace ohtarr;

class Utility
{
    public function __construct()
    {
        throw new \Exception("Do not create instances of this object, call public static member functions like \ohtarr\Utility::someDumbThing(params)");
    }

    /*
        determine if a string is valid json to decode, return bool
    */
    public static function isJson($string)
    {
        json_decode($string);

        return json_last_error() === JSON_ERROR_NONE;
    }

	public function curl_automation_report_api( $POST = array() )
    {
		/*
				origin_hostname		REQUIRED	Text (50 characters)
				processname			REQUIRED	Text (50 characters)
				category			REQUIRED	Text (50 characters)
				timesaved			REQUIRED	Integer
				datestarted			REQUIRED	Date (YYYY-MM-DD HH:MM:SS)
				datefinished		REQUIRED	Date (YYYY-MM-DD HH:MM:SS)
				success				REQUIRED	Text (50 characters)
				target_hostname		OPTIONAL	Text (50 characters)
				triggeredby			OPTIONAL	Text (50 characters)
				description			OPTIONAL	Text
				target_ip			OPTIONAL	Text (50 characters)
				notes				OPTIONAL	Text
		*/
		$URL = AUTO_REPORT_API_DEV;
        $CURL = curl_init($URL);
		//url-ify the data for the POST
		foreach($POST as $KEY => $VALUE ) {
			// handle basic arrays
			if(is_array($VALUE)) {
				die("Value cannot be an array!");
			// Handle simple values as strings
			}else{
				$FARRAY[] = $KEY . '=' . $VALUE;
			}
		}
		$PSTRING = implode('&',$FARRAY);
		dumper($PSTRING);
		// setup curl options
        $OPTS = array(
								// send basic authentication as the tools service account in AD
								CURLOPT_USERPWD			=> LDAP_USER . ":" . LDAP_PASS,
                                // We will be sending POST requests
                                CURLOPT_POST            => true,
								CURLOPT_POSTFIELDS		=> $PSTRING,
								CURLOPT_HEADER			=> false,
                                // Generic client stuff
                                CURLOPT_RETURNTRANSFER  => true,
                                CURLOPT_FOLLOWLOCATION  => true,
                                CURLOPT_USERAGENT       => "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)",
								CURLOPT_TIMEOUT			=> 30,
                                // Debugging
                                //CURLOPT_CERTINFO      => true,
                                //CURLOPT_VERBOSE       => true,
                                );
        curl_setopt_array($CURL,$OPTS);

		// execute the curl request and get our response
//        $RESPONSE = curl_exec($CURL);
		// close the curl handle
//		curl_close($CURL);
		// return the complete response
//       return $RESPONSE;
    }
	public function auto_report_test()
	{
		return $this->curl_automation_reportdev_api(				[
													"origin_hostname"		=> "netman test",
													"processname"		=> "netman test",
													"category"			=> "network",
													"timesaved"		=> "2",
													"datestarted"			=> "2016-03-28 09:01:00",
													"datefinished"	=> "2016-03-28 09:01:30",
													"success"	=> true,
												]);
	}


}
