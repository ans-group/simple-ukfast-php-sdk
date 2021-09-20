# Simple UKFast SDK

This is not intended to replace the UKFast SDK, but is simply a proof of concept of another way we could approach it.

Instead of the SDK being an abstraction over our API's, this offers a thin wrapper.

The client provides methods for hitting the 5 different kinds of endpoints we have

 * Getting a page of items
 * Getting a single item
 * Creating an item
 * Updating an item
 * Deleting an item

You pass the API paths directly to this SDK, using developers.ukfast.io as a reference e.g.

```php
<?php

$ukfast = new \UKFast\SimpleSDK\Client;
$ukfast->auth('my-ukfast-key');

$page = $ukfast->get('/pss/v1/requests'); // returns a Page class

echo $page->getItems()[0]->author->id . "\n";


// can also do concurrent requests
[$requests, $replies] = $ukfast->concurrently(fn ($ukfast) => [
    $ukfast->get('/pss/v1/requests'),
    $ukfast->get('/pss/v1/replies'),
]);

echo $requests->getItems()[0]->author->id . "\n";
echo $replies->getItems()[0]->author->id . "\n";

```

The advantage of this is that this is all the code we need to interface with our entire API's