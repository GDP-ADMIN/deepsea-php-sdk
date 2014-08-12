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
use DeepSea\DeepSea;
use DeepSea\Entities\SCOPE;

$clientId     = '<client id>';
$clientSecret = '<client secret>';
$scope        = array(SCOPE::ALL);
$redirectUri  = 'http://apps.deepsea.co.id/auth';

$deepSea = new DeepSea($clientId, $clientSecret, $scope, $redirectUri);
```
