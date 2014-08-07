# Installing

Put this in your `composer.json`

```javascript
{
    "repositories": [
        {
            "type": "git",
            "url": "https://github.com/GDP-ADMIN/deepsea-php-sdk.git"
        }
    ],
    "require": {
        "deepsea/php-sdk": "0.1.0"
    }
}
```

# Usage

```php
use DeepSea\SDK\DeepSea;
use DeepSea\SDK\SCOPE;

$clientId = 'id';
$clientSecret = 'secret';
$scope = array(SCOPE::ALL);
$redirectUri = 'http://apps.deepsea.co.id/auth';
$host = null; // If it's set to null, default value will be picked --> 'http://api.deepsea.co.id'
$version = null'; // If it's set to null, default value will be picked --> '/v2'

$deepSea = new DeepSea($clientId, $clientSecret, $scope, $redirectUri, $host, $version);
```
