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

	//get ERLs from mysql backdoor.
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
/*
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
/**/
	//returns an array of all configured ERLs (ERLID => ERLNAME) from the E911 gateway appliance
	public function E911_get_erl_names(){
		$erls = \ohtarr\Utility::E911_get_erls();						//get E911 erls via netman API to E911 gateway
		//extract the NAME of each erl into a new array
		foreach($erls as $erl){											//go through all e911 erls
			$allerls[$erl[location_id]] = $erl[erl_id];
		}
		natcasesort($allerls);
		//$allerls = array_values($allerls);
		return $allerls;
	}

	//returns an array of default ERLs (ERLID => ERLNAME) from the E911 gateway appliance
	public function E911_get_default_erl_names(){
		$erls = \ohtarr\Utility::E911_get_erl_names();

		//extract the NAME of each erl into a new array
		foreach($erls as $id => $erlname){											//go through all e911 erls
			$exploded = explode("_", $erlname);									//we break apart the erl using the "_" as a delimiter
			$allerls[$id]=$exploded[0];											//add only the sitecodes of all erls to new array $sites
		}
		natcasesort($allerls);
		$sites = array_unique($allerls);
		return $sites;
	}

	//Compares SNOW and E911 gateway to determine which ERLs need to be removed from the E911 gateway.
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

	//Removes ERLs that are ready to be removed
	public function E911_Remove_erls(){
		if($delerls = \ohtarr\Utility::E911_erls_to_Remove()){
						
			$EGW = new \EmergencyGateway\EGW(	E911_ERL_SOAP_URL,
												E911_ERL_SOAP_WSDL,
												E911_SOAP_USER,
												E911_SOAP_PASS);

			foreach($delerls as $erl){
				print $erl;
				try{
					$RESULT = $EGW->deleteERL($erl);
				} catch (\Exception $e) {
					print $e;
					print "\n CATCH! \n";
				}
			}
		}
	}

	//Compares SNOW with E911 Gateway to determine which DEFAULT ERLs need to be added.
	public function E911_erls_to_Add(){
		
		$SNOW = new \ohtarr\ServiceNowRestClient;						//initialize a new snow rest api call
		$snowlocs = $SNOW->SnowGetValidAddresses();

		$E911erls = \ohtarr\Utility::E911_get_default_erl_names();

		return array_values(array_diff($snowlocs,$E911erls));
	}

	//add any missing DEFAULT ERLs
	public function E911_Add_erls(){
		if ($adderls = \ohtarr\Utility::E911_erls_to_Add()){
			$EGW = new \EmergencyGateway\EGW(	E911_ERL_SOAP_URL,
												E911_ERL_SOAP_WSDL,
												E911_SOAP_USER,
												E911_SOAP_PASS);

			$SNOW = new \ohtarr\ServiceNowRestClient;

			foreach($adderls as $site){
				$addr = (array) $SNOW->SnowGetSite($site);
				$ADDRESS = \EmergencyGateway\Address::fromString($addr[street], $addr[city], $addr[state], $addr[country], $addr[zip], $addr[name]);
				$ADDRESS->LOC = $addr[u_street_2];
				try{
					$RESULT = $EGW->addERL($addr[name], (array) $ADDRESS);
				} catch (\Exception $e) {
					print $e;
					print "\n***************************************************************************CATCH!\n";
				}
			}
		}
	}

	//return an array of switch objects from the E911 Gateway
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
	
	//return an array of switch names (IPADDRESS => NAME)
	public function E911_get_switch_names(){
		$e911switches = \ohtarr\Utility::E911_get_switches();

		foreach($e911switches as $e911switch){
			$e911switchnames[$e911switch['switch_ip']] = $e911switch['switch_description'];
		}
		natcasesort($e911switchnames);
		return $e911switchnames;
	}

	//query netman to retrieve an array of all switch names (ID => NAME)
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
			
			//$reg = "/^.*(sw[acdpi]|SW[ACDPI])[0-9]{2}.*$/";
			$reg = "/^\D{5}\S{3}.*(sw[acdpi]|SW[ACDPI])[0-9]*$/";
			if (preg_match($reg,$DEVICE->data['name'], $hits)){
				$switches[$DEVICE->data['id']] = $DEVICE->data['name'];
			}
		}
		natcasesort($switches);
		return $switches;

	}

