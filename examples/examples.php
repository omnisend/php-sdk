<?php
require_once __DIR__ . "/../src/Omnisend.php";

//change 'your-api-key' to you're API Key
$omnisend = new Omnisend('your-api-key', array('timeout' => 15));

// Create new contact
$contactID = "";
$contacts = $omnisend->post(
    'contacts',
    array(
        "email" => "vanessa.kensington@example.com",
        "firstName" => "Vanessa",
        "lastName" => "Kensington",
        "status" => "subscribed",
        "statusDate" => "2018-12-11T10:29:43+00:00",
    )
);
if ($contacts) {
    print_r($contacts);
    $contactID = $contacts['contactID'];
} else {
    print_r($omnisend->lastError());
}

// updated in example 1 created contact
if ($contactID) {
    $contacts = $omnisend->patch(
        'contacts/' . $contactID,
        array("country" => "United Kingdom")
    );
    if ($contacts) {
        print_r($contacts);
    } else {
        print_r($omnisend->lastError());
    }
}

// Get contacts with filter (query parameters)
$contacts = $omnisend->get('contacts', array("limit" => 10, "offset" => 0));
if ($contacts) {
    print_r($contacts);
} else {
    print_r($omnisend->lastError());
}

// Create or update product
$product = $omnisend->push(
    'products',
    array(
        "productID" => "prod666",
        "title" => "Container for mojo",
        "status" => "inStock",
        "description" => "Super quality metal container",
        "currency" => "USD",
        "productUrl" => "http://www.example.com/products/prod-666",
        "vendor" => "Nanotech",
        "type" => "containers",
        "createdAt" => "2000-01-01T00:00:01Z",
        "images" => [
            array(
                "imageID" => "prod-66-img1",
                "url" => "http://www.example.com/images/products/prod-666.png",
                "isDefault" => true,
                "variants" => [
                    "prod666",
                ],
            ),
        ],
        "tags" => [
            "container",
            "metal",
        ],
        "categoryIDs" => [
            "cat123",
            "cat1267",
        ],
        "variants" => array(
            [
                "variantID" => "prod666",
                "title" => "Container for mojo",
                "sku" => "123",
                "status" => "inStock",
                "price" => 66666,
                "oldPrice" => 75000,
                "productUrl" => "http://www.example.com/products/prod-666",
                "imageID" => "prod-66-img1",
                "customFields" => [
                    "protectionClass" => "IP99",
                ],
            ],
        ),
    )
);
if ($product) {
    //Product successfully saved
    print_r($product);
    $productID = $product['productID'];
} else {
    print_r($omnisend->lastError());
}

//delete product
if (!isset($productID)) {
    $productID = "prod666";
}

$product = $omnisend->delete('products/' . $productID);
if ($product) {
    echo "Product successfully deleted.";
} else {
    print_r($omnisend->lastError());
}

//Output Omnisend snippet
echo $omnisend->getSnippet();
