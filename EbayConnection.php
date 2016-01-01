<?php
class EbayConnection {

	private $auth = null;
	private $error_msg = null;
	private $error_code = null;

	function __construct($auth) {
		$this->auth = $auth;
	}

	/**
	 * This function ends an Ebay listing.  Returns true if successful.
	 *
	 * @param $ebay_id String Ebay Item Id
	 */
	public function endItem($ebay_id) {
		$endpoint = "https://api.ebay.com/ws/api.dll";

		$xmlbody = '
<?xml version="1.0" encoding="utf-8"?>
<EndItemsRequest xmlns="urn:ebay:apis:eBLBaseComponents">
	<RequesterCredentials>
		<eBayAuthToken>' . $this->auth . '</eBayAuthToken>
	</RequesterCredentials>

	<!-- Call-specific Input Fields -->
	<EndItemRequestContainer>
		<EndingReason>NotAvailable</EndingReason>
		<ItemID>' . $ebay_id . '</ItemID>
		<MessageID>' . $ebay_id . '</MessageID>
	</EndItemRequestContainer>
	<!-- ... more EndItemRequestContainer nodes allowed here ... -->
	<!-- Standard Input Fields -->
	<ErrorLanguage>en_US</ErrorLanguage>
	<WarningLevel>High</WarningLevel>
</EndItemsRequest>
';

		$headers = array(
//Regulates versioning of the XML interface for the API
			'X-EBAY-API-COMPATIBILITY-LEVEL: 861',
//the name of the call we are requesting
			'X-EBAY-API-CALL-NAME: EndItems',
//SiteID must also be set in the Request's XML
//SiteID = 0  (US) - UK = 3, Canada = 2, Australia = 15, ....
//SiteID Indicates the eBay site to associate the call with
			'X-EBAY-API-SITEID: 0 ',
		);


//initialise a CURL session
		$connection = curl_init();
//set the server we are using (could be Sandbox or Production server)
		curl_setopt( $connection, CURLOPT_URL, $endpoint );

//stop CURL from verifying the peer's certificate
		curl_setopt( $connection, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt( $connection, CURLOPT_SSL_VERIFYHOST, 0 );

//set the headers using the array of headers
		curl_setopt( $connection, CURLOPT_HTTPHEADER, $headers );

//set method as POST
		curl_setopt( $connection, CURLOPT_POST, 1 );

//set the XML body of the request
		curl_setopt( $connection, CURLOPT_POSTFIELDS, $xmlbody );

//set it to return the transfer as a string from curl_exec
		curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );

		curl_setopt( $connection, CURLOPT_TIMEOUT, 5 );
//Send the Request
		$response = curl_exec( $connection );

//close the connection
		curl_close( $connection );

		$r = simplexml_load_string( $response );

		if($r->Ack == 'Success') {
			return true;
		} else {
			$this->recordError($r);
			return false;
		}
	}

	private function recordError($response) {
		if(isset($response->Errors->LongMessage)) {
			$this->error_msg = $response->Errors->LongMessage;
		} elseif(isset($response->Error->ShortMessage)) {
			$this->error_msg = $response->Errors->ShortMessage;
		}
		if(isset($response->Errors->ErrorCode)) {
			$this->error_code = $response->Errors->ErrorCode;
		}
	}

	/**
	 * @return null|String
	 */
	public function getErrorMsg() {
		return $this->error_msg;
	}

	/**
	 * @return null|String
	 */
	public function getErrorCode() {
		return $this->error_code;
	}
}