/*
	public function E911_Switches_to_Add(){
		$netmanswitches = \ohtarr\Utility::Netman_get_switch_names();
		$deferls = \ohtarr\Utility::E911_get_default_erl_names();
		foreach($netmanswitches as $id => $switch){

			foreach($deferls as $erl){
				if(strtoupper(substr($switch,0,8)) == $erl){
					$addswitches[$id]=$switch;
				}
			}
		}
		natcasesort($addswitches);

		$e911switches = \ohtarr\Utility::E911_get_switch_names();
		return array_diff($addswitches,$e911switches);
	}
/**/
	
	//returns true if $switchname exists in $e911switches array.
	public function E911_switch_exists(array $e911switches, $switchname)
	{
		//$e911switches = \ohtarr\Utility::E911_get_switch_names();
		foreach($e911switches as $switch){
			if ($switchname == $switch[switch_description]){
				return true;
			}
		}
		return false;
	}
	
	//returns true if $erlname exists in $e911erls array.
	public function E911_erl_exists(array $e911erls, $erlname)
	{
		//$e911erls = \ohtarr\Utility::E911_get_erl_names();
		foreach($e911erls as $erl){
			if ($erlname == $erl){
				return true;
			}
		}
		return false;
	}

/*
	public function E911_erl_matches($switchname)
	{
		$netmanswitches = \ohtarr\Utility::Netman_get_switch_names();
		$e911switches = \ohtarr\Utility::E911_get_switches();
		$erls = \ohtarr\Utility::E911_get_erl_names();	//get ERLS out of E911 appliance

		foreach($netmanswitches as $id => $nmswitch){
			if ($switchname == $nmswitch){
				$nmid = $id;
				break;
			}
		}
		
		$DEVICE = \Information::retrieve($nmid);		//retrieve the switch object from netman information
		$NMSNMP = $DEVICE->get_snmp_location();	//retrieve the snmp location from switch object

		foreach($e911switches as $e911switch){
			if ($e911switch[switch_description] == $switchname){
				$e911erlid = $e911switch[switch_default_erl_id];
				break;
			}
		}
		foreach($erls as $erlid => $erlname){
			if($e911erlid == $erlid){
				$e911erlname = $erlname;
				break;
			}
		}

		if ($NMSNMP[erl] == $e911erlname){
			return true;
		} else {
			return false;
		}
	}
/**/

	//returns true if ERL configured for $switchname in NETMAN($netmanswitches) AND E911GW($e911switches) matches. 
	public function E911_erl_matches(array $netmanswitches, array $e911switches, array $erls, $switchname)
	{
		//print_r($netmanswitches);
		//print_r($e911switches);
		//print_r($erls);
		foreach($netmanswitches as $id => $nmswitch){
			if ($switchname == $nmswitch){
				$nmid = $id;
				//print "NETMAN SWITCH ID : " . $nmid . "\n";
				break;
			}
		}
		$DEVICE = \Information::retrieve($nmid);		//retrieve the switch object from netman information
		$NMSNMP = $DEVICE->get_snmp_location();	//retrieve the snmp location from switch object
		//print "SNMP LOCATION ERL : " . $NMSNMP[erl] . "\n";
		foreach($e911switches as $e911switch){
			//print "E911 Switch : " . $e911switch[switch_description] . "\n";
			//print "Queried Switch : " . $switchname . "\n";
			if ($e911switch[switch_description] == $switchname){
				$e911erlid = $e911switch[switch_default_erl_id];
				//print "E911 SWITCH ERL ID : " . $e911erlid . "\n";
				break;
			}
		}
		foreach($erls as $erlid => $erlname){
			if($e911erlid == $erlid){
				$e911erlname = $erlname;
				//print "ERL : " . $e911erlname . "\n";
				break;
			}
		}

		if ($NMSNMP[erl] == $e911erlname){
			return true;
		} else {
			return false;
		}
	}

	//returns an array of switches that need to be added to E911 GW.
	//this includes checking if the switch exists, if the configured ERL exists, and if the ERL matches.
	public function E911_Switches_to_Add()
	{
		$netmanswitches = \ohtarr\Utility::Netman_get_switch_names(); //Get switches out of netman
		$e911switches = \ohtarr\Utility::E911_get_switches();  //Get switches out of E911 appliance
		$erls = \ohtarr\Utility::E911_get_erl_names();	//get ERLS out of E911 appliance

		foreach($netmanswitches as $id => $switch){		//Loop through each switch from netman
			$DEVICE = \Information::retrieve($id);		//retrieve the switch object from netman information
			$SNMPLOC = $DEVICE->get_snmp_location();	//retrieve the snmp location from switch object
			//print $switch . "\n";
			if (\ohtarr\Utility::E911_switch_exists($e911switches, $switch)){
				//print "switch exists!\n";
				if (\ohtarr\Utility::E911_erl_matches($netmanswitches, $e911switches, $erls, $switch)){
					//print "erl matches!!\n";
					continue;
				} else {
					//print "ADD SWITCH!\n";
					$addswitches[$id] = $switch;
				}
			} else {
				if (\ohtarr\Utility::E911_erl_exists($erls, $SNMPLOC[erl])){
					//print "erl exists!\n";
					//print "ADDSWITCH!\n";
					$addswitches[$id] = $switch;
				}
			}
			unset($DEVICE);
		}
		return $addswitches;
	}

