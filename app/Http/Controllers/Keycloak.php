<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Keycloak\KeycloakClient;

class Keycloak extends Controller
{
    protected $token; 

    public function page(KeycloakClient $keycloak)
    {      
        // echo 'working'; 
        if (!isset($_REQUEST["code"])) {
			return redirect($keycloak->step1());
		}
		else {
            $this->token = $keycloak->step2($_REQUEST["code"]);
			if (isset($this->token->id_token)) {
                $userData = $keycloak->step3($this->token);
                var_dump($userData);
			}
			else {
				// if error, refresh the code
				$keycloak->step1();
			}
		}
    }
}
