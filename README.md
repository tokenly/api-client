# Tokenly API client.

Use this client for various Tokenly APIs.

[![Build Status](https://travis-ci.org/tokenly/api-client.svg?branch=master)](https://travis-ci.org/tokenly/api-client)

# Installation


### Add the Laravel package via composer

```
composer require tokenly/api-client
```


### Call a public API without a key

```php
$api = new TokenlyAPI('https://music.tokenly.com/api/v1');
$albums_array = $api->get('music/catalog/albums');
```

### Call a protected API with a client id and secret key

```php
$api = new TokenlyAPI('https://music.tokenly.com/api/v1', new Tokenly\HmacAuth\Generator(), 'MY_CLIENT_ID', 'MY_CLIENT_SECRET');
$albums_array = $api->get('music/music/mysongs');
```
