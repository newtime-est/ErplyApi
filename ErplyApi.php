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
			$message = "Erply Api error: ". $response->status->errorCode;
			if (isset($response->status->errorField)) {
				$message .= ': '.$response->status->errorField;
			}
			throw new Exception($message, $response->status->errorCode);
		}
		return $response;
	}
	public function __call($name,$parameters=array())
	{
		return $this->sendRequest($name,call_user_func_array('array_merge',$parameters));
	}
	public function bulkRequest()
	{
		$return = json_decode(call_user_func_array(array('parent','bulkRequest'),func_get_args()));
		if ($return->status->errorCode == 0) {
			$requests = array();
			usort($return->requests, function($a, $b) {
				if (($a->status->requestID === null) == ($b->status->requestID === null)) {
					return 0;
				}
				if ($a->status->requestID === null) {
					return 1;
				}
				return -1;
			});
			foreach ($return->requests as $request) {
				if (empty($request->status->requestID)) {
					$requests[] = $request;
				} else {
					$requests[$request->status->requestID] = $request;
				}
			}
			$return->requests = $requests;
		}
		return $return;
	}

	protected function getSessionKey() 
	{	
		if($this->isCLI()){
			if($this->sessionKey==null || $this->sessionKeyExpires=== null || $this->sessionKeyExpires < time()){
				$this->downloadSessionKey();
			}
		}else{			
			if(!isset($_SESSION))
				session_start();

			if(
				!isset($_SESSION['EAPIsessionKey']) ||
				!isset($_SESSION['EAPIsessionKeyExpires']) || 
				$_SESSION['EAPIsessionKey']===null || 
				$_SESSION['EAPIsessionKeyExpires']=== null || 
				$_SESSION['EAPIsessionKeyExpires'] < time()
			){
				$this->downloadSessionKey();
			}else{
				$this->sessionKey=$_SESSION['EAPIsessionKey'];
				$this->sessionKeyExpires=$_SESSION['EAPIsessionKeyExpires'];
			}

			$_SESSION['EAPIsessionKey']=$this->sessionKey;
			$_SESSION['EAPIsessionKeyExpires']=$this->sessionKeyExpires;
		}
		
		return $this->sessionKey;
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