/*
	public function E911_Switches_to_Add2()
	{
		$netmanswitches = \ohtarr\Utility::Netman_get_switch_names();
		$e911switches = \ohtarr\Utility::E911_get_switches();
		$erls = \ohtarr\Utility::E911_get_erl_names();	//get ERLS out of E911 appliance

	}

	//builds an array of switches that need to be ADDED or MODIFIED in the E911 appliance.
	public function E911_Switches_to_Add3(){
		$netmanswitches = \ohtarr\Utility::Netman_get_switch_names(); //Get switches out of netman
		$e911switches = \ohtarr\Utility::E911_get_switches();	//get switches out of E911 appliance
		$erls = \ohtarr\Utility::E911_get_erl_names();	//get ERLS out of E911 appliance
			//print_r($erls);
			foreach($netmanswitches as $id => $switch){		//Loop through each switch from netman
				$exists = 0;
				$erlexists = 0;
				print "NETMAN SWITCH: " . $switch . "\n";
				$DEVICE = \Information::retrieve($id);		//retrieve the switch object from netman information
				$SNMPLOC = $DEVICE->get_snmp_location();	//retrieve the snmp location from switch object
				print_r($SNMPLOC);

				foreach($erls as $erl){
					if ($erl == $SNMPLOC[erl]){			//If erl exists, add switch to array and break foreach loop.
						//print "ERL = " . $erl . "\n";
						//print "SWITCHERL = " . $SNMPLOC[erl] . "\n";
						print "ERL EXISTS! \n";
						print "Modify switch! \n";
						//$addswitches[$id] = $switch;  
						$erlexists = 1;
						break;
					}	
				}

				foreach($e911switches as $e911switch){		//Loop through each E911 switch
					//print_r($e911switch);
					//print "E911 SWITCH: " . $e911switch[switch_description] . "\n";
					//print "E911 SWITCH ERL ID: " . $e911switch[switch_default_erl_id] . "\n";
					//print "E911 ERL NAME: " . $erls[$e911switch[switch_default_erl_id]] . "\n";
					if($switch == $e911switch[switch_description]){		//See if we have a match
						//switch exists already!
						$exists = 1;		//If the switch already exists in the E911 appliance, flag $exists.
						print "EXISTS! \n";
						if($SNMPLOC[erl] !== $erls[$e911switch[switch_default_erl_id]]){	//Now check if the ERL in SNMP-SERVER LOCATION matches the ERL assigned in the E911 appliance 
							//ERL does not match, need to modify!
							//print "SWITCH ERL: " . $SNMPLOC[erl];
							//print "E911 ERL: " . $erls[$e911switch[location_id]] . "\n";

							
						} else {
							print "ERL MATCHES! \n";
						}
						break;  //break out of foreach since we found a match!
					} 
				}
				unset($DEVICE);		//clear memory
				if ($exists == 0){		//If the switch doesn't exist at all, go ahead and add it to our array.
					print "Switch doesn't exist, need to add it! \n";
					$addswitches[$id] = $switch;
				}
			}
		natcasesort($addswitches);		//sort the resulting array alphabetically
		$addswitches = array_unique($addswitches);		//remove any duplicate switches
		return $addswitches;		//return the array!
	}
/**/

	public function E911_Switches_to_Remove(){
		$netmanswitches = \ohtarr\Utility::Netman_get_switch_names();
		$e911switches = \ohtarr\Utility::E911_get_switch_names();
		return array_diff($e911switches,$netmanswitches);

	}

	public function E911_Add_switches(){
		if($addswitches = \ohtarr\Utility::E911_Switches_to_Add()){
			$EGW = new \EmergencyGateway\EGW(	E911_SWITCH_SOAP_URL,
												E911_SWITCH_SOAP_WSDL,
												E911_SOAP_USER,
												E911_SOAP_PASS);

			foreach($addswitches as $OBJECTID => $NAME){
				$DEVICE = \Information::retrieve($OBJECTID);
				$SNMP = $DEVICE->get_snmp_location();
				if ($SNMP[erl]){
					$ADD_SWITCH = array("switch_ip"				=>	$DEVICE->data['ip'],
										"switch_vendor"			=>	"Cisco",
										"switch_erl"			=>	$SNMP[erl],
										"switch_description"	=>	$NAME,
					);

					print_r($ADD_SWITCH);
					
					try {
						$RESULT = $EGW->delete_switch($DEVICE->data['ip']);
					} catch ( \SoapFault $E ){

					}
					try {
						$RESULT = $EGW->add_switch($ADD_SWITCH);
					} catch ( \SoapFault $E ) {
						die("SOAP Error: {$E}". $HTML->footer() );
					} 
				}
				unset($DEVICE);
				//die("Croak!");
			}
		}
	}

	public function E911_Remove_switches(){
		if($delswitches = \ohtarr\Utility::E911_Switches_to_Remove()){
			$EGW = new \EmergencyGateway\EGW(	E911_SWITCH_SOAP_URL,
												E911_SWITCH_SOAP_WSDL,
												E911_SOAP_USER,
												E911_SOAP_PASS);

			foreach($delswitches as $OBJECTIP => $NAME){
				try {
					$RESULT = $EGW->delete_switch($OBJECTIP);
				} catch ( \SoapFault $E ) {
					die("SOAP Error: {$E}". $HTML->footer() );
				} 
				//die("Croak!");
			}
		}
	}

	public function E911_Remove_ALL_switches(){
		if($delswitches = \ohtarr\Utility::E911_get_switches()){
			$EGW = new \EmergencyGateway\EGW(	E911_SWITCH_SOAP_URL,
												E911_SWITCH_SOAP_WSDL,
												E911_SOAP_USER,
												E911_SOAP_PASS);

			foreach($delswitches as $SWITCH){
				try {
					$RESULT = $EGW->delete_switch($SWITCH[switch_ip]);
				} catch ( \SoapFault $E ) {
					die("SOAP Error: {$E}". $HTML->footer() );
				} 
				//die("Croak!");
			}
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
