<?php
	
include_once('IDNAConvert.class.php');

function ardis_getConfigArray(){
$configarray = array(
    "address" => array (
        "FriendlyName" => "Адрес",
        "Type" => "text", # Text Box
        "Size" => "25", # Defines the Field Width
        "Description" => "",
        "Default" => "my.ru-tld.ru",
    ),
/*    "port" => array (
        "FriendlyName" => "Порт",
        "Type" => "text", # Text Box
        "Size" => "25", # Defines the Field Width
        "Description" => "",
        "Default" => "443",
    ),*/
    "api" => array (
        "FriendlyName" => "Адрес API",
        "Type" => "text", # Text Box
        "Size" => "25", # Defines the Field Width
        "Description" => "",
        "Default" => "/manager/billmgr",
    ),


    "username" => array (
        "FriendlyName" => "Логин",
        "Type" => "text", # Text Box
        "Size" => "25", # Defines the Field Width
        "Description" => "Введите логин полученный у регистратора https://my.ru-tld.ru/",
        "Default" => "",
    ),
    "password" => array (
        "FriendlyName" => "Пароль",
        "Type" => "password", # Password Field
        "Size" => "25", # Defines the Field Width
        "Description" => "",
        "Default" => "",
    ),


    "ns1" => array (
        "FriendlyName" => "Первичный",
        "Type" => "text", # Text Box
        "Size" => "25", # Defines the Field Width
        "Description" => "",
        "Default" => "dns1.ru-tld.ru",
    ),
    "ns2" => array (
        "FriendlyName" => "Вторичный",
        "Type" => "text", # Text Box
        "Size" => "25", # Defines the Field Width
        "Description" => "",
        "Default" => "dns1.ru-tld.ru",
    ),
    "ns3" => array (
        "FriendlyName" => "Дополнительный",
        "Type" => "text", # Text Box
        "Size" => "25", # Defines the Field Width
        "Description" => "",
        "Default" => "",
    ),
    "ns4" => array (
        "FriendlyName" => "Расширенный",
        "Type" => "text", # Text Box
        "Size" => "25", # Defines the Field Width
        "Description" => "",
        "Default" => "",
    ),

);

		$xxx = file_get_contents("https://ru-tld.ru/json/pricelist.json");
		$json=(array)json_decode($xxx,true);

	foreach($json[elem] as $elem)
		{
		$elem=(array)$elem;
		if($elem[itemtype]=='domain')
			$ret[$elem[tld]][$elem[id]]=$elem[internalname];
		}


	$IDNAConverter = new IDNAConvert();

	foreach($ret as $tld=>$el)
		{
//		if(count($el)>1){
			$configarray[$tld]=array("FriendlyName" => $IDNAConverter->decode($tld),
			"Type" => "dropdown",
		        "Size" => "50", # Defines the Field Width
			"Options" => implode(",",$el),
			"Default" => "");
//			}
		}
return $configarray;
}
function ardis_Sync($params) {
	SaveLOG('ardis_Sync',$params);
}
function translitIt($str) {
$tr = array(
        "А"=>"A","Б"=>"B","В"=>"V","Г"=>"G",
        "Д"=>"D","Е"=>"E","Ж"=>"J","З"=>"Z","И"=>"I",
        "Й"=>"Y","К"=>"K","Л"=>"L","М"=>"M","Н"=>"N",
        "О"=>"O","П"=>"P","Р"=>"R","С"=>"S","Т"=>"T",
        "У"=>"U","Ф"=>"F","Х"=>"H","Ц"=>"TS","Ч"=>"CH",
        "Ш"=>"SH","Щ"=>"SCH","Ъ"=>"","Ы"=>"YI","Ь"=>"",
        "Э"=>"E","Ю"=>"YU","Я"=>"YA","а"=>"a","б"=>"b",
        "в"=>"v","г"=>"g","д"=>"d","е"=>"e","ж"=>"j",
        "з"=>"z","и"=>"i","й"=>"y","к"=>"k","л"=>"l",
        "м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
        "с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"h",
        "ц"=>"ts","ч"=>"ch","ш"=>"sh","щ"=>"sch","ъ"=>"y",
        "ы"=>"yi","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya"
);
     return strtr($str,$tr);
}
function RegDomen($params){

	$IDNAConverter = new IDNAConvert();


	$Response=array(
		'domain'=>$IDNAConverter->encode($params[sld]).".".$IDNAConverter->encode($params[tld]),
	);
	$query=http_build_query($Response);
	$xxx = file_get_contents("https://{$params["address"]}/mancgi/domaininfo?".$query);
	$xml = (array)simplexml_load_string($xxx);
	SaveLOG('ardis_RegisterDomain - $xml',$xml);

	if($xml[status]!='free')
		return array('error'=>'Домен уже занят / Domain already taken');


	$stld=str_replace('_','',$params[tld]);
	$tariff=explode('-',$params[$IDNAConverter->decode($stld)]);


	$Response=array(
		'authinfo'=>"{$params["username"]}:{$params["password"]}",
		'out'=>'json',
		'func'=>'availableprice.periods',
		'elid'=>$tariff[0],
		'sok'=>'ok'
	);
	$query=http_build_query($Response);
	$xxx = file_get_contents("https://{$params["address"]}{$params["api"]}?".$query);
	$json=(array)json_decode($xxx,true);
	SaveLOG('ardis_RegisterDomain - $Response',$Response);
	SaveLOG('ardis_RegisterDomain - $json',$json);




	$Response=array(
		'authinfo'=>"{$params["username"]}:{$params["password"]}",//
		'out'=>'json',
		'func'=>'accountinfo',
		'sok'=>'ok'
	);
	$query=http_build_query($Response);
	$xxx = file_get_contents("https://{$params["address"]}{$params["api"]}?".$query);
	$json2=(array)json_decode($xxx,true);
	SaveLOG('ardis_RegisterDomain - $Response',$Response);
	SaveLOG('ardis_RegisterDomain - $json2',$json2);

//?func=availableprice.periods&out=json&elid=565&sok=ok


	$Response=array(
		'authinfo'=>"{$params["username"]}:{$params["password"]}",//
		'out'=>'json',
		'func'=>'domain.order.4',
		'operation'=>'register',
		'countdomain'=>1,
		'domain'=>$IDNAConverter->encode($params[sld]),
		'domainname_0'=>$IDNAConverter->encode($params[sld]),
		'lang'=>$IDNAConverter->encode($params[tld]),
		'tld'=>$IDNAConverter->encode($params[tld]),
		'domainpropind'=>0,
		'ns0'=>$params[ns1],
		'ns1'=>$params[ns2],
		'ns2'=>$params[ns3],
		'ns3'=>$params[ns4],
		'period_0'=>$json[elem][0]->id,
		'price'=>$tariff[0],
		'pricelist_0'=>$tariff[0],
		'contact'=>$params['additionalfields']['dogovor'],
		'owner'=>$params['additionalfields']['dogovor'],
		'payfrom'=>'account'.$json2[elem][0]->id,//'neworder',
		'remoteid'=>$params[domainid],
		'paynow'=>'on',
		'sok'=>'ok'
	);
	$query=http_build_query($Response);
	$xxx = file_get_contents("https://{$params["address"]}{$params["api"]}?".$query);
	$json=(array)json_decode($xxx,true);
	SaveLOG('ardis_RegisterDomain - $Response',$Response);
	SaveLOG('ardis_RegisterDomain - $json',$json);


	$sql=mysql_query("UPDATE `tbldomainsadditionalfields` SET `value` = '{$json['item.id']}' WHERE `name` ='domenid' AND `domainid`='{$params['domainid']}'");

	if($json['item.id']>0)
		return $json['item.id'];
	else
		return false;

}
function RegContact($params){

	if($params['additionalfields']['reg_to']=='Частное лицо(Physical person)'){

		$person=urlencode($params['additionalfields']['person_r']);
		$Response=array(
			'authinfo'=>"{$params["username"]}:{$params["password"]}",
			'cname'=>$params['additionalfields']['Name'].' '.$params['additionalfields']['Lastname'].' '.$params['additionalfields']['Sourname'],
			'out'=>'json',
			'func'=>'contcat.create.1',
			'ctype'=>'person',
			'sok'=>'ok'
		);
		$query=http_build_query($Response);
		$xxx = file_get_contents("https://{$params["address"]}{$params["api"]}?".$query);
		$json=(array)json_decode($xxx,true);
		SaveLOG('ardis_RegisterDomain - query',$query);
		SaveLOG('ardis_RegisterDomain - json',$json);

		if($json['error'])
			return array('error'=>$json['error']["msg"]);

		$ContractID=$json['domaincontact.id'];

		if($ContractID<=0)
			return array('error'=>'Не удалось зарегистрировать контракт!');


		$bt_=explode('.',$params['additionalfields']['birth_date']);
		$bt=$bt_[2].'-'.$bt_[1].'-'.$bt_[0];

		$pt_=explode('.',$params['additionalfields']['PasportDate']);
		$pt=$pt_[2].'-'.$pt_[1].'-'.$pt_[0];



		$cp=GetCountry($params['countrycode']);


		$Response = array(
		'authinfo'=>"{$params["username"]}:{$params["password"]}",
		'out'=>'json',
		'func'=>'domaincontact.edit',

		'ctype' => 'person',
		'firstname' => translitIt($params['additionalfields']['Name']),
		'firstname_ru' => $params['additionalfields']['Name'],
		'lastname' => translitIt($params['additionalfields']['Sourname']),
		'lastname_ru' => $params['additionalfields']['Sourname'],
		'middlename' => mb_substr(translitIt($params['additionalfields']['Lastname']),0,1),
		'middlename_ru' => $params['additionalfields']['Lastname'],
		'birthdate' => $bt,


		'elid' => $ContractID,

		'la_country' => $cp,

		'phone' => $params['additionalfields']['Phone'],
		'mobile' => $params['additionalfields']['CellPhone'],
		'email' => $params['additionalfields']['Email'],
		'fax' => $params['additionalfields']['Fax'],

//		'inn' => $Person['Inn'],

		'private' => $params['additionalfields']['private_person'],

		'la_address' => $params['additionalfields']['pAddress'],
		'la_city' => $params['additionalfields']['pCity'],
		'la_postcode' =>$params['additionalfields']['pIndex'],
		'la_state'=> $params['additionalfields']['pState'],
		'pa_address' => $params['additionalfields']['pAddress'],
		'pa_addressee' => $params['additionalfields']['pRecipient'],
		'pa_city' => $params['additionalfields']['pCity'],
		'pa_postcode' => $params['additionalfields']['pIndex'],
		'pa_state' => $params['additionalfields']['pState'],

		'passport_series' => $params['additionalfields']['PasportLine'].' '.$params['additionalfields']['PasportNum'],
		'passport_org' => $params['additionalfields']['PasportWhom'],
		'passport_date' => $pt,
		'sok'=>'ok'
		);

		$query=http_build_query($Response);

		$xxx = file_get_contents("https://{$params["address"]}{$params["api"]}?".$query);

		$json=(array)json_decode($xxx,true);

		SaveLOG('ardis_RegisterDomain - query',$query);
		SaveLOG('ardis_RegisterDomain - json',$json);

		if($json['error'])
			return array('error'=>$json['error']->msg);


	}else{

		$person=urlencode($params['additionalfields']['person_r']);
		$Response=array(
			'authinfo'=>"{$params["username"]}:{$params["password"]}",
			'cname'=>$params['additionalfields']['org_r'],
			'out'=>'json',
			'func'=>'contcat.create.1',
			'ctype'=>'company',
			'sok'=>'ok'
		);
		$query=http_build_query($Response);
		$xxx = file_get_contents("https://{$params["address"]}{$params["api"]}?".$query);
		$json=(array)json_decode($xxx,true);
		SaveLOG('ardis_RegisterDomain - query',$query);
		SaveLOG('ardis_RegisterDomain - json',$json);

		if($json['error'])
			return array('error'=>$json['error']["msg"]);

		$ContractID=$json['domaincontact.id'];

		if($ContractID<=0)
			return array('error'=>'Не удалось зарегистрировать контракт!');

		if($params['countrycode']<=0)
			$params['countrycode']='RU';


		$cp=GetCountry($params['countrycode']);

		if($params['additionalfields']['Email']=='')
			$params['additionalfields']['Email']='info@ardis.ru';


		$Response = array(
		'authinfo'=>"{$params["username"]}:{$params["password"]}",
		'out'=>'json',
		'func'=>'domaincontact.edit',

		'ctype' => 'company',
		'company' => translitIt($params['additionalfields']['org_r']),
		'company_ru' => $params['additionalfields']['org_r'],


		'elid' => $ContractID,

		'la_country' => $cp,

		'phone' => $params['additionalfields']['Phone'],
		'mobile' => $params['additionalfields']['CellPhone'],
		'email' => $params['additionalfields']['Email'],
		'fax' => $params['additionalfields']['Fax'],

		'inn' => $params['additionalfields']['inn'],
		'kpp' => $params['additionalfields']['kpp'],

//		'private' => $params['additionalfields']['private_person'],

		'la_address' => $params['additionalfields']['pAddressU'],
		'la_city' => $params['additionalfields']['pCityU'],
		'la_postcode' =>$params['additionalfields']['pIndexU'],
		'la_state'=> $params['additionalfields']['pStateU'],
		'pa_address' => $params['additionalfields']['pAddressU2'],
//		'pa_addressee' => $params['additionalfields']['pRecipient'],
		'pa_city' => $params['additionalfields']['pCityU2'],
		'pa_postcode' => $params['additionalfields']['pIndexU2'],
		'pa_state' => $params['additionalfields']['pStateU2'],

//		'passport_series' => $params['additionalfields']['PasportLine'].' '.$params['additionalfields']['PasportNum'],
//		'passport_org' => $params['additionalfields']['PasportWhom'],
//		'passport_date' => $pt,
		'sok'=>'ok'
		);


		$query=http_build_query($Response);
		$xxx = file_get_contents("https://{$params["address"]}{$params["api"]}?".$query);
		$json=(array)json_decode($xxx,true);

		SaveLOG('ardis_RegisterDomain - query',$Response);
		SaveLOG('ardis_RegisterDomain - json',$json);

		if($json['error'])
			return array('error'=>$json['error']->val.' - '.$json['error']->msg);


	}

	return array('dogovor'=>$ContractID);
}
function GetCountry($COUNTRY){
	if($COUNTRY=='')
		return false;
	$cr=file_get_contents('https://ardis.ru/json/country.json');
	$el=json_decode($cr,true);
	foreach($el->elem as $cr_)
		$c[$cr_->iso2]=$cr_->id;

	return $c[$COUNTRY];

}
function ardis_RegisterDomain($params) {

	SaveLOG('ardis_RegisterDomain',$params);

	if($params['additionalfields']['dogovor']<=0){
		$ret=RegContact($params);
		SaveLOG('ardis_RegisterDomain - RegContact',$ret);
		if($ret['error'])
			return $ret;
		else
			{
			$sql=mysql_query("UPDATE `tbldomainsadditionalfields` SET `value` = '{$ret['dogovor']}' WHERE `name` ='dogovor' AND `domainid`='{$params['domainid']}'");
			SaveLOG('ardis_RegisterDomain - $sql',$sql);
			$params['additionalfields']['dogovor']=$cid=$ret['dogovor'];
			}

	}
	else
		$ret[dogovor]=$cid=$params['additionalfields']['dogovor'];


	$ret=RegDomen($params);
	if($ret['error'])
		return $ret;

	$cid=$params['additionalfields']['dogovor']=$ret[dogovor];

	if($cid>0)
		{
		$values['additionalfields']['dogovor']=$cid;
//		$values['dogovor']=$cid;
		}
	else
		$values["error"] = "unknown";

	SaveLOG('ardis_RegisterDomain - values',$values);

	return $values;

}


