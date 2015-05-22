<?php 
/**
* 
*/
class ErplyApi extends EApi
{
	private $sessionKey;
	private $sessionKeyExpires;

	public function sendRequest($request, $parameters = array())
	{
		$response=json_decode(parent::sendRequest($request, $parameters));
		if($response && $response->status && $response->status->responseStatus=='error'){
			throw new Exception("Erply Api error", $response->status->errorCode);
		}
		return $response;
	}
	public function __call($name,$parameters=array())
	{
		return $this->sendRequest($name,call_user_func_array('array_merge',$parameters));
	}
	public function bulkRequest()
	{
		return json_decode(call_user_func_array(array('parent','bulkRequest'),func_get_args()));
	}

	protected function getSessionKey() 
	{	
		if($this->isCLI()){
			if($this->sessionKey==null || $this->sessionKeyExpires=== null || $this->sessionKeyExpires < time()){
				$this->downloadSessionKey();
			}
			return $this->sessionKey;
		}else{
			return parent::getSessionKey();
		}
	}
	public function downloadSessionKey()
	{
		$result = $this->verifyUser(array("username" => $this->username, "password" => $this->password));
		if(!isset($result->records[0]->sessionKey)) {
			$e = new Exception('Verify user failure', self::VERIFY_USER_FAILURE);
			$e->response = $result;
			throw $e;
		}
		$this->sessionKey=$result->records[0]->sessionKey;
		$this->sessionKeyExpires=time() + $result->records[0]->sessionLength - 30;
	}
	public function isCLI()
	{
		return (!isset($_SERVER['SERVER_SOFTWARE']) && (php_sapi_name() == 'cli' || (is_numeric($_SERVER['argc']) && $_SERVER['argc'] > 0)));
	}
}
