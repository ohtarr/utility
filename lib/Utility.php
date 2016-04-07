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
        throw new \Exception("Do not create instances of this object, call public static member functions like \metaclassing\Utility::someDumbThing(params)");
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
		$URL = API_REPORTING_URL;
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
        $RESPONSE = curl_exec($CURL);
		// close the curl handle
		curl_close($CURL);
		// return the complete response
		return $RESPONSE;
    }

	public function Find_Switches()
    {
		// Use the object (information store) search function to find the devices we WANT to push to
		$SEARCH = array();

		// what we want to find
		$SEARCH = array(    // Search for all cisco network devices
                "category"		=> "Management",
                "type"			=> "Device_Network_Cisco",
                );

		// Do the actual search
		$SWITCHES = array();
		$RESULTS = \Information::search($SEARCH);
		foreach($RESULTS as $OBJECTID)
		{
			// Get the information for the device matching the specific ID
			$DEVICE = \Information::retrieve($OBJECTID);

			// Regex to match all devices named swa/swp
			$PATTERN = "/^[a-zA-Z]{8}[sS][wW][aApPcCdD][0-9]{2,3}$/";

			//Search our list of cisco devices for all swa/swps.
			if ( preg_match($PATTERN,$DEVICE->data["name"],$REG) )
			{
				//If device is a switch, add to our array!
				$SWITCHES[]=$OBJECTID;  
			}
		}
		//die(\metaclassing\Utility::dumper($SWITCHES));  //debugging
		
		//return the array to the caller!
		return($SWITCHES); 
	}
	public function get_snow_locations()
	{
		// Instance (kiewit = prod, kiewitdev = dev, might be a test one as well?)
		$INSTANCE = "kiewit";
		//$INSTANCE = "kiewitdev";

		// Table to query, translation map in SNSoapClient class for some
		$TABLE = "location";

		// Include our library objects
		require_once(BASEDIR . "/vendor/metaclassing/snsoapclient/phpsoapclient/class.Record.php");
		require_once(BASEDIR . "/vendor/metaclassing/snsoapclient/phpsoapclient/Class.SoapClient.php");
		require_once(BASEDIR . "/vendor/metaclassing/snsoapclient/phpsoapclient/Class.DefaultValues.php");

		$OPTIONS = [
					"login" => LDAP_USER,
					"password" => LDAP_PASS,
					"instance" => $INSTANCE,
					"tableName" => $TABLE,
					"debug" => FALSE,
                    ];
		$SERVICENOW = new \SNSoapClient($OPTIONS);

		///////////////////////////////////////////////////////////////////////////////////////////////////////////
		// by default, this returns a maximum of 250 items...
		$RECORDS = array();     // Our final results array built by the chunk foreach
		$KEYNAME = "sys_id";    // sys_id is the current key value returned
		$CHUNKSIZE = 250;       // 250 records at a time (max supported by ServiceNow API)
		// So we are going to need the complete keys list...
		$KEYS = $SERVICENOW->getKeys();
		// and array_chunk them into separate requests
		//\metaclassing\Utility::dumper($KEYS);
		foreach ( array_chunk($KEYS,250) as $RECORDKEYS ) {
			\metaclassing\Utility::dumper($RECORDKEYS);
			$SEARCH = "sys_id=" . implode( "^ORsys_id=" , $RECORDKEYS );// Form a crafted search for our key chunk
			$RECORDSRESPONSE = $SERVICENOW->getRecords($SEARCH);        // Run our search
			$RECORDS = array_merge($RECORDS , $RECORDSRESPONSE );       // Add response to our records array
			//\metaclassing\Utility::dumper($RECORDS);
			//print "\n";
		}
		// Convert the soap reply objects into an assoc array (recursively)
		$RECORDS = \metaclassing\Utility::objectToArray($RECORDS);
		//\metaclassing\Utility::dumper($RECORDS);

		$SITES = [];
		foreach($RECORDS as $RECORD) {
			if(isset($RECORD["soapRecord"]["full_name"])) {
				$SITE = substr($RECORD["soapRecord"]["full_name"],0,8);
				$SITES[$SITE] = $SITE;
			}
		}
		ksort($SITES);

		return $SITES;
	}

    public function snow_tableapi_getTable()
	{
		$uri = API_SNOW_URL . "/api/now/v1/table/cmn_location";
		return \Httpful\Request::get($uri)                  // Build a PUT request...
						->expectsJson()
						->authenticateWith(LDAP_USER, LDAP_PASS)  // authenticate with basic auth...
						->parseWith("\\metaclassing\\Utility::decodeJson")
 						->send()
						->body;
		//$json = $response->raw_body;
		//return \metaclassing\Utility::decodeJson($json);
	}

    public function snow_tableapi_getTable2($TABLE)
	{
		$uri = API_SNOW_URL . "/" .  $TABLE . ".do?JSONv2&sysparm_action=getKeys";
		$uri = API_SNOW_URL . "/" .  $TABLE . ".do?JSONv2";
		return \Httpful\Request::get($uri)
						->expectsJson()
						->authenticateWith(LDAP_USER, LDAP_PASS)
						->parseWith("\\metaclassing\\Utility::decodeJson")
/*						->parseWith(function($body){
								return json_decode($body, true);
							})/**/
 						->send()
						->body;
		//$json = $response->raw_body;
		//return \metaclassing\Utility::decodeJson($json);
	}

