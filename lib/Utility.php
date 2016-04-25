<?php
//require_once "/opt/iahunter/php-911enable-egw/lib/EGW.php";
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


	public function E911_get_erls(){


		$URI = BASEURL . "/api/911-get-locations.php";
		//print $URI;
		return \Httpful\Request::get($URI)											//Build a PUT request...
								->expectsJson()										//we expect JSON back from the api
//								->authenticateWith(LDAP_USER, LDAP_PASS)			//authenticate with basic auth...
								->parseWith("\\metaclassing\\Utility::decodeJson")	//Parse and convert to an array with our own parser, rather than the default httpful parser
								->send()											//send the request.
								->body;											

	}
	
	public function E911_to_add(){
		
		$object = new \ohtarr\ServiceNowRestClient;						//initialize a new snow rest api call
		//$locations = $object->SnowGetAllRecords("cmn_location");		//get all locations from snow

		//params for our snow api call (active and only give us the names)
		$PARAMS = array(
							"u_active"                	=> "true",
							
							"sysparm_fields"        	=> "name,street",
		);
		$locations = $object->SnowTableApiGet("cmn_location", $PARAMS);  //get all locations from snow api

		//extract the NAME of each location into a new array
		foreach($locations as $location){
			foreach($location as $lname){
				$locname[]=$lname[name];
			}
		}
		sort($locname);													//sort the array

		$erls = \ohtarr\Utility::E911_get_erls();						//get E911 erls via netman API to E911 gateway
		
		//extract the NAME of each erl into a new array
		foreach($erls as $erl){		
			$erlname[]=$erl[erl_id];
		}
		sort($erlname);													//sort the array

		return array_values(array_diff($locname,$erlname));
	}

	public function E911_to_remove(){
		
		$object = new \ohtarr\ServiceNowRestClient;						//initialize a new snow rest api call
		$locations = $object->SnowGetAllRecords("cmn_location");		//get all locations from snow

		//extract the NAME of each location into a new array
		foreach($locations as $location){
			$locname[]=$location[name];
		}
		sort($locname);													//sort the array

		$erls = \ohtarr\Utility::E911_get_erls();						//get E911 erls via netman API to E911 gateway
		
		//extract the NAME of each erl into a new array
		foreach($erls as $erl){		
			$erlname[]=$erl[erl_id];
		}
		sort($erlname);													//sort the array

		return array_values(array_diff($erlname,$locname));
	}

	public function E911_add_erls(){
		
		$object = new \ohtarr\ServiceNowRestClient;						//initialize a new snow rest api call
		//$locations = $object->SnowGetAllRecords("cmn_location");		//get all locations from snow

		//params for our snow api call (active and only give us the names)
		$PARAMS = array(
							"u_active"                	=> "true",
//							"sysparm_fields"        	=> "name,street,city,state,zip,country",
							"sysparm_fields"        	=> "name,street",
		);
		$locations = $object->SnowTableApiGet("cmn_location", $PARAMS);  //get all locations from snow api

		//extract the NAME of each location into a new array
		foreach($locations as $location){
			foreach($location as $lname){
				$locname[]=$lname[name];
			}
		}
		sort($locname);													//sort the array

		$erls = \ohtarr\Utility::E911_get_erls();						//get E911 erls via netman API to E911 gateway
		
		//extract the NAME of each erl into a new array
		foreach($erls as $erl){		
			$erlname[]=$erl[erl_id];
		}
		sort($erlname);													//sort the array
		print_r($locations);
		//return array_values(array_diff($locname,$erlname));
	}

	function getInfoAddress ($address)
    {
        $return = array('street'=>NULL,
                        'number'=>NULL,
                        'complement'=>NULL);

        //firstly, erase spaces of the strings
        $addressWithoutSpace = str_replace(' ', '', $address);
        //discover the pattern using regex
        if(preg_match('/^([0-9.-])+(.)*$/',$addressWithoutSpace) === 1) {
            //here, the numbers comes first and then the information about the street
            $info1 = preg_split('/[[:alpha:]]/', $addressWithoutSpace);
            $info2 = preg_split('/[0-9.-]/', $address);
            $return['number'] = $info1[0];
            $return['street'] = end($info2);
        }
        elseif(preg_match('/^([[:alpha:]]|[[:punct:]])+(.)*$/',$addressWithoutSpace) === 1) {
            //here, I have a alpha-numeric word in the first part of the address
            if(preg_match('/^(.)+([[:punct:]])+(.)*([0-9.-])*$/',$addressWithoutSpace) === 1) {
                if(preg_match('/,/',$addressWithoutSpace) === 1) {
                    //have one or more comma and ending with the number
                    $info1 = explode(",", $address);
                    $return['number'] = trim(preg_replace('/([^0-9-.])/', ' ', end($info1)));//the last element of the array is the number
                    array_pop($info1);//pop the number from array
                    $return['street'] = str_replace(",", "",implode(" ",$info1));//the rest of the string is the street name
                }
                else {
                    //finish with the numer, without comma
                    $info1 = explode(" ", $address);
                    $return['number'] = end($info1);//the last elemento of array is the number
                    array_pop($info1);//pop the number from array
                    $return['street'] = implode(" ",$info1);//the rest of the string is the street name
                }
            }
            elseif(preg_match('/^(.)+([0-9.-])+$/',$addressWithoutSpace) === 1) {
                //finish with the number, without punctuation
                $info1 = explode(" ", $address);
                $return['number'] = end($info1);//the last elemento of array is the number
                array_pop($info1);//pop the number from array
                $return['street'] = implode(" ",$info1);//the rest of the string is the street name
            }
            else {
                //case without any number
                if (preg_match('/,/',$addressWithoutSpace) === 1) {
                    $return['number'] = NULL;
                    $endArray = explode(',', $address);
                    $return['complement'] = end($endArray);//complement is the last element of array
                    array_pop($endArray);// pop the last element
                    $return['street'] = implode(" ", $endArray);//the rest of the string is the name od street
                }
                else {
                    $return['number'] = NULL;
                    $return['street'] = $address;//address is just the street name
                }
            }
        }

        return ($return);
    }

	public function parseAddress($ADDRESS){

		


	}

function splitAddress($address) {
    // Get everything up to the first number with a regex
    $hasMatch = preg_match('/^[^0-9]*/', $address, $match);
    // If no matching is possible, return the supplied string as the street
    if (!$hasMatch) {
        return array($address, "", "");
    }
    // Remove the street from the address.
    $address = str_replace($match[0], "", $address);
    $street = trim($match[0]);
    // Nothing left to split, return
    if (strlen($address == 0)) {
        return array($street, "", "");
    }
    // Explode address to an array
    $addrArray = explode(" ", $address);
    // Shift the first element off the array, that is the house number
    $housenumber = array_shift($addrArray);
    // If the array is empty now, there is no extension.
    if (count($addrArray) == 0) {
        return array($street, $housenumber, "");
    }
    // Join together the remaining pieces as the extension.
    $extension = implode(" ", $addrArray);
    return array($street, $housenumber, $extension);
}

	public function SnowGetAddresses(){
		
		$object = new \ohtarr\ServiceNowRestClient;						//initialize a new snow rest api call
		//$locations = $object->SnowGetAllRecords("cmn_location");		//get all locations from snow

		//params for our snow api call (active and only give us the names)
		$PARAMS = array(
							"u_active"                	=> "true",
							"sysparm_fields"        	=> "name,street,city,state,zip,country",
		);
		$locations = $object->SnowTableApiGet("cmn_location", $PARAMS);  //get all locations from snow api

		//extract the NAME of each location into a new array

		return json_encode($locations);

	}

}
