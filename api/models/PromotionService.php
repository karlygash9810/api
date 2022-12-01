<?php

namespace frontend\modules\api\models;

use common\models\CartsHelper;
use common\models\Products;
use common\models\ProductsPhotos;
use common\models\ProductsPhotosHelper;
use common\models\RefCategories;
use common\models\RefLocationsHelper;
use frontend\models\helpers\BonusesHelper;
use frontend\models\helpers\LogFoHelper;
use frontend\modules\cart\models\ServiceProductHelper;
use frontend\modules\checkout\models\PresentForm;
use yii\base\Component;
use yii\helpers\ArrayHelper;

class PromotionService extends Component
{

    public static function PromotionPrice($products, $prices, $code = NULL, $delivery_model, $payment_form = '000000002', $likeIt = NULL, $selectedPromotion = null)
    {
        $cart = CartsHelper::getCart();

        /** http://jira.next.local/browse/DEVELOPERS-1963 */
//        if (!empty($likeIt)) {
//            $phoneNumber = BonusesHelper::getCurrentUserPhone();
//        }

        /**
         * Берем телефон из пользователя, если он авторизирован.
         * Иначе берем телефон из формы, сохраняем в сессиию.
         * Иначе берем из сессии.
         */

        $phoneNumber = BonusesHelper::getCurrentUserPhone();
        if (\Yii::$app->request->post('phone_number')) {
            $phoneNumber = preg_replace('/[^0-9]/', '', \Yii::$app->request->post('phone_number'));
        } elseif (\Yii::$app->request->post('CheckoutForm') !== null) {
            $phoneNumber = preg_replace('/[^0-9]/', '', \Yii::$app->request->post('CheckoutForm')['phone_number']);
        }

//        file_put_contents('phone.log',$phoneNumber,FILE_APPEND);
//        file_put_contents('phone.log',\Yii::$app->session->get('phoneNumber'),FILE_APPEND);


        $currentLocation = RefLocationsHelper::getLocation();

        if(!isset($currentLocation['fo_id']) && isset($currentLocation['id'])){
            $currentLocation = RefLocationsHelper::getLocationById($currentLocation['id']);
        }

        $data = [];

        $data ['prices'] = [];

        $data ['total_sum'] = NULL;

        $products_ids = [];

        if (!empty($products) && is_array($products) && !empty($prices) && is_array($prices) && !empty($cart)) {

            $carts_products_maps = ArrayHelper::map(
                \Yii::$app->db->createCommand('SELECT * FROM `carts_products_map` WHERE `cart_id`=:cart_id')->bindValues([':cart_id' => $cart->id])->queryAll(),
                'product_id',
                'quantity');

//            $preOrdersFromPromos = \Yii::$app->db->createCommand('SELECT `product_id` FROM `promos_products_map` `ppm` INNER JOIN `promos` `p` ON `p`.`id` = `ppm`.`promo_id` INNER JOIN `preorders_nd`.`po` ON `po`.`promo_id` = `p`.`id` WHERE `p`.`datetime_start` <= NOW() AND `p`.`datetime_end` >= NOW()')->queryColumn();

            $preOrdersProducts = \Yii::$app->db->createCommand('SELECT `pp`.`sku` FROM `preorder_products` `pp` INNER JOIN `preorders_nd` `po` ON `po`.`id` = `pp`.`preorder_id` WHERE `po`.`status` = 1')->queryColumn();

            $cart_data = [];

            $presents = CartsHelper::getPresents();
            $presentsWithoutParents = $presents;
            if (!empty($presents)){
                $presentsIdList = [];
                foreach ($presents as $presentIdList){
                    foreach ($presentIdList as $presentId){
                        $presentsIdList[] = $presentId;
                    }
                }
                $presentsParentsIdList = array_keys($presents);
                $presentsSkusQuery = 'SELECT id, sku FROM products WHERE id IN (' . implode(',', array_merge($presentsIdList, $presentsParentsIdList)) . ')';
                $presentsSkus = \Yii::$app->db->createCommand($presentsSkusQuery)->queryAll();
                $presentsSkusById = ArrayHelper::map($presentsSkus, 'id', 'sku');
            }

            foreach ($products as $product) {
                if (isset($prices[$product['id']]) && isset($carts_products_maps[$product['id']])) {
                    $isDelivery = $delivery_model->getDeliveryOptions($product['id']);

                    $productPrice =  $prices[$product['id']];

                    $isService = 'false';
                    if(isset($product['category_id']) && self::isService($product['category_id']))
                        $isService = 'true';

                    ServiceProductHelper::init();

                    if(ServiceProductHelper::isProductService($product['id'])){
                        $isService = 'true';
                        $productPrice =  ServiceProductHelper::getProductServicePrice($product['id'], $prices);
                    }

                    $presentsBody = [];
                    if (!empty($presents[$product['id']])){
                        unset($presentsWithoutParents[$product['id']]);
                        $presentsByCartItem = $presents[$product['id']];
                        foreach ($presentsByCartItem as $presentId){
                            if (isset($presentsSkusById[$presentId])){
                                $presentsBody[] = [
                                    'SKU' => $presentsSkusById[$presentId],
                                    'Price' => 'null',
                                    'FinalPrice' => 'null',
                                    'Count' => 1,
                                    'IsDelivery' => 'false',
                                    'IsPreOrder' => 'false',
                                    'IsService' => 'false',
                                    'IsPackage' => 'false',
                                    'IsChecked' => 'true',
                                    'OrderNumber' => 0,
                                    'SaleType' => 2,
                                    'GoodGroupId' => 0,
                                    'isPresent' => 'false',
                                    'SubItems' => 'null'
                                ];
                            }
                        }
                    }
                    /*if(isset($presents[$product['id']]) && isset($presentsSkusById[$presents[$product['id']]])){
                        unset($presentsWithoutParents[$product['id']]);
                        $present = [
                            'SKU' => $presentsSkusById[$presents[$product['id']]],
                            'Price' => 'null',
                            'FinalPrice' => 'null',
                            'Count' => 1,
                            'IsDelivery' => 'false',
                            'IsPreOrder' => 'false',
                            'IsService' => 'false',
                            'IsPackage' => 'false',
                            'IsChecked' => 'true',
                            'OrderNumber' => 0,
                            'SaleType' => 2,
                            'GoodGroupId' => 0,
                            'isPresent' => 'false',
                            'SubItems' => 'null'
                        ];
                    }*/

                    $cart_data['CartItems'][] = [
                        'SKU' => $product['sku'],
                        'Price' => $productPrice,
                        'FinalPrice' => $productPrice,
                        'Count' => $carts_products_maps[$product['id']],
                        'IsDelivery' => (isset($isDelivery) ?  'true' : 'false'),
                        'IsPreOrder' => (!empty($preOrdersProducts) && in_array($product['sku'], $preOrdersProducts) ?  'true' : 'false'),
                        'IsService' => $isService,
                        'IsPackage' => 'false',
                        'IsChecked' => 'true',
                        'OrderNumber' => 0,
                        'SaleType' => 2,
                        'GoodGroupId' => 0,
                        'isPresent' => 'false',
                        'SubItems' => $presentsBody
                    ];

                    $products_ids[$product['sku']] = $product['id'];

                }
            }

            if (!empty($presentsWithoutParents)) {
                foreach ($presentsWithoutParents as $parentId => $presentId) {
                    if (isset($presentsSkusById[$parentId]) && isset($presentsSkusById[$presentId])) {
                        $cart_data['CartItems'][] = [
                            'SKU' => $presentsSkusById[$parentId],
                            'Price' => 'null',
                            'FinalPrice' => 'null',
                            'Count' => 1,
                            'IsDelivery' => 'false',
                            'IsPreOrder' => 'false',
                            'IsService' => 'false',
                            'IsPackage' => 'false',
                            'IsChecked' => 'true',
                            'OrderNumber' => 0,
                            'SaleType' => 2,
                            'GoodGroupId' => 0,
                            'isPresent' => 'false',
                            'SubItems' => [
                                'SKU' => $presentsSkusById[$presentId],
                                'Price' => 'null',
                                'FinalPrice' => 'null',
                                'Count' => 1,
                                'IsDelivery' => 'false',
                                'IsPreOrder' => 'false',
                                'IsService' => 'false',
                                'IsPackage' => 'false',
                                'IsChecked' => 'true',
                                'OrderNumber' => 0,
                                'SaleType' => 2,
                                'GoodGroupId' => 0,
                                'isPresent' => 'false',
                                'SubItems' => 'null'
                            ]
                        ];
                    }
                }
            }

            if (!empty($cart_data['CartItems'])) {

                $opts = [
                    'http' => [
                        'method' => 'GET',
                        'header' => 'content-type: application/json',

                        'content' => http_build_query([
                            'ShopCode' => '000756',
                            'CityId' => (isset($currentLocation['fo_id']) ? '' . $currentLocation['fo_id'] . '' : ''),
                            'FaceType' => 1,
                            'SelectedPromotions' => isset($selectedPromotion) ? [$selectedPromotion] : [],
                            'PayFormId' => $payment_form,
                            'PayTypeId' => 1,
                            'PhoneNumber' => (!empty($phoneNumber) ? '' . $phoneNumber . '' : ''),
                            'BonusSpent' => (!empty($likeIt) && !empty($phoneNumber) ? '' . $likeIt . '' : ''),
                            'CartItems' => $cart_data['CartItems'],
                            'Promocode' => [
                                'IsPromocodeUsed' => 'false',
                                'PromocodeNumber' => (isset($code) ? '' . $code . '' : ''),
                                'PromocodeName' => 'null'
                            ]
                        ])
                    ]
                ];

                $context = stream_context_create($opts);

                $logRequestTime = date('Y-m-d H:i:s');

                $url = sprintf(
                    \Yii::$app->params['webapi_url']['carts']['calc-new'],
                    \Yii::$app->params['webapi_host']
                );
                $calcData = @file_get_contents($url, false, $context);
                $logResponseTime = date('Y-m-d H:i:s');

                LogFoHelper::log($url, $opts, $logRequestTime, $calcData, $logResponseTime);

                //TODO после удалить
                /*\Yii::error($url,'promotions');
                \Yii::error($opts,'promotions');
                \Yii::error($logRequestTime,'promotions');
                \Yii::error($calcData,'promotions');
                \Yii::error($logResponseTime,'promotions');*/

                $final_prices = [];

                $total_sum = NULL;

                if (!empty($calcData)) {
                    $calcData = json_decode($calcData);

                    if (!empty($calcData) && $calcData->code == 0 && !empty($calcData->data)) {
                        if(is_object($calcData->data) && !empty($calcData->data->CartItems)){

                            $cartItemPromotions = [];
                            $cartItemServices = [];
                            $cartItemPresentGroups = [];
                            foreach ($calcData->data->CartItems as $cartItem){
                                if(isset($products_ids[$cartItem->SKU])){
                                    if(ServiceProductHelper::isProductService($products_ids[$cartItem->SKU])){ //заново пересчитываем цены на услуги
                                        $price = ServiceProductHelper::getProductServicePrice($products_ids[$cartItem->SKU], $prices);
                                        $final_prices[$products_ids[$cartItem->SKU]] = $price;
                                        //$total_sum += $price * $cartItem->Quantity; //Конечную сумму берем с акционки
                                    }else {
                                        $final_prices[$products_ids[$cartItem->SKU]] = $cartItem->FinalPrice;
                                        //$total_sum += $cartItem->FinalPrice * $cartItem->Quantity; //Конечную сумму берем с акционки
                                    }

                                    if((isset($cartItem->IsPreOrder) && $cartItem->IsPreOrder == 'true' && !empty($products_ids[$cartItem->SKU])) || (in_array($cartItem->SKU, $preOrdersProducts))){
                                        $data['isPreOrder'][$products_ids[$cartItem->SKU]] = true;
                                    }
                                }

                                $cartItemQuantity[$cartItem->SKU] = $cartItem->Quantity;
                                $data['cart_item_quantity'] = $cartItemQuantity;

                                $cartItemPromotions[$cartItem->SKU] = $cartItem->Promotions;
                                $cartItemServices[$cartItem->SKU] = $cartItem->Services;
                                if (!empty($cartItem->AvailablePresents))
                                    $cartItemPresentGroups[$cartItem->SKU] = $cartItem->AvailablePresents;
                            }

                            $data['products_promotions'] = $cartItemPromotions;
                            $data['products_services'] = $cartItemServices;
                            $data['products_present_groups'] = $cartItemPresentGroups;
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

//                        if (!empty($total_sum) && !empty($final_prices) && is_array($final_prices) && sizeof($final_prices) > 0) {
//                            $data ['prices'] = $final_prices;
//                            $data ['total_sum'] = $total_sum;  //Конечную сумму берем с акционки
//                        }
                        if (!empty($final_prices) && is_array($final_prices) && sizeof($final_prices) > 0) {
                            $data ['prices'] = $final_prices;
                        }

                        if (!empty($calcData->data->Promocode)) {
                            if(!empty($calcData->data->Promocode->IsPromocodeUsed) && $calcData->data->Promocode->IsPromocodeUsed == true){
                                $data ['code'] = $calcData->data->Promocode->PromocodeNumber;
                            }

                        }

                        if (!empty($calcData->data->BonusSpent)) {
                            $data ['likeIt'] = $calcData->data->BonusSpent;
                        }

                        if (!empty($calcData->data->PromotionsForSelection)){
                            $data['promotions_for_selection'] = $calcData->data->PromotionsForSelection[0];
                        }

                        $data ['total_sum'] = $calcData->data->TotalFinalPrice; //Конечную сумму берем с акционки
                    }
                }
            }
        }
        return $data;
    }

    public static function isService($categoryId){

        $category = RefCategories::findOne($categoryId);
        if($category && $category->parent_id == 720) //услуга
            return true;

        return false;
    }

    /**
     * @param array $promotionServiceData Result data from PromotionPrice
     * @return array $data
     * @author Yelzhan Z
     */
    public static function getPresentsData(array $promotionServiceData) : array
    {
        $data = [];

        $presentsGroupsByCartItem = $promotionServiceData['products_present_groups'] ?? [];
        $cartItemQuantities = $promotionServiceData['cart_item_quantity'] ?? [];

        $allAvailablePresentsSkuList = [];
        $cartItemSkuList = [];
        $presentPrices = [];
        $presentsCountPerProduct = [];
        $totalPresentsCount = 0;
        $presentFoDetails = [];
        $defaultPresents = [];

        $presentsGroups = [];
        $presentsData = [];

        foreach ($presentsGroupsByCartItem as $cartItemSku => $presentGroups){
            $cartItemSkuList[] = $cartItemSku;
            $presentsCountPerProduct[$cartItemSku] = isset($cartItemQuantities[$cartItemSku]) ?? 1;
            $presentCounterByGroup = 0;
            foreach ($presentGroups as $presentGroup){
                foreach ($presentGroup->Presents as $present){
                    $presentCounterByGroup++;
                    $presentPrices[$present->SKU] = $present->FinalPrice;
                    $allAvailablePresentsSkuList[] = $present->SKU;
                    $presentFoDetails[$cartItemSku][$present->SKU] = [
                        'title' => $present->Name,
                        'startPrice' => $present->StartPrice,
                        'finalPrice' => $present->FinalPrice
                    ];
                    $totalPresentsCount++;
                    $presentModel = new PresentForm();
                    $presentModel->setSku($present->SKU);
                    $presentModel->setParentSku($cartItemSku);
                    $presentModel->setStartPrice($present->StartPrice);
                    $presentModel->setFinalPrice($present->FinalPrice);
                    $presentModel->setTitleFromFo($present->Name);
                    if ($presentCounterByGroup == 1) {
                        $presentModel->setIsSelected(true);
                        $defaultPresents[$presentModel->getParentSku()][] = $presentModel->getSku();
                    }
                    $presentsData[$present->SKU] = $presentModel;
                    $presentsGroups[$cartItemSku][$presentGroup->GroupNumber][$present->SKU] = $presentModel;
                }
                $presentCounterByGroup = 0;
            }
        }

        $allProductsSkuList = array_merge($cartItemSkuList, $allAvailablePresentsSkuList);

        $products = Products::findAll(['sku' => $allProductsSkuList, 'status' => 1]);
        $availablePresents = [];
        $productDetailsBySku = [];

        foreach ($products as $product){
            $productIds[$product->sku] = $product->id;
            $productDetailsBySku[$product->sku]['title'] = $product->title;
            $mainPhotosIdList[] = $product->photo_id;
            $productSkusByPhotoId[$product->photo_id] = $product->sku;
            if (in_array($product->sku, $allAvailablePresentsSkuList))
                $availablePresents[] = $product;
            if (isset($presentsData[$product->sku]))
                $presentsData[$product->sku]->setProductId($product->id);
        }

        if (!empty($mainPhotosIdList)){
            $photos = ProductsPhotos::findAll($mainPhotosIdList);

            foreach ($photos as $photo){
                if (isset($productSkusByPhotoId[$photo->id])){
                    $mainPhotoBySku[$productSkusByPhotoId[$photo->id]] = ProductsPhotosHelper::getPhoto($photo, 80, 80);
                    if (isset($presentsData[$productSkusByPhotoId[$photo->id]])){
                        $presentsData[$productSkusByPhotoId[$photo->id]]->setImageUrl($mainPhotoBySku[$productSkusByPhotoId[$photo->id]]);
                    }
                }
            }
        }

        foreach ($presentsGroups as $parentSku => $presentGroups){
            foreach ($presentGroups as $presentsGroupNumber => $presentsGroup){
                foreach ($presentsGroup as $presentSku => $presentDetails){
                    if (!isset($productIds[$presentSku])){
                        unset($presentsGroups[$parentSku][$presentsGroupNumber][$presentSku]);
                        $totalPresentsCount--;
                    }
                    if (empty($presentsGroups[$parentSku][$presentsGroupNumber])){
                        unset($presentsGroups[$parentSku][$presentsGroupNumber]);
                    }
                }
            }
        }

        $defaultPresentsIdList = [];
        foreach ($defaultPresents as $parentSku => $presentSkuList){
            foreach ($presentSkuList as $presentSku){
                if(isset($productIds[$presentSku]) && isset($productIds[$parentSku]))
                    $defaultPresentsIdList[$productIds[$parentSku]][] = $productIds[$presentSku];
            }
        }

        if (!CartsHelper::getPresents()) {
            foreach ($defaultPresentsIdList as $parentId => $presentIdList) {
                CartsHelper::addPresents($presentIdList, $parentId);
            }
        }

        //TODO некоторые данные избыточны, удалить после рефакторинга во всех местах
        $data['all_available_presents_sku_list'] = $allAvailablePresentsSkuList;
        $data['all_available_presents'] = $availablePresents;
        $data['presents_groups_by_cart_item'] = $presentsGroupsByCartItem;
        $data['main_photo_by_sku'] = $mainPhotoBySku ?? [];
        $data['cart_item_sku_list'] = $cartItemSkuList;
        $data['products_details'] = $productDetailsBySku;
        $data['present_prices'] = $presentPrices;
        $data['present_count_per_product'] = $presentsCountPerProduct;
        $data['present_fo_details'] = $presentFoDetails;

        $data['total_presents_count'] = $totalPresentsCount;
        $data['product_ids'] = $productIds ?? [];
        $data['presents_groups'] = $presentsGroups;
        $data['presents_data'] = $presentsData;

        return $data;
    }
}