/*
    public function snow_tableapi_getTable( $TABLE )
    {
		// create a curl handle for our URL
        $URL = API_SNOW_URL."/api/now/v1/table/{$TABLE}";
		//print $URL;
        $CURL = curl_init($URL);

		// setup curl options
        $OPTS = array(
								// send basic authentication as the tools service account in AD
								CURLOPT_USERPWD			=> LDAP_USER . ":" . LDAP_PASS,
                                // Generic client stuff
								CURLOPT_HEADER			=> true,
                                CURLOPT_RETURNTRANSFER  => true,
                                CURLOPT_FOLLOWLOCATION  => true,
								CURLOPT_HTTPGET			=> true,
                                CURLOPT_USERAGENT       => "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)",
								CURLOPT_TIMEOUT			=> 30,
								//SNOW HEADERS
								CURLOPT_HTTPHEADER		=> array('Accept: application/json','Content-type: application/json'),
                                );
		\metaclassing\Utility::dumper($OPTS);
        curl_setopt_array($CURL,$OPTS);

		// execute the curl request and get our response
        $RESPONSE = curl_exec($CURL);
		// close the curl handle
		curl_close($CURL);
		// return the complete response
        print $RESPONSE;
		return $RESPONSE;
    }
/**/

	public function parse_nested_list_to_array($LIST, $INDENTATION = " ")
	{
		$RESULT = array();
		$PATH = array();

		$LINES = explode("\n",$LIST);

		foreach ($LINES as $LINE)
		{
			if ($LINE == "") { continue; print "Skipped blank line\n"; } // Skip blank lines, they dont need to be in our structure
			$DEPTH	= strlen($LINE) - strlen(ltrim($LINE));
			$LINE	= trim($LINE);
			// truncate path if needed
			while ($DEPTH < sizeof($PATH))
			{
				array_pop($PATH);
			}
			// keep label (at depth)
			$PATH[$DEPTH] = $LINE;
			// traverse path and add label to result
			$PARENT =& $RESULT;
			foreach ($PATH as $DEPTH => $KEY)
			{
				if (!isset($PARENT[$KEY]))
				{
					$PARENT[$LINE] = array();
					break;
				}
				$PARENT =& $PARENT[$KEY];
			}
		}
		$RESULT = \metaclassing\Utility::recursiveRemoveEmptyArray($RESULT);
		//ksort($RESULT);	// Sort our keys in the array for comparison ease // Do we really need this?
		return $RESULT;
	}

}
