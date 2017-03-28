<?php
$client = new SoapClient("http://search.isiknowledge.com/esti/wokmws/ws/WOKMWSAuthenticate?wsdl");
echo "<pre>";
print_r($client->__getFunctions()); 
print_r($client->__getTypes()); 

$ret_val = $client->authenticate();

print_r($ret_val);

echo "</pre>";
?>
