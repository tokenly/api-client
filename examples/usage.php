<?php

$api = new TokenlyAPI('https://music.tokenly.com/api/v1', 'MY_CLIENT_ID', 'MY_CLIENT_SECRET');
$api->get('music/catalog/albums');

