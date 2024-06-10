
# automatecrm-php

A PHP client SDK for automateCRM REST API.






## Installation

You can install the SDK by downloading the source.

A package on packagist will be introduced soon, there after you would be able to install via composer.


After you have downloaded the source, use composer to install the dependencies.

```
cd automatecrm-php-sdk
php composer.phar update

```




    
## Usage/Examples


You will need to get 3 details to instantiate the client:
1. CRM URL, such as https://demo.automatecrm.io/
2. User name
3. Access key, this is available under the user's profile details

```php
<?php
require_once 'automatecrm-php-sdk/vendor/autoload.php';
require_once 'automatecrm-php-sdk/src/automatecrm/Client.php';

use Automatecrm\Rest\Api\Client;

$client = new Client('<crm url>');

try{
	if($client->doLogin('<username>','<access key>')){
		
		//create new Contact
		$createData = array(
			'firstname' 	=> 'API Contact',
			'lastname' 		=> '001',
			'mobile'		=> '111111111'
		);
		
		$result = $client->doCreate('Contacts',$createData);
		if($result){
			echo 'Contact created:'.PHP_EOL;
			print_r($result);
		}else{
			$error = $client->lastError();
			throw new Exception($error['message']);
		}
		
		//get the ID of the created contact
		$contactId = $result['id'];
		
		//Retrieve the contact details based on ID
		$result = $client->doRetrieve($contactId);
		if($result){
			echo 'Contact retrieved'.PHP_EOL;
			print_r($result);
		}else{
			$error = $client->lastError();
			throw new Exception($error['message']);
		}
		
		
		//Update the contact detail: description
		//Note: in update operation all the fields must be passed. 
		//If a field has value, but no value is passed under update operation, it will be overwritten with blank value.
		$result['description'] = 'Updated description';
		$result = $client->doUpdate('Contacts',$result);
		if($result){
			echo  'Contact updated'.PHP_EOL;
			print_r($result);
		}else{
			$error = $client->lastError();
			throw new Exception($error['message']);
		}
		
		
		//Revise the contact detail: description
		//Note: it is safer to use Revise operation if you want to update value for only specified fields, 
		//without the need to pass all field values.
		$revisedata = array(
			'id'	=> $result['id'],
			'description' => 'Revised description'
		);
		$result = $client->doRevise('Contacts',$revisedata);
		if($result){
			echo "Contact revised".PHP_EOL;
			print_r($result);
		}else{
			$error = $client->lastError();
			throw new Exception($error['message']);
		}
		
		
		
		//Query operation
		$query = "Select * from Contacts where firstname='API Contact';";
		$result = $client->doQuery($query);
		
		if($result){
			echo 'Contact list query result'.PHP_EOL;
			print_r($result);
		}else{
			$error = $client->lastError();
			throw new Exception($error['message']);
		}
	}
}catch(Exception $ex){
	echo $ex->getMessage();
}

>
```


## Roadmap

- Introduce support for Exception classes

- Publish the package on Packagist

- Introduce Postman collection and Documentation

