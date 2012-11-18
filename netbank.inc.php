<?php

class NetBank {
    public function NetBank () {
    }


    public function login($clientno, $password) {

        $data = Array();
        $url = $this->basepath.'logon.aspx';
        
        $f = explode("\n", $this->getRequest($url));

        foreach ($f as $l) {
            if (!preg_match('!<input!', $l))
                continue;
            $key = trim(preg_replace('!^.*?<input .*name="([^"]+).*?$!', '$1', $l));
            if (preg_match('!value="!', $l))
                $value = trim(preg_replace('!^.*?<input .*?value="([^"]+).+?$!', '$1', $l));
            else
                $value = '';
            $data[$key] = $value;
        }
        
        $data["ctl00\$DefaultContent\$txtUserId"] = $clientno;
        $data["ctl00\$DefaultContent\$txtPassword"] = $password;

        $res = $this->postRequest($url, $data);
        $r = explode("\n", $res);

        foreach ($r as $l) {
            $l = trim($l);
            if (preg_match('!ViewAccounts!', $l)) {
                $this->mainlink = preg_replace('!^.+href="([^"]+)".*$!', '$1', $l);
                break;
            }
        }

        if (!$this->mainlink) {
            echo "Error: Not find link\n";
            return false;
        }

        return true;


    }

    public function getAccounts() {
        if (!$this->mainlink) {
            echo "Error: not logged in\n";
            return false;
        }

        $res = $this->getRequest($this->basepath.$this->mainlink);
        $r = explode("\n", $res);

        foreach ($r as $l) {
            if (preg_match('!ctl00_DefaultContent_accounts!', $l)) {
                $dl = $l;
                break;
            }
        }
        $group = 'none';
        $dla = explode('<tr>', $dl);
        $i = 0;
        $accounts = array();
        $first = true;
        foreach ($dla as $l) {
            $l = trim($l);
            if (preg_match('!disabledMenuItemTitle!', $l)) {
                $group = strip_tags($l);
                continue;
            }
            if (preg_match('!ViewAccountTransactionHistory!', $l)) {
                if (!$first) {
                    $accounts[] = array(
                        'group'     => $group,
                        'name'      => $name,
                        'no'        => $no,
                        'balance'   => $balance,
                        'available' => $available,
                        'link'      => $link,
                    );
                }
                $first = false;
                list($name, $no) = explode('::', strip_tags(str_replace('<br/>', '::', $l)));
                $link = preg_replace('!^.+?<a[^>]+href=\'([^\']+)\'.+?$!', '$1', $l);
                continue;
            }
            if (preg_match('!Balance!', $l)) {
                list($rbal, $cd) = explode('&nbsp;', strip_tags(substr($l, strpos($l, '$')+1)));
                $rbal = str_replace(',', '', $rbal);
                if ($cd != 'CR')
                    $balance = -floatval($rbal);
                else
                    $balance = floatval($rbal);
            }
            if (preg_match('!Available funds!', $l)) {
                list($rbal, $cd) = explode('&nbsp;', strip_tags(substr($l, strpos($l, '$')+1)));
                $rbal = str_replace(',', '', $rbal);
                if ($cd != 'CR')
                    $available = -floatval($rbal);
                else
                    $available = floatval($rbal);
            }
        }
        $accounts[] = array(
            'group' => $group,
            'name'  => $name,
            'no'    => $no,
            'balance'   => $balance,
            'available' => $available,
            'link'      => $link,
        );
        $this->accounts = $accounts;
        return $accounts;
    }

    public function getAccountData($id = 0) {
/*
        if (!isset($this->accounts[$id]))
            return false;

        $a = $this->accounts[$id];

        echo ($this->baseurl.$a['link'])."\n";
        $res = $this->getRequest($this->baseurl.$a['link']);
        
        file_put_contents("/tmp/nb.$id.account", $res);

        */
        $res = file("/tmp/nb.$id.account");
        
        foreach ($res AS $l) {
            $l = trim($l);
            echo "[$l]\n";
        }

    }

    private function getRequest($url) {
        $cookie="cookie.txt"; 
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6"); 
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); 
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie); 
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie); 
        curl_setopt($ch, CURLOPT_REFERER, $url); 

        curl_setopt ($ch, CURLOPT_POST, 0); 
        $result = curl_exec ($ch); 

        $header = curl_getinfo($ch);
        curl_close($ch);
        return $result;
    }

    private function postRequest($url, $data = array()) {
        $cookie="cookie.txt"; 
        $postdata = http_build_query($data); 
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6"); 
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); 
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie); 
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie); 
        curl_setopt($ch, CURLOPT_REFERER, $url); 

        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata); 
        curl_setopt($ch, CURLOPT_POST, 1); 
        $result = curl_exec ($ch); 

        $header = curl_getinfo($ch);
        curl_close($ch);

        if ($header['http_code'] == '302') {
            $url = $header['redirect_url'];
            $result = $this->postRequest($url, $data);
        }
        return $result;
    }

    public function getCookies() {
        return $this->cookies;
    }

    private $basepath = 'https://www2.my.commbank.com.au/mobile/security/';
    private $baseurl = 'https://www2.my.commbank.com.au';
    private $mainlink;

    private $http;
}

?>
