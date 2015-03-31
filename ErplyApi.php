<?php 
/**
* 
*/
class ErplyApi extends EApi
{
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
		//test for session
		if(!isset($_SESSION)){
			session_start();
		}
		//if no session key or key expired, then obtain it
		if(
			!isset($_SESSION['EAPISessionKey'][$this->clientCode][$this->username]) ||
			!isset($_SESSION['EAPISessionKeyExpires'][$this->clientCode][$this->username]) ||
			$_SESSION['EAPISessionKeyExpires'][$this->clientCode][$this->username] < time()
		) {
			//make request
			$result = $this->verifyUser(array("username" => $this->username, "password" => $this->password));

			//check failure
			if(!isset($result->records[0]->sessionKey)) {
				unset($_SESSION['EAPISessionKey'][$this->clientCode][$this->username]);
				unset($_SESSION['EAPISessionKeyExpires'][$this->clientCode][$this->username]);
				
				$e = new Exception('Verify user failure', self::VERIFY_USER_FAILURE);
				$e->response = $result;
				throw $e;
			}
			
			//cache the key in PHP session
			$_SESSION['EAPISessionKey'][$this->clientCode][$this->username] = $result->records[0]->sessionKey;
			$_SESSION['EAPISessionKeyExpires'][$this->clientCode][$this->username] = time() + $result->records[0]->sessionLength - 30;
			
		}

		//return cached key
		return $_SESSION['EAPISessionKey'][$this->clientCode][$this->username];
	}
}
