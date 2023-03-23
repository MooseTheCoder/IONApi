# IONApi

```php
<?php

require_once('IONApi.php')

// This requires an account without MFA

$email = 'YOURIAONEMAIL@MAIL.COM';
$password = 'YOURIONPASSWORD';

$IONApi = new IONApi($email, $password);
// Store this token somewhere so you can use it later without needing to ->Login() again
// If you already have a token, skip login and pass the token directly to $IONApi->Token($AuthToken)
$AuthToken = $IONApi->Login();
// Use this to get a list of sites
$Sites = $IONApi->GetSites();
// Set your site id
$IONApi->SetSiteId('YOUR-ION-SITE-ID');
//Get Inventory report
$IONApi->GetInventory();
//Get Client Summary
$IONApi->GetInventory();
// More to do in the future
