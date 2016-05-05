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

	public function E911_get_erl_names(){
		$erls = \ohtarr\Utility::E911_get_erls();						//get E911 erls via netman API to E911 gateway
		//extract the NAME of each erl into a new array
		foreach($erls as $erl){											//go through all e911 erls
			$allerls[] = $erl[erl_id];
		}
		natcasesort($allerls);
		$allerls = array_values($allerls);
		return $allerls;
	}

	public function E911_get_default_erl_names(){
		$erls = \ohtarr\Utility::E911_get_erl_names();

		//extract the NAME of each erl into a new array
		foreach($erls as $erlname){											//go through all e911 erls
			$exploded = explode("_", $erlname);									//we break apart the erl using the "_" as a delimiter
			$allerls[]=$exploded[0];											//add only the sitecodes of all erls to new array $sites
		}
		$sites = array_unique($allerls);
		natcasesort($sites);
		return $sites;
	}

	public function E911_erls_to_Remove(){
		
		$SNOW = new \ohtarr\ServiceNowRestClient;						//initialize a new snow rest api call
		$snowlocs = $SNOW->SnowGetValidAddresses();
		//print "SNOW LOCS:\n";
		//print_r($snowlocs);
		$e911erls = \ohtarr\Utility::E911_get_default_erl_names();
		//print "E911 DEFAULT ERLS:\n";
		//print_r($e911erls);
		$deldefaults = array_values(array_diff($e911erls, $snowlocs));		//compare $sites to snow $locname to determine default erls to delete
		//print "DEL DEFAULT ERLS:\n";
		//print_r($deldefaults);
		$alle911erls = \ohtarr\Utility::E911_get_erl_names();
		//print "ALL E911 ERLS:\n";
		//print_r($alle911erls);

		foreach($deldefaults as $defsite){
			foreach($alle911erls as $site){
				//print "SITE: " . $site . " DEFAULT SITE: " . $defsite . " SUBSTRING: " . substr($site, 0, 8) . "\n";
				if(substr($site, 0, 8) == $defsite){
					$dellall[] = $site;
				}
			}
		}
		//print "DEL ALL:\n";
		//print_r($dellall);
		return $dellall;
	}

	public function E911_Remove_erls(){
		print "REMOVING ERLS! \n";
		$delerls = \ohtarr\Utility::E911_erls_to_Remove();
		
		$EGW = new \EmergencyGateway\EGW(	E911_ERL_SOAP_URL,
											E911_ERL_SOAP_WSDL,
											E911_SOAP_USER,
											E911_SOAP_PASS);

		foreach($delerls as $erl){
			print "name: " . $erl . "\n";
			try{
				$RESULT = $EGW->deleteERL($erl);
				
			} catch (\Exception $e) {
//				print ("SoapError: {$E->getMessage()}");
				print "\n CATCH! \n";
			}
			print "RESULT:" . $RESULT . "\n";
		}
	}

	public function E911_erls_to_Add(){
		
		$SNOW = new \ohtarr\ServiceNowRestClient;						//initialize a new snow rest api call
		$snowlocs = $SNOW->SnowGetValidAddresses();

		$E911erls = \ohtarr\Utility::E911_get_default_erl_names();

		return array_values(array_diff($snowlocs,$E911erls));
	}

	public function E911_Add_erls(){
		$adderls = \ohtarr\Utility::E911_erls_to_Add();
		$EGW = new \EmergencyGateway\EGW(	E911_ERL_SOAP_URL,
											E911_ERL_SOAP_WSDL,
											E911_SOAP_USER,
											E911_SOAP_PASS);

		$SNOW = new \ohtarr\ServiceNowRestClient;

		foreach($adderls as $site){
			//print "PRINTING SITE: \n";
			//print_r($site);
			//print "\nPRINTING OBJECT: \n";
			$addr = (array) $SNOW->SnowGetSite($site);
			//print_r($addr);
			$ADDRESS = \EmergencyGateway\Address::fromString($addr[street], $addr[city], $addr[state], $addr[country], $addr[zip], $addr[name]);
			$ADDRESS->LOC = $addr[u_street_2];
			//print_r((array) $ADDRESS);
			//print "\n";

			try{
				$RESULT = $EGW->addERL($addr[name], (array) $ADDRESS);
			} catch (\Exception $e) {
				print $e;
				print "***************************************************************************CATCH!";
			}
			//die();
		}
	}

	public function E911_get_switches(){
		$URI = BASEURL . "/api/911-get-switches.php";
		//print $URI;
		return \Httpful\Request::get($URI)											//Build a PUT request...
								->expectsJson()										//we expect JSON back from the api
//								->authenticateWith(LDAP_USER, LDAP_PASS)			//authenticate with basic auth...
								->parseWith("\\metaclassing\\Utility::decodeJson")	//Parse and convert to an array with our own parser, rather than the default httpful parser
								->send()											//send the request.
								->body;											
	}

	public function E911_get_switch_names(){
		$e911switches = \ohtarr\Utility::E911_get_switches();

		foreach($e911switches as $e911switch){
			$e911switchnames[$e911switch['switch_id']] = $e911switch['switch_description'];
		}
		natcasesort($e911switchnames);
		return $e911switchnames;
	}

	public function Netman_get_switch_names(){

		$SEARCH = array(    // Search for all cisco network devices
                "category"              =>	"Management",
                "type"                  =>	"Device_Network_Cisco",
//				"custom"				=>	"%SWD01%",
                );

		// Do the actual search
		$RESULTS = \Information::search($SEARCH);
	
		//print_R($RESULTS);

		foreach($RESULTS as $OBJECTID){
			$DEVICE = \Information::retrieve($OBJECTID);
			
			$reg = "/^.*[sS][wW][aAcCdDpP].*$/";
			if (preg_match($reg,$DEVICE->data['name'], $hits)){
				$switches[$DEVICE->data['id']] = $DEVICE->data['name'];
			}
		}
		natcasesort($switches);
		return $switches;

	}

	public function E911_Switches_to_Add(){
		$netmanswitches = \ohtarr\Utility::Netman_get_switch_names();
		$e911switches = \ohtarr\Utility::E911_get_switch_names();
		return array_diff($netmanswitches,$e911switches);
	}

	public function E911_Switches_to_Remove(){
		$netmanswitches = \ohtarr\Utility::Netman_get_switch_names();
		$e911switches = \ohtarr\Utility::E911_get_switch_names();
		return array_diff($e911switches,$netmanswitches);

	}

	public function E911_Add_switches(){
		//print "ADDING SWITCHES \n";
		$addswitches = \ohtarr\Utility::E911_Switches_to_Add();
		//\metaclassing\Utility::dumper($addswitches);
		$EGW = new \EmergencyGateway\EGW(	E911_SWITCH_SOAP_URL,
											E911_SWITCH_SOAP_WSDL,
											E911_SOAP_USER,
											E911_SOAP_PASS);

		foreach($addswitches as $OBJECTID => $NAME){
			$DEVICE = \Information::retrieve($OBJECTID);
			
			$ADD_SWITCH = array("switch_ip"				=>	$DEVICE->data['ip'],
								"switch_vendor"			=>	"Cisco",
								"switch_erl"			=>	strtoupper(substr($NAME, 0, 8)),
								"switch_description"	=>	$NAME,
			);

			print_r($ADD_SWITCH);

			try {
				$RESULT = $EGW->add_switch($ADD_SWITCH);
			} catch ( \SoapFault $E ) {
				die("SOAP Error: {$E}". $HTML->footer() );
			} 

			unset($DEVICE);
			die("Croak!");
		}



	}

	public function Sitecode_from_Name($NAME){
		if ($NAME){
			return strtoupper(substr($NAME, 0, 8));
		} else {
			die("No Input!");
		}
	}

	public function Type_from_Name($NAME){
	if ($NAME){
			return strtoupper(substr($NAME, 8, 3));
		} else {
			die("No Input!");
		}
	}


}