function ardis_RenewDomain($params) {
	SaveLOG('ardis_RenewDomain',$params);

	$IDNAConverter = new IDNAConvert();


	$stld=str_replace('_','',$params[tld]);
	$tariff=explode('-',$params[$IDNAConverter->decode($stld)]);

	$Response=array(
		'authinfo'=>"{$params["username"]}:{$params["password"]}",
		'out'=>'json',
		'func'=>'availableprice.periods',
		'elid'=>$tariff[0],
		'sok'=>'ok'
	);
	$query=http_build_query($Response);
	$xxx = file_get_contents("https://{$params["address"]}{$params["api"]}?".$query);
	$json=(array)json_decode($xxx,true);
	SaveLOG('ardis_RenewDomain - $Response',$Response);
	SaveLOG('ardis_RenewDomain - $json',$json);

	$queryresult = mysql_query("SELECT value domenid FROM `tbldomainsadditionalfields` WHERE `name` ='domenid' AND `domainid`='{$params['domainid']}'");
	$data = mysql_fetch_array($queryresult);
    
	$domenid=$data['domenid'];

	$Response = array(
		'authinfo'=>"{$params["username"]}:{$params["password"]}",
		'out'=>'json',
		'func'=>'domain.renew',
		'elid'=>$domenid,
		"autoperiod"=>$json[elem][0]->id,
		'sok'=>'ok'
		);

	$query=http_build_query($Response);
	$xxx = file_get_contents("https://{$params["address"]}{$params["api"]}?".$query);
	$json=(array)json_decode($xxx,true);
	SaveLOG('ardis_RenewDomain - $Response',$Response);
	SaveLOG('ardis_RenewDomain - $json',$json);

	if($json[error])
		return array('error'=>$json['error']->msg);

	return false;

}
function ardis_SaveNameservers($params) {
	SaveLOG('ardis_SaveNameservers',$params);

	$IDNAConverter = new IDNAConvert();

	$Response = array(
		'authinfo'=>"{$params["username"]}:{$params["password"]}",
		'out'=>'json',
		'func'=>'domain.edit',
		'elid'=>$params['additionalfields'][domenid],
		'owner'=>$params['additionalfields'][dogovor],
		'tld'=>$IDNAConverter->encode($params[tld]),
		'ns0'=>$params[ns1],
		'ns1'=>$params[ns2],
		'ns2'=>$params[ns3],
		'ns3'=>$params[ns4],
		'name'=>$IDNAConverter->encode($params[sld]),
		'changens'=> 'on',
		'sok'=>'ok'
		);
	$query=http_build_query($Response);
	$xxx = file_get_contents("https://{$params["address"]}{$params["api"]}?".$query);
	$json=(array)json_decode($xxx,true);
	SaveLOG('ardis_SaveNameservers - query',$query);
	SaveLOG('ardis_SaveNameservers - json',$json);

	if($json['error'])
		return array('error'=>$json['error']->msg);

	return $values;
}
function ardis_GetNameservers($params) {
	SaveLOG('ardis_GetNameservers',$params);

//	ardis_GetContactDetails($params);

	$values["ns1"] = $params["ns1"];
	$values["ns2"] = $params["ns2"];
	$values["ns3"] = $params["ns3"];
	$values["ns4"] = $params["ns4"];
//	$values["ns5"] = $params["ns5"];

	return $values;
}
function ardis_SaveContactDetails($params) {
//	SaveLOG('ardis_SaveContactDetails',$params);

	return array('error'=>'Изменение невозможно! / Change is not possible!');

}

function ardis_GetContactDetails($params) {
/*	SaveLOG('ardis_GetContactDetails',$params);

	$queryresult = mysql_query("SELECT value dogovor FROM `tbldomainsadditionalfields` WHERE `name` ='dogovor' AND `domainid`='{$params['domainid']}'");
	$data = mysql_fetch_array($queryresult);
	$dogovor=$data['dogovor'];


	$Response = array(
		'authinfo'=>"{$params["username"]}:{$params["password"]}",
		'out'=>'json',
		'func'=>'domaincontact.detail',
		'la_country'=>182,
		'elid'=>$dogovor.' - ',
		'sok'=>'ok'
		);
	$query=http_build_query($Response);
	$xxx = file_get_contents("https://{$params["address"]}{$params["api"]}?".$query);
	$json=(array)json_decode($xxx);
	SaveLOG('ardis_GetContactDetails - query',$query);
	SaveLOG('ardis_GetContactDetails - json',$json);

*/

}

function SaveLOG($name,$data){
	ob_start();
	print_r($data);
	$data = ob_get_clean();
	$fp = fopen($_SERVER['DOCUMENT_ROOT'].'/data.txt', 'a');
	fwrite($fp, $name.":\n".$data);
	fclose($fp);
}

?>