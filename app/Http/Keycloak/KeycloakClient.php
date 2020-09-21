<?php
namespace App\Http\Keycloak;

use Illuminate\Support\Facades\Redirect;

class KeycloakClient
{

    protected $baseUrl = "http://localhost:8080/";

    const AUTHORIZE_URL = "auth/realms/Demo-realm/protocol/openid-connect/auth";
    const ACCESS_TOKEN_URL = "auth/realms/Demo-realm/protocol/openid-connect/token";
    const USER_DATA_URL = "auth/realms/Demo-realm/protocol/openid-connect/userinfo";

    protected $state;
    protected $nonce;

    private function getAuthUrl()
    {
        // $keycloakAuthServer = rtrim($this->properties->keycloakAuthServer, "/");
		// $keycloakRealm = $this->properties->keycloakRealm;
        // $url = $keycloakAuthServer . "/realms/" . $keycloakRealm . "/protocol/openid-connect/auth";
        
        return $this->baseUrl . self::AUTHORIZE_URL;
    }

    private function getAccessTokenUrl()
    {
        return $this->baseUrl . self::ACCESS_TOKEN_URL;
    }

    private function getUserInfoUrl()
    {
        return $this->baseUrl . self::USER_DATA_URL;
    }

    private function post($url, $params, $headers = array()) 
    {		
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params, null, '&'));
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		
		$return = curl_exec($ch);
		$info = curl_getinfo($ch);
		
		curl_close($ch);
		
		return $return;	
	}

    private function generateRandString() {
		
        return md5(uniqid(rand(), TRUE));
		
    }
	
	private function setNonce() {
		
		$this->nonce = $this->generateRandString();
		$_SESSION['openid_connect_nonce'] = $this->nonce;
		
	}
	
	private function setState() {
		
		$this->state = $this->generateRandString();
		$_SESSION['openid_connect_state'] = $this->nonce;
		
	}

        
    /*
        // STEP 1: Redirect to Keycloak login page and comeback with code

        http://localhost:8080/auth/realms/Demo-realm/protocol/openid-connect/auth
            ?response_type=code
            &redirect_uri=http://localhost/wordpress/keycloak&client_id=nodejs-microservice
            &nonce=5866beb58c22eed663b0d1e8451ef1e6&state=b8493148f3b57cba19523e2fc8a4c0d2
            &scope=openid

        After login redirect to below link with code

        http://localhost/wordpress/keycloak/?
            state=3f146b61cb4f507b68835645aac66412
            &session_state=a7bcb0b3-e3a5-4a2a-abe9-db9d5bda35c0
            &code=bd5738d3-3472-4652-82f2-e40e6ccc8733.a7bcb0b3-e3a5-4a2a-abe9-db9d5bda35c0.940f9dda-6362-40c4-baa5-df2dfd4bc046
    */		
	public function step1() {

        $url = $this->getAuthUrl();
        
		$this->setNonce();
		$this->setState();
		
		$params = array(
			'response_type'	=> 'code',
			'redirect_uri'	=> 'http://localhost:8000/keycloak',
			'client_id'		=> 'lara7',
			'nonce'			=> $this->nonce,
			'state'			=> $this->state,
			'scope'			=> 'openid'
		);
		
		$url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params, null, '&');
        session_commit();
        return $url;        
    }
    

    /*        
        Step 2 : 
        http: //localhost:8080/auth/realms/Demo-realm/protocol/openid-connect/token  
        {
            "grant_type": "authorization_code",
            "code": "b0d0975d-26de-47d6-96b7-c0e1fa566b45.e1fe96df-a062-4590-859d-928d29c97c42.940f9dda-6362-40c4-baa5-df2dfd4bc046",
            "redirect_uri": "http:\/\/localhost\/wordpress\/keycloak",
            "client_id": "nodejs-microservice",
            "client_secret": "5a96ae33-4d1d-4505-a688-995f311f1306"
        }
        {
            "access_token": "eyJhbGciOiJSUzI1NiIsInR5cCIgOiAiSldUIiwia2lkIiA6ICJrN2NDSEdGZUVVZlZBeEdOZXRzcThpVV9sQThHazd0eXlCaG1MbzBlQ2xvIn0.eyJleHAiOjE2MDA1MjAxNDIsImlhdCI6MTYwMDUxOTg0MiwiYXV0aF90aW1lIjoxNjAwNTE5NDY3LCJqdGkiOiI4NWFmZTI0Yi1mYzI2LTQ0MGUtOTFmNS1kMGNjYTU5NzRiYjIiLCJpc3MiOiJodHRwOi8vbG9jYWxob3N0OjgwODAvYXV0aC9yZWFsbXMvRGVtby1yZWFsbSIsImF1ZCI6ImFjY291bnQiLCJzdWIiOiJjODFlNzNmNi01ZTI5LTQ1MTQtYTc0OS04M2NhMjlkMDk4MDMiLCJ0eXAiOiJCZWFyZXIiLCJhenAiOiJub2RlanMtbWljcm9zZXJ2aWNlIiwibm9uY2UiOiJmNjY2MWM1ODY1OGJhZmRiMzNlMGY4ZmQ0ZDBhNGQxZiIsInNlc3Npb25fc3RhdGUiOiJlMWZlOTZkZi1hMDYyLTQ1OTAtODU5ZC05MjhkMjljOTdjNDIiLCJhY3IiOiIwIiwiYWxsb3dlZC1vcmlnaW5zIjpbImh0dHA6Ly9sb2NhbGhvc3Qvd29yZHByZXNzIl0sInJlYWxtX2FjY2VzcyI6eyJyb2xlcyI6WyJvZmZsaW5lX2FjY2VzcyIsInVtYV9hdXRob3JpemF0aW9uIiwiYXBwLXVzZXIiXX0sInJlc291cmNlX2FjY2VzcyI6eyJub2RlanMtbWljcm9zZXJ2aWNlIjp7InJvbGVzIjpbInVzZXIiXX0sImFjY291bnQiOnsicm9sZXMiOlsibWFuYWdlLWFjY291bnQiLCJtYW5hZ2UtYWNjb3VudC1saW5rcyIsInZpZXctcHJvZmlsZSJdfX0sInNjb3BlIjoib3BlbmlkIHByb2ZpbGUgZW1haWwiLCJlbWFpbF92ZXJpZmllZCI6dHJ1ZSwicHJlZmVycmVkX3VzZXJuYW1lIjoiZW1wbG95ZWUxIn0.fScV2bOOdpi2TesOD5UrElyxykUAE9SPgNoOHewT-jASe_1QgOwJTN4JKYPWIJMd2OsKk3-d5QJPpF8yui6NiEAPHYI-LJRMCoyZpJLeG6pCaiW12jSn4ImpwdH5AtDClw5NwjhQLkNEzGklWtgHzW_8tuEEqoOmn0ah7mE2mCSQf8ODJoK_wYHPwOjR_g-3KDLeoIEDUZ1xQp1b7eQ2ASnkqAzqD49lGAna5tPQqmUckb0iyqrasyeX3R-qO5tzMoPYcv0JIniu7344eODuPVEitYFKivsR0FNemQ9IiYtaPseiQDe_hW3Ea5bU3upFp-Y4N6QWY_yygfvUdNCjQA",
            "expires_in": 300,
            "refresh_expires_in": 1800,
            "refresh_token": "eyJhbGciOiJIUzI1NiIsInR5cCIgOiAiSldUIiwia2lkIiA6ICI4ZGRhOGEyYi00MjJjLTQ0ZGYtYjM3My1mZTIyOGQ5OGU5OTcifQ.eyJleHAiOjE2MDA1MjE2NDIsImlhdCI6MTYwMDUxOTg0MiwianRpIjoiN2NlMGRhNGItM2I5MC00ZTgyLTliMjMtYTUxNTFjMzYyYjRkIiwiaXNzIjoiaHR0cDovL2xvY2FsaG9zdDo4MDgwL2F1dGgvcmVhbG1zL0RlbW8tcmVhbG0iLCJhdWQiOiJodHRwOi8vbG9jYWxob3N0OjgwODAvYXV0aC9yZWFsbXMvRGVtby1yZWFsbSIsInN1YiI6ImM4MWU3M2Y2LTVlMjktNDUxNC1hNzQ5LTgzY2EyOWQwOTgwMyIsInR5cCI6IlJlZnJlc2giLCJhenAiOiJub2RlanMtbWljcm9zZXJ2aWNlIiwibm9uY2UiOiJmNjY2MWM1ODY1OGJhZmRiMzNlMGY4ZmQ0ZDBhNGQxZiIsInNlc3Npb25fc3RhdGUiOiJlMWZlOTZkZi1hMDYyLTQ1OTAtODU5ZC05MjhkMjljOTdjNDIiLCJzY29wZSI6Im9wZW5pZCBwcm9maWxlIGVtYWlsIn0.Oi3hmVuKRnW6Z2Ni9xY8-qQkOuGa4Z4yLTMzr_2EG5g",
            "token_type": "bearer",
            "id_token": "eyJhbGciOiJSUzI1NiIsInR5cCIgOiAiSldUIiwia2lkIiA6ICJrN2NDSEdGZUVVZlZBeEdOZXRzcThpVV9sQThHazd0eXlCaG1MbzBlQ2xvIn0.eyJleHAiOjE2MDA1MjAxNDIsImlhdCI6MTYwMDUxOTg0MiwiYXV0aF90aW1lIjoxNjAwNTE5NDY3LCJqdGkiOiI1ZmU4YmUxYi1lZDBiLTQxMTctODA5OS01YThkNzgwYjhhMjIiLCJpc3MiOiJodHRwOi8vbG9jYWxob3N0OjgwODAvYXV0aC9yZWFsbXMvRGVtby1yZWFsbSIsImF1ZCI6Im5vZGVqcy1taWNyb3NlcnZpY2UiLCJzdWIiOiJjODFlNzNmNi01ZTI5LTQ1MTQtYTc0OS04M2NhMjlkMDk4MDMiLCJ0eXAiOiJJRCIsImF6cCI6Im5vZGVqcy1taWNyb3NlcnZpY2UiLCJub25jZSI6ImY2NjYxYzU4NjU4YmFmZGIzM2UwZjhmZDRkMGE0ZDFmIiwic2Vzc2lvbl9zdGF0ZSI6ImUxZmU5NmRmLWEwNjItNDU5MC04NTlkLTkyOGQyOWM5N2M0MiIsImF0X2hhc2giOiI5Z3hkTndBSGFaSUxpbHQxeXNSUklRIiwiYWNyIjoiMCIsImVtYWlsX3ZlcmlmaWVkIjp0cnVlLCJwcmVmZXJyZWRfdXNlcm5hbWUiOiJlbXBsb3llZTEifQ.ZET16GlsNxu3PbFDF4FltyuxMvmyhaxpMpK6b6c-UHsjBXpAMmdUdIE7lPVKXVcv6PaHJwf_VwDgpi5JrUB2dBfeA5EdyiRdMZXj4F044ZLK93aMaihYwLOfXOgyiekH0L4YS1BilGcDw8239pKG1ZZO8WKEYSnov-uC3-C8ASLhr_xQdd5YntXxrhKjLawgOzpeINwgjTJBk0iVlsoPa9hArQg0kgRaUfQawzeAsOr7__OVtoAGdLWRoTyKBwzXNGqNCVAf56noj0EF6W1_suIizymw9ASUhi7ABiEfzt8TJh9KPHrN_IczHzcX6NYEOsCPq51YLD3qsYOr7Clnrw",
            "not-before-policy": 0,
            "session_state": "e1fe96df-a062-4590-859d-928d29c97c42",
            "scope": "openid profile email"
        }
    */
    public function step2($code)
    {
        $url = $this->getAccessTokenUrl();

        $params = array(
			'grant_type'	=> 'authorization_code',
			'code'			=> $code,
			'redirect_uri'	=> 'http://localhost:8000/keycloak',
			'client_id'		=> 'lara7',
			'client_secret'	=> '92f39a16-72c8-4f19-949b-1c2d682760cf'
		);

		$token = $this->post($url, $params);
		return json_decode($token);
    }

    // Step 3: Retrieve User Data
    public function step3($token) 
    {
        
        $url = $this->getUserInfoUrl();
		
		$headers = array (
			'Authorization: Bearer ' . $token->access_token
		);
		
        $userInfoObject = json_decode($this->post($url, array(), $headers));
        
        $user = [];
				
		$user['sub']  = isset($userInfoObject->sub) ? $userInfoObject->sub : "";
		$user['name'] = isset($userInfoObject->name) ? $userInfoObject->name : "";
		$user['preferred_username']	= isset($userInfoObject->preferred_username) ? $userInfoObject->preferred_username : "";
		$user['given_name']	 = isset($userInfoObject->given_name) ? $userInfoObject->given_name : "";
		$user['family_name'] = isset($userInfoObject->family_name) ? $userInfoObject->family_name : "";
		$user['email'] = isset($userInfoObject->email) ? $userInfoObject->email : "";
		
        $_SESSION['sub'] = $user['sub'];
        
        return $user;
	}

}
