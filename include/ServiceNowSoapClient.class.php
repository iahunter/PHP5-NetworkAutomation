<?php
/**
*	Class: ServiceNowSoapClient
*	Description: a wrapper class for php SoapClient to ease the usage of service now soap web services
*   http://community.servicenow.com/forum/4048
*	Date: 07.08.2010
*	Author: Chris Sherman
*	License: none, enjoy it :-)
*
*/
class ServiceNowSoapClient{
	private $client, $instance, $login, $password;
 
	/**
	*	location must be to a WSDL service location within service-now.com 
	*	ie. <a href="https://demo.service-now.com/cmdb_ci_service.do?WSDL<br />
" title="https://demo.service-now.com/cmdb_ci_service.do?WSDL<br />
">https://demo.service-now.com/cmdb_ci_service.do?WSDL<br />
</a>	*	login and password must be a service-now.com user with 
	*	SOAP access added to its account 
	*/
	public function __construct($location, $login, $password){
		$this->setLogin($login);
		$this->setPassword($password);
		$this->location = $location;
		$this->client = new soapclient($this->location,$this->getAuthorization());
	}


	/**
	*	Function: set login
	*	Purpose: Helper function you will not need as of now. 
	*			sets $this->login = $login
	*	param: 	string, the login for this service now soap web service 
	*	return: void 
	*/
	public function setLogin($login){
		$this->login = $login; 
	}
	/**
	*	Function: setPassword($password)
	*	Purpose: 	Helper function you will not need as of now. 
	*			sets $this->password = $password 
	*	param: 	string, the password for this soap web service 
	*	return: void 
	*/
	public function setPassword($password){
		$this->password = $password; 
	}


	//sets a new end point for soap client 
	//SoapClient::__setLocation throws error on the call and
	//for time being I will just construct a new soap client 
	/**
	*	Function: setLocation($location)
	*	Purpose: 	Updates the location to the WSDL SOAP Service 
	*			Now web service
	*			As of now method constructs a new SoapClient with updated
	*			location as I was having problems with using 
	*			SoapClient->__setLocation
	*			I attribute this to the WSDL but thats for another day.  
	*	param: 	string, the location of the new web service 
	*	return: void
	*/
	public function setLocation($location){
		//$this->client->__setLocation($location); 
		$this->location = $location; 
		$this->client = new soapclient($this->location,$this->getAuthorization());
	} 	
	/**
	*	Function: getAuthorization() 
	*	Purpose: 	Helper function you will not need as of now. 
	*			Used when constructor constructs a new soap client or
	*			when set location is called.  Creates the array to authenticate
	*			with the web service. 
	*	param: 	null
	*	return: associative array, this.array('login' -> $this->login, 
	*	'password'->$this->password') 
	*/
	public function getAuthorization(){
		return array('login'=> $this->login, 'password' => $this->password); 
	}

	//auxilary function to merely dump the list of available functions
	//specific this.client.currentLocation 
	public function listAvailableFunctions(){
		$result = $this->client->__getFunctions(); 
		foreach($result as $key=>$value){
			echo $key.' -> '.$value.'<br/>';
		}
	}

	/**
	*	Function: getRecords($query)
	*	Purpose: 	Implements the ServiceNow Soap GetRecords method.  The idea is that
	*			we accept an array containing the query and always return back an array.
	*			This method is what makes this class so great.  Service Now Server
	*			can return to to soap client multiple types, 
	*				-either an array of record objects when multiple 
	*				objects are in the result set
	*				-a record object when one item is in result set 
	*				-a record object which is empty when no results are returned 
	*			Instead of handling that mess with all soap queries this function 
	*			handles it for you by determining if the result is an array, or
	*			or not and it will always return an array.  The array can be empty,
	*			for no results, or have some number of recorc objects in it. :-) 
	*	param: 	array, the query for soap web service 
	*	return: array, array containing record objects or an empty array for no results 
	*/
	public function getRecords($query){
		$result = $this->client->__soapCall('getRecords',array('GetRecords' => $query));
		$returnResult = array(); 
		if(isset($result)){
			if(isset($result->getRecordsResult)){	
				if(is_array($result->getRecordsResult)){
					$returnResult = $result->getRecordsResult;
				}else if(isset($result->getRecordsResult->name)){
					$returnResult = array($result->getRecordsResult);
				}
			}
		}
		return $returnResult; 
	}

	/**
	*	function insert($query) 
	*	inserts a record into service now. 
	*	
	*	@param array associative field -> value pairs 
	*/
	public function insert($query){
		$result = $this->client->__soapCall('insert',array('insert' => $query));
	}

	/**
	*	function: update($query) 
	*	Updates a specific record in service now, the record is sepcific
	*	to constructors location argument. 
	*
	*	@param array - associative array of field -> value pairs 
	*	@requires adhering to WSDL of specific location with at least 
	*	the min requirements.
	*/
	public function update($query){
		$result = $this->client->__soapCall('update',array('update' => $query));
	}

	/**
	*	Function: get($sys_id)
	*	Gets a specific record by its system id.  Specific to a given 
	*	WSDL defined via constructor.  IE. <a href="http://service-now.com/incident.do?WSDL" title="http://service-now.com/incident.do?WSDL">http://service-now.com/incident.do?WSDL</a> 
	*	specified as $location param to cnostructor then making call to $this::get($sys_id)
	*	returns an incident with given sys id.
	*	@param: system id 
	*	@todo: 
	*/
	public function get($sys_id){
		$result = $this->client->__soapCall('get', array('get'=> array('sys_id'=>$sys_id)));
		return $result;
	}

}

?>
