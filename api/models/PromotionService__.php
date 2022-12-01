<?php

namespace frontend\modules\api\models;

use common\models\CartsHelper;
use common\models\RefCategories;
use common\models\RefLocationsHelper;
use frontend\models\helpers\BonusesHelper;
use frontend\models\helpers\LogFoHelper;
use frontend\modules\cart\models\ServiceProductHelper;
use yii\base\Component;
use yii\helpers\ArrayHelper;

class PromotionService__ extends Component
{

    public static function PromotionPrice($products, $prices, $code = NULL, $delivery_model, $payment_form = NULL, $likeIt = NULL)
    {
        $cart = CartsHelper::getCart();

        if (!isset($payment_form) || empty($payment_form)) {
            $payment_form = '000000002';
        }

        if (!empty($likeIt)) {
            $phoneNumber = BonusesHelper::getCurrentUserPhone();
        }
        
        $currentLocation = RefLocationsHelper::getLocation();

        if(!isset($currentLocation['fo_id']) && isset($currentLocation['id'])){
            $currentLocation = RefLocationsHelper::getLocationById($currentLocation['id']);
        }

        $data = [];

        $data ['prices'] = [];

        $data ['total_sum'] = NULL;

        $products_ids = [];

        if (!empty($products) && is_array($products) && sizeof($products) && !empty($prices) && is_array($prices) && sizeof($prices) && !empty($cart)) {

            $carts_products_maps = ArrayHelper::map(\Yii::$app->db->createCommand('SELECT * FROM `carts_products_map` WHERE `cart_id`=:cart_id')->bindValues([':cart_id' => $cart->id])->queryAll(), 'product_id', 'quantity');

            $preOrdersFromPromos = \Yii::$app->db->createCommand('SELECT `product_id` FROM `promos_products_map` WHERE `promo_id`in (2155,2156)')->queryColumn();

            $cart_data = [];

            foreach ($products as $product) {
                if (isset($prices[$product['id']]) && isset($carts_products_maps[$product['id']])) {
                    $isDelivery = $delivery_model->getDeliveryOptions($product['id']);

                    $productPrice =  $prices[$product['id']];

                    $isService = 'false';
                    if(isset($product['category_id']) && self::isService($product['category_id']))
                        $isService = 'true';

                    ServiceProductHelper::init();

                    /*if(ServiceProductHelper::isProductService($product['id'])){
                        $isService = 'true';
                        $productPrice =  ServiceProductHelper::getProductServicePrice($product['id'], $prices);
                    }*/

                    $cart_data['CartItems'][] = ['SKU' => $product['sku'], 'Price' => $productPrice, 'FinalPrice' => $productPrice, 'Count' => $carts_products_maps[$product['id']], 'IsDelivery' => (isset($isDelivery) ?  'true' : 'false'), 'IsPreOrder' => (!empty($preOrdersFromPromos) && in_array($product['id'], $preOrdersFromPromos) ?  'true' : 'false'), 'IsService' => $isService, 'IsPackage' => 'false', 'IsChecked' => 'true', 'OrderNumber' => 0,  'SaleType' => 2, 'GoodGroupId' => 0, 'SubItems' => 'null'];

                    $products_ids[$product['sku']] = $product['id'];

                }
            }

            if (!empty($cart_data['CartItems'])) {

                $opts = ['http' => [
                    'method' => 'GET',
                    'header' => 'content-type: application/json',

                    'content' => http_build_query(['ShopCode' => '000756', 'CityId' => (isset($currentLocation['fo_id']) ?  ''.$currentLocation['fo_id'].'' : ''), 'FaceType' => 1, 'PayFormId' => $payment_form, 'PayTypeId' => 1, 'PhoneNumber' => (!empty($phoneNumber) ?  ''.$phoneNumber.'' : ''), 'BonusSpent' => (!empty($likeIt) && !empty($phoneNumber) ?  ''.$likeIt.'' : ''), 'CartItems' => $cart_data['CartItems'], 'Promocode' => ['IsPromocodeUsed' => 'false', 'PromocodeNumber' => (isset($code) ?  ''.$code.'' : ''), 'PromocodeName' => 'null']])
                ]

                ];

                $context = stream_context_create($opts);

                $logRequestTime = date('Y-m-d H:i:s');
                $url = \Yii::$app->params['webapi_host'] . '/api/v1/carts/calc'; // $url = 'http://scsm.next.local:8083/api/TestNewPromotion/NewCartCalc';
                $calcData = @file_get_contents($url, false, $context);
                //echo '<pre>'; var_dump($calc_data); die;
                $logResponseTime = date('Y-m-d H:i:s');

                $final_prices = [];

                $total_sum = NULL;

                if (!empty($calcData)) {
                    LogFoHelper::log($url, $opts, $logRequestTime, $calcData, $logResponseTime);
                    $calcData = json_decode($calcData);

                    if (!empty($calcData) && $calcData->code == 0 && !empty($calcData->data)) {
                        if(is_object($calcData->data) && !empty($calcData->data->CartItems)){
                            foreach ($calcData->data->CartItems as $cartItem){echo '<pre>'; var_dump($cartItem->AvailablePresents); die;
                                if(isset($products_ids[$cartItem->SKU])){
                                    if(ServiceProductHelper::isProductService($products_ids[$cartItem->SKU])){ //заново пересчитываем цены на услуги
                                        $price = ServiceProductHelper::getProductServicePrice($products_ids[$cartItem->SKU], $prices);
                                        $final_prices[$products_ids[$cartItem->SKU]] = $price;
                                        $total_sum += $price * $cartItem->Quantity;
                                    }else {
                                        $final_prices[$products_ids[$cartItem->SKU]] = $cartItem->FinalPrice;
                                        $total_sum += $cartItem->FinalPrice * $cartItem->Quantity;
                                    }

                                    /*if($cartItem->IsPreOrder == 'true' && !empty($products_ids[$cartItem->SKU])){
                                        $data['isPreOrder'][$products_ids[$cartItem->SKU]] = true;
                                    }*/
                                }
                            }
                        }

                        if(isset($products_ids[$cartItem->SKU]) && !empty($calcData->data->PayTypeId)){
                            $data['payFormId'][$products_ids[$cartItem->SKU]] = $calcData->data->PayFormId;
                        }

                        if(!empty($calcData->data->Shop)){
                            foreach ($calcData->data->Shop as $value){
                                $data ['location_shop_code'][] = $value->LocationCode;
                            }
                        }

                        if (!empty($calcData->data->Delivery)) {
                            foreach ($calcData->data->Delivery as $delivery) {
                                $data ['delivery_methods'][$delivery->Id]['id'] = $delivery->Id;
                                $data ['delivery_methods'][$delivery->Id]['name'] = $delivery->Name;
                                $data ['delivery_methods'][$delivery->Id]['days'] = $delivery->Days;
                                $data ['delivery_methods'][$delivery->Id]['sum'] = $delivery->Sum;
                                $data ['delivery_methods'][$delivery->Id]['transport_code'] = $delivery->TransportCode;
                            }

                        }

                        if (!empty($total_sum) && !empty($final_prices) && is_array($final_prices) && sizeof($final_prices) > 0) {
                            $data ['prices'] = $final_prices;
                            $data ['total_sum'] = $total_sum;
                        }

                        if (!empty($calcData->data->Promocode)) {
                            if(!empty($calcData->data->Promocode->IsPromocodeUsed) && $calcData->data->Promocode->IsPromocodeUsed == true){
                                $data ['code'] = $calcData->data->Promocode->PromocodeNumber;
                            }

                        }

                        if (!empty($calcData->data->BonusSpent)) {
                            $data ['likeIt'] = $calcData->data->BonusSpent;
                        }
                    }
                }
            }
        }
        return $data;
    }

    public static function isService($categoryId){

        $category = RefCategories::findOne($categoryId);
        if($category->parent_id == 720) //услуга
            return true;

        return false;
    }
}