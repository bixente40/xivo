<?php
// 118218.php is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
//
// @package   118218.php
// @copyright 2014 Sébastien TIMONER (EURL STIMSYSTEM) <s.timoner@stimsystem.com>
// @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
//
 $prefix = "0033";
  
  header('HTTP/1.1 200 OK');
  header('Content-Type: text/plain; charset=utf-8');
  
  if (isset($_GET["search"])){
    $search = $_GET["search"];
    search($search);
  }else if (isset($_GET["phonesearch"])){
    $phone = $_GET["phonesearch"];  
    searchphone($phone);
  }
  // recherche inversée
  function searchphone($phone){
    $searchphone = normalizePhoneNumber($phone);
    $url = "http://www.118218.fr/recherche?geo_id=&distance=&category=&phone=".$searchphone."&where=";
    $page = getPage($url);
    writeHeader();
    extractData($page,$phone);
  }
  
  // recherche dans l'annuaire
  function search($search){
    writeHeader();
    $arr = explode(",",$search);
    $nom = urlencode(trim($arr[0]));
    (isset($arr[1]) && (strtolower(trim($arr[1]))!=""))?$ville=trim($arr[1]):$ville="";
    $pro = (isset($arr[2]) && (strtolower(trim($arr[2]))=="pro"));
    if ($pro){
      $url = "http://www.118218.fr/recherche?category=&what=$nom&where=$ville";
    }else{
      $url = "http://www.118218.fr/recherche?category=&who=$nom&where=$ville";
    }
    $page = getPage($url);
    extractData($page);
  }
  function writeLn($name,$phone,$ville){
    $arr = explode("\n",$ville);
    $ville = "";
    foreach ($arr as $line){
      $ville .= trim($line);
    }
    echo "$name|$phone|$ville \n";
  }
  function writeHeader(){
    writeLn("name","phone","ville");
  }
  function getPage($url){
    $cookie = 'cookies.txt';
    $headers[] = 'Accept: image/gif, image/x-bitmap, image/jpeg, image/pjpeg,text/html,application/xhtml+xml'; 
    $headers[] = 'Connection: Keep-Alive'; 
    $headers[] = 'Content-type: application/x-www-form-urlencoded;charset=UTF-8'; 
    $useragent = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.0.3705; .NET CLR 1.1.4322; Media Center PC 4.0)'; 
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
    curl_setopt($ch, CURLOPT_HEADER, 0); 
    curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
    curl_setopt($ch, CURLOPT_COOKIEJAR,$cookie);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
    $output = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    $output = mb_convert_encoding($output, 'HTML-ENTITIES', 'UTF-8');
    return $output;  
  }
    
  function normalizePhoneNumber($phoneNumber) {
    $phoneNumber = preg_replace('/[^0-9]/','',$phoneNumber);
    global $prefix;
    if(strlen($phoneNumber) > 10) {
      $phoneNumber = '0'.substr($phoneNumber, strlen($prefix), strlen($phoneNumber)-strlen($prefix));
    }
    return $phoneNumber;
  }  
  function extractData($page,$forcetel=""){
    libxml_use_internal_errors(true);
    $dom = new DomDocument();
    $dom->encoding = 'UTF-8';    
    $dom->loadHTML($page);
    if (is_object($dom)){
      $lp = $dom->getElementById('resultsColumn');
      if (is_object($lp)){
        $nom = "";
        $adresse = "";
        $tel = "";
        $sections = $lp->getElementsByTagName("section");
        foreach($sections as $section){
          $class = $section->getAttributeNode('class');
          $classValue=trim($class->value);
          $classNeedle = "searchResult";
          $pos = strpos($classValue,$classNeedle);
          if ($pos !== false){
            
            $listeh2 = $section->getElementsByTagName("h2");
            foreach($listeh2 as $h2){
              $nom = trim($h2->textContent);
            }
            $addresss = $section->getElementsByTagName("address");
            foreach($addresss as $address){
              $adresse = trim($address->textContent);
            }
            
            if ($forcetel<>""){
              $tel=$forcetel;
            }else{
              $telephones = $section->getElementsByTagName("p");
              foreach($telephones as $telephone){
                $class = $telephone->getAttributeNode('class');
                if (is_object($class)){
                  if (trim($class->value)=="telephone"){
                    $tel = normalizePhoneNumber($telephone->textContent);
                  }
                }
              }
            }
            if (trim($tel)<>""){
              writeLn($nom,$tel,$adresse);
            }
          }
        }
      }
    }
    return;
  }
?>
