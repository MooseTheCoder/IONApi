<?php

class IONApi{
    private $IONEmail;
    private $IONPassword;
    private $IONToken;
    private $IONSiteId;

    public function Login($email, $password){
        $this->IONEmail = $email;
        $this->IONPassword = $password;
        // Get Auth Token
        $OAuthSettings = json_decode(file_get_contents("https://portal.arubainstanton.com/settings.json"), true);
        $ClientID = $OAuthSettings['ssoClientIdAuthZ'];
        $AccessToken = $this->post(
            'https://sso.arubainstanton.com/aio/api/v1/mfa/validate/full',
            ['username'=>$email,'password'=>$password]
        )['access_token'];
        $Random = bin2hex(openssl_random_pseudo_bytes(32));
        $State = $this->base64url_encode(pack('H*', $Random));
        $CodeChallenge = $this->base64url_encode(pack('H*', hash('sha256', $State)));
        $LoginURL = "https://sso.arubainstanton.com/as/authorization.oauth2?client_id=$ClientID&redirect_uri=https://portal.arubainstanton.com&response_type=code&scope=profile%20openid&state=$State&code_challenge_method=S256&code_challenge=$CodeChallenge&sessionToken=$AccessToken";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $LoginURL);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        $RedirectURL = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $LoginCode = preg_replace("/^https?:\/\/.*\?.*code=([^&]+).*$/", "$1", $RedirectURL);
        $AuthToken = $this->post('https://sso.arubainstanton.com/as/token.oauth2', [
            'client_id'=>$ClientID,
            'redirect_uri'=>'https://portal.arubainstanton.com',
            'code'=>$LoginCode,
            'code_verifier'=>$State,
            'grant_type'=>'authorization_code'
        ])['access_token'];
        $this->IONToken = $AuthToken;
    }

    public function Token($AuthToken){
        $this->IONToken = $AuthToken;
    }

    private function post($endpoint, $params, $headers = []){
        $headers[]='Content-Type: application/x-www-form-urlencoded';
        $headers[]='x-ion-api-version: 12';
        $postString="";
        foreach($params as $param=>$value){
            $value=urlencode($value);
            $postString.="$param=$value&";
        }
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $postString,
            CURLOPT_HTTPHEADER => $headers,
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response, true);
        return $response;
    }

    private function get($endpoint, $headers=[]){
        $headers[]='Authorization: Bearer '.$this->IONToken;
        $headers[]='x-ion-api-version: 12';
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => $endpoint,
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_CUSTOMREQUEST => 'GET',
          CURLOPT_HTTPHEADER => $headers,
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response, true);
    }

    private function base64url_encode($plainText){
        $base64 = base64_encode($plainText);
        $base64 = trim($base64, "=");
        $base64url = strtr($base64, '+/', '-_');
        return ($base64url);
    }

    public function SetSiteId($SiteId){
        $this->IONSiteId = $SiteId;
    }

    public function GetSites(){
        return $this->get('https://nb.portal.arubainstanton.com/api/sites');
    }

    public function GetInventory(){
        return $this->get('https://nb.portal.arubainstanton.com/api/sites/'.$this->IONSiteId.'/inventory');
    }

    public function GetClientSummary(){
        return $this->get('https://nb.portal.arubainstanton.com/api/sites/'.$this->IONSiteId.'/clientSummary');
    }
}
