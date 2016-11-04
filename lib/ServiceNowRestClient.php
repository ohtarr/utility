<?php
/**
 * lib/ServiceNowRestClient.php.
 *
 * This library contains functions to hit service-nows GET API to retrieve information from specific tables.
 *
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

class ServiceNowRestClient
{
	public $table;

	public function __construct($Options)
	{

	}

	//SNOW TABLE API - GET - retrieve data from a SNOW table.
	public function SnowTableApiGet($TABLE, $PARAMS)
	{
		//convert associative array $PARAMS into a non-associative array $CONSTRUCT, ready to implode!
		foreach($PARAMS as $KEY => $VALUE)	
		{
			if ($VALUE){
				$CONSTRUCT[] = $KEY . "=" . $VALUE;									//If there is a value, insert $KEY=$VALUE
			}
			else{
				$CONSTRUCT[] = $KEY;												//Otherwise just insert $KEY
			}
		}
		//Implode & between each element of $CONSTRUCT.
		$PARMS = implode( "&" , $CONSTRUCT );
		//Build the URL to GET including our parameters from $PARMS
		$URI = API_SNOW_URL . "/api/now/table/" . $TABLE . "?" . $PARMS;
		//build the http query and execute using httpful, return the data
		$raw = \Httpful\Request::get($URI)											//Build a GET request...
								->expectsJson()										//we expect JSON back from the api
								->authenticateWith(LDAP_USER, LDAP_PASS)			//authenticate with basic auth...
								->parseWith("\\Metaclassing\\Utility::decodeJson")	//Parse and convert to an array with our own parser, rather than the default httpful parser
								->send()											//send the request.
								->body;												//include the body
	
		foreach($raw[result] as $site){
			$new[] = $site;
		}
		return $new;
	}

	//Feed this function a $TABLE name and it will give you an array with all the sysIDs in that table.
	public function SnowGetTableIds($TABLE)
	{
		//array of parameters to send
		$PARAMS = array(
						"active"				=> "true",
						"sysparm_fields"		=> "sys_id",
						);
		//Execute the main SnowTableApiGet with our parameters
		return $this->SnowTableApiGet($TABLE, $PARAMS);
	}
	//Feed this function a table name ($TABLE) and a sys_id ($RECORD) and it will return all the details of that record.
	public function SnowGetRecord($TABLE, $RECORD)
	{
		//array of parameters to send
		$PARAMS = array(
						"sys_id"		=> $RECORD,
						);
		//Execute the main SnowTableApiGet with our parameters
		return $this->SnowTableApiGet($TABLE, $PARAMS);
	}
	//Feed this function with the name of a table ($TABLE) and it will return all details of all records in the table. (active only!)
	public function SnowGetAllRecords($TABLE)
	{
		$RECORDS = array();												//declare array
		$INCREMENT = 10000;												//chunk size of query, snow allows max of 10000
		$OFFSET = 0;													//start with the first chunk of records!
		do {
//			print $OFFSET . "\n";
			unset($RESULTS);
			//array of parameters to send
			$PARAMS = array(
							"active"		=> "true",					//only give us active records
							"sysparm_offset"	=> $OFFSET,				//Skip the first $OFFSET number of records
							"sysparm_limit"		=> $INCREMENT,			//Only provide us with $INCREMENT records
							);
			//Execute the main SnowTableApiGet with our parameters
			$RESULTS = $this->SnowTableApiGet($TABLE, $PARAMS);
//			$RECORDS = array_merge($RECORDS,$RESULTS[result]);
			foreach ($RESULTS[result] as $v){
				array_push($RECORDS, $v);
			}
			//print($RECORDS);
			$OFFSET += $INCREMENT;										//next chunk!
		} while ($RESULTS[result]);
		//print($RECORDS);
		return $RECORDS;
	}
	//Feed this function the name ($SITENAME) of a jobsite and it will return all details of that location.
	public function SnowGetSite($SITENAME)
	{
		$TABLE = "cmn_location";
		//array of parameters to send
		$PARAMS = array(
						"name"		=> $SITENAME,
						);
		//Execute the main SnowTableApiGet with our parameters, return the results
		$raw = $this->SnowTableApiGet($TABLE, $PARAMS);
		return $raw[0];
	}

	public function SnowGetValidAddresses(){

		$PARAMS = array(
							"u_active"                	=>	"true",
							"u_e911_validated"			=>	"true",
							"sysparm_fields"        	=>	"name",
		);
		$locations = $this-> SnowTableApiGet("cmn_location", $PARAMS);
		foreach($locations as $location){
			$locname[]=$location[name];
		}
		sort($locname);
		return $locname;
	}

}

