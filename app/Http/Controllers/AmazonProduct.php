<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Peron\AmazonMws\AmazonProductList;

class AmazonProduct extends Controller
{
    public function getMatchProductForId(Request $request)
    {
        $marketplace = $request->input('marketplace');
        $country = $request->input('country');
        $storeName = strtoupper($marketplace.$country);
        $productHandler = new AmazonProductList($storeName);

        if ($request->input('EAN')) {
            $productHandler->setIdType('EAN');
            $productHandler->setProductIds($request->input('EAN'));
        } elseif ($request->input('UPC')) {
            $productHandler->setIdType('UPC');
            $productHandler->setProductIds($request->input('UPC'));
        }

        $productHandler->fetchProductList();

        $product = $productHandler->getProduct();

        if (isset($product['Error'])) {
            return response()->json($product);
        } else {
            $productData = $product[0]->getData();

            return response()->json(['ASIN' => $productData['Identifiers']['MarketplaceASIN']['ASIN']]);
        }
    }
}
