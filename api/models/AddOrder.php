<?php

namespace frontend\modules\api\models;

use common\models\Orders;
use common\models\RefLocationsHelper;
use frontend\models\helpers\BonusesHelper;
use frontend\models\helpers\LogFoHelper;
use frontend\modules\checkout\models\CheckoutDeliveryForm;
use yii\base\Component;

class AddOrder extends Component
{

    public static function addOrder($hash_id)
    {
        $order = \Yii::$app->db->createCommand('SELECT * FROM `orders` WHERE `hash_id`=:hash_id')->bindValues([':hash_id' => $hash_id])->queryOne();

        $currentLocation = RefLocationsHelper::getLocation();

        if(!isset($currentLocation['fo_id'])){
            $currentLocation = RefLocationsHelper::getLocationById($order['delivery_location']);
        }

        if (!empty($order) && !empty($currentLocation)) {

//            switch ($order['delivery_method']) {
//                case 1:
//                    $sale_type = 2;
//                    $transport_type_code = '000000010';
//                    break;
//                case 2:
//                    $sale_type = 3;
//                    $transport_type_code = '000000008';
//                    break;
//                case 3:
//                    $sale_type = 2;
//                    $transport_type_code = '000000015';
//                    break;
//            }

            $payTypeId = null;
            $bank_name = '';

            switch ($order['payment_method']) {
                case 0:
                    $pay_type = '000000002';
                    break;
                case 1:
                    $pay_type = '000000006';
                    break;
                case 3:
                    $pay_type = '000000001';
                    $home_credit_data = \Yii::$app->db->createCommand('SELECT * FROM `orders_homecredit_data` WHERE `order_id`=:order_id')->bindValues([':order_id' => $order['id']])->queryOne();
                    $first_name = (isset($home_credit_data['firstname']) ? $home_credit_data['firstname'] : "");
                    $last_name = (isset($home_credit_data['lastname']) ? $home_credit_data['lastname'] : "");
                    $credit_status = (isset($home_credit_data['status']) ? $home_credit_data['status'] : "");
                    $credit_terms = (isset($home_credit_data['loan_length']) ? $home_credit_data['loan_length'] : "");
                    $bank_name = 'Home Credit';
                    break;
                case 4:
                    $pay_type = '000000001';
                    $eur_credit_data = \Yii::$app->db->createCommand('SELECT * FROM `orders_eurasian_bank_data` WHERE `order_id`=:order_id')->bindValues([':order_id' => $order['id']])->queryOne();
                    $first_name = (isset($eur_credit_data['fname']) ? $eur_credit_data['fname'] : "");
                    $last_name = (isset($eur_credit_data['sname']) ? $eur_credit_data['sname'] : "");
                    $credit_status = (isset($eur_credit_data['status']) ? $eur_credit_data['status'] : "");
                    $credit_terms = (isset($eur_credit_data['month_count']) ? $eur_credit_data['month_count'] : "");
                    $bank_name = 'Eurasian';
                    $payTypeId = isset($eur_credit_data['pay_type_id']) ? $eur_credit_data['pay_type_id'] : null;
                    break;
                case 5:
                    $pay_type = '000000001';
                    $halyk_data = \Yii::$app->db->createCommand('SELECT * FROM `orders_halyk_data` WHERE `order_id`=:order_id')->bindValues([':order_id' => $order['id']])->queryOne();
                    $first_name = (isset($halyk_data['firstname']) ? $halyk_data['firstname'] : "");
                    $last_name = (isset($halyk_data['lastname']) ? $halyk_data['lastname'] : "");
                    $credit_status = '';
                    $credit_terms = (isset($halyk_data['loan_length']) ? $halyk_data['loan_length'] : "");
                    $bank_name = 'Halyk';
                    break;
                case 6:
                    $pay_type = '000000007';
                    break;
                case 8:
                    $pay_type = '000000001'; //is credit
                    $freedom_data = \Yii::$app->db->createCommand('SELECT * FROM `orders_freedom_finance` WHERE `order_id`=:order_id')->bindValues([':order_id' => $order['id']])->queryOne();
                    $first_name = (isset($freedom_data['firstname']) ? $freedom_data['firstname'] : "");
                    $last_name = (isset($freedom_data['lastname']) ? $freedom_data['lastname'] : "");
                    $credit_status = '';
                    $credit_terms = (isset($freedom_data['loan_length']) ? $freedom_data['loan_length'] : "");
                    $bank_name = 'FF Credit';
                    $payTypeId = isset($freedom_data['pay_type_id']) ? $freedom_data['pay_type_id'] : null;
                    break;
                case 9:
                    $pay_type = '000000001';
                    $alfa = \Yii::$app->db->createCommand('SELECT * FROM `orders_alfa_bank` WHERE `order_id`=:order_id')->bindValues([':order_id' => $order['id']])->queryOne();
                    $first_name = (isset($alfa['firstname']) ? $alfa['firstname'] : "");
                    $last_name = (isset($alfa['lastname']) ? $alfa['lastname'] : "");
                    $credit_status = '';
                    $credit_terms = (isset($alfa['loan_length']) ? $alfa['loan_length'] : "");
                    $bank_name = 'Alfa bank';
                    $payTypeId = isset($alfa['pay_type_id']) ? $alfa['pay_type_id'] : null;
                    break;
                case 10:
                    $pay_type = '000000001';
                    $sberbank= \Yii::$app->db->createCommand('SELECT * FROM `orders_sberbank` WHERE `order_id`=:order_id')->bindValues([':order_id' => $order['id']])->queryOne();
                    $first_name = (isset($sberbank['firstname']) ? $sberbank['firstname'] : "");
                    $last_name = (isset($sberbank['lastname']) ? $sberbank['lastname'] : "");
                    $credit_status = '';
                    $credit_terms = (isset($sberbank['loan_length']) ? $sberbank['loan_length'] : "");
                    $bank_name = 'Sberbank';
                    $payTypeId = isset($sberbank['pay_type_id']) ? $sberbank['pay_type_id'] : null;
                    break;
            }

            $orders_products_maps = \Yii::$app->db->createCommand('SELECT * FROM `orders_products_map` WHERE `order_id`=:order_id')->bindValues([':order_id' => $order['id']])->queryAll();

            $orders_epay_report = \Yii::$app->db->createCommand('SELECT * FROM `orders_epay_reports` WHERE `order_id`=:order_id')->bindValues([':order_id' => $order['id']])->queryOne();

            $products_ids = [];

            if (!empty($orders_products_maps) && is_array($orders_products_maps) && sizeof($orders_products_maps) > 0) {

                foreach ($orders_products_maps as $orders_products_map) {
                    $products_ids[] = $orders_products_map['product_id'];
                    $products_maps[$orders_products_map['product_id']] = $orders_products_map;
                }

                $products = \Yii::$app->db->createCommand('SELECT * FROM `products` WHERE `id` IN (' . implode(',', $products_ids) . ')')->queryAll();

                $data = [];

                if (!empty($products) && is_array($products) && sizeof($products) > 0) {

                    foreach ($products as $product) {
                        if (isset($products_maps[$product['id']]) && $products_maps[$product['id']]['price_per_item'] > 0) {

                            $supplier_product = \Yii::$app->db->createCommand('SELECT id FROM `stocks_by_shop` WHERE shop_id=118 AND product_id=:product_id')->bindValues([':product_id' => $product['id']])->queryOne();

                            $data['products'][] = ['quantity' => $products_maps[$product['id']]['quantity'], 'saleType' => ($order['delivery_method'] == CheckoutDeliveryForm::PICKUP_SHOP ? '3' : '2'), 'saleChannel' => 4, 'price' => $products_maps[$product['id']]['price_per_item'], 'oldPrice' => (!empty($products_maps[$product['id']]['old_price_per_item']) ? '' . $products_maps[$product['id']]['old_price_per_item'] . '' : ''), 'sku' => '' . $product['sku'] . '', 'transportTypeCode' => ($order['delivery_transport_code'] ? '' . $order['delivery_transport_code'] . '' : ''),
                                'locationCode' => '0000004990', 'isFinalPrice' => true, 'attachments' => [], 'endPrice' => $products_maps[$product['id']]['total_sum'], 'isVirtual' => (!empty($supplier_product) ? 'true' : 'false')];
                        }

                    }

                    $location = \Yii::$app->db->createCommand('SELECT `title` FROM `ref_locations` WHERE `id` = ' . $order['delivery_location'] . '')->queryOne();

                    if (!empty($order['delivery_pickup_shop'])) {
                        $shop = \Yii::$app->db->createCommand('SELECT `id_1c` FROM `ref_shops` WHERE `id` = ' . $order['delivery_pickup_shop'] . '')->queryOne();

                        $shop_code = '' . $shop['id_1c'] . '';
                    }

                    // Считывание code_hash из сессии
                    $session_code = \Yii::$app->session->has('code_hash') ? \Yii::$app->session->get('code_hash') : NULL;

                    // Считывание like_it из сессии
                    if(\Yii::$app->session->has(BonusesHelper::SESSION_LIKE_IT_KEY)){
                        $bonusSpentPhoneNumber = BonusesHelper::getCurrentUserPhone();
                    }

                    // Считывание like_it из сессии
                    $likeIt = \Yii::$app->session->has(BonusesHelper::SESSION_LIKE_IT_KEY) ? \Yii::$app->session->get(BonusesHelper::SESSION_LIKE_IT_KEY) : NULL;

                    $contacts = [];
                    $contacts['homeNumber'] = '';
                    $contacts['flatNumber'] = '';

                    if($order['delivery_courier_address']){
                        $address = explode(',',$order['delivery_courier_address']);
                        $homeIdx = 2;
                        $flatIdx = 3;
                        if(isset($address[$homeIdx])){
                            $address[$homeIdx] = trim($address[$homeIdx]);
                            $contacts['homeNumber'] = substr($address[$homeIdx], strpos($address[$homeIdx], " ") + 1);
                        }
                        if(!empty($address[$flatIdx])){
                            $address[$flatIdx] = trim($address[$flatIdx]);
                            $contacts['flatNumber'] = (is_numeric(substr($address[$flatIdx], strpos($address[$flatIdx], " ") + 1)))? substr($address[$flatIdx], strpos($address[$flatIdx], " ") + 1) : '';
                        }
                    }
                    $opts = ['http' => [
                        'method' => 'GET',
                        'header' => 'content-type: application/json',
                        'content' => http_build_query(
                            [
                                'BonusSpentPhone' => (!empty($bonusSpentPhoneNumber) ? '' . $bonusSpentPhoneNumber . '' : ''),
                                'BonusSpent' => (!empty($likeIt) ? '' . $likeIt . '' : ''),
                                'PromocodeNumber' => (!empty($session_code) ? '' . $session_code . '' : ''),
                                'seller' => [
                                    'shopCode' => (!empty($shop_code) ? '' . $shop_code . '' : '')
                                ],
                                'client' => [
                                    'contractCode' => '',
                                    'binlin' => '',
                                    'firstName' => (!empty($first_name) ? '' . $first_name . '' : '' . $order['full_name'] . ''),
                                    'lastName' => (!empty($last_name) ? '' . $last_name . '' : ''),
                                    'phone' => '' . $order['phone_number'] . '',
                                    'email' => $order['email'],
                                    'faceType' => 1
                                ],
                                'payType' => [
                                    'payFormId' => (!empty($pay_type) ? $pay_type : ''),
                                    'PayTypeId' => $payTypeId,
                                    'CreditInfo' => [
                                        'BankName' => (!empty($bank_name) ? $bank_name : ''),
                                        'CreditTerms' => (!empty($credit_terms) ? $credit_terms : ''),
                                        'CreditStatus' => (!empty($credit_status) ? $credit_status : ''),
                                    ],
                                    'EpayInfo' => [
                                        'RefNumber' => (!empty($orders_epay_report) ? '' . $orders_epay_report['body_payment_reference'] . '' : ''),
                                        'PaySum' => (!empty($orders_epay_report) ? '' . $orders_epay_report['body_order_amount'] . '' : ''),
                                        'PayStatus' => (!empty($orders_epay_report) ? '' . $order['payment_status'] . '' : '')
                                    ]
                                ],
                                'products' => $data['products'],
                                'delivery' => [
                                    'DateDelivery' => '' . $order['delivery_courier_date'] . '',
                                    'TimeDelivery' => '' . $order['delivery_courier_time'] . '',
                                    'City' => '' . ($location['title'] ? '' . $location['title'] . '' : '') . '',
                                    'Street' => ($order['delivery_courier_address'] ? '' . $order['delivery_courier_address'] . '' : ''),
                                    'Home' => $contacts['homeNumber'],
                                    'Flat' => $contacts['flatNumber'],
                                    'DeliveryPrice' => '1000',
                                    'Latitude' => $order['latitude'],
                                    'Longitude' => $order['longitude']
                                ],
                                /*'business' => [
                                    'IsBusiness' => !empty($order['is_business']) ? $order['is_business'] : '',
                                    'OrgName' => !empty($order['is_business']) ? $order['org_name'] : '',
                                    'BIN' => !empty($order['is_business']) ? $order['bin'] : '',
                                    'BIC' => !empty($order['is_business']) ? $order['bic'] : '',
                                    'CheckingAccount' => !empty($order['is_business']) ? $order['checking_account'] : '',
                                    'OrgAddress' => !empty($order['is_business']) ? $order['org_address'] : '',
                                ],*/
                                'shopCode' => (!empty($shop_code) ? '' . $shop_code . '' : ''),
                                'internetShopNumber' => '' . $hash_id . '',
                                'cityId' => (!empty($currentLocation['fo_id']) ? '' . $currentLocation['fo_id'] . '' : ''),
                                'OrderId' => '' . $order['id'] . ''], 'flags_')
                        ]
                    ];

                    $context = stream_context_create($opts);
                    $logRequestTime = date('Y-m-d H:i:s');
                    $url = sprintf(
                        \Yii::$app->params['webapi_url']['orders']['saveOrder'],
                        \Yii::$app->params['webapi_host']
                    );
                    $data = @file_get_contents($url, false, $context);
                    $logResponseTime = date('Y-m-d H:i:s');
                    LogFoHelper::log($url, $opts, $logRequestTime, $data, $logResponseTime);

                    if (!empty($data)) {
                        $insert_data = json_decode($data);
                        if (!empty($insert_data) && !$insert_data->errorCode) {
                            // Обновление записи в базе данных
                            $status = ($bank_name == 'FF Credit' || $bank_name == 'Alfa bank' || $bank_name == 'Eurasian' || $bank_name == 'Sberbank') ? 2 : 0; //Если FF или Альфа то 2 - Принято
                           if ($insert_data->isSpentBonus) {

                               \Yii::$app->db->createCommand('UPDATE orders 
                                SET 
                                    `transaction_id` = :transaction_id,
                                    `request_uu_id`  = :request_uu_id,
                                    `is_spent_bonus` = :is_spent_bonus,
                                    `spent_bonus`    = :spent_bonus,
                                    `ppo_id`         = :ppo_id,
                                    `status`         = :status
                                WHERE `id` = :id')
                                   ->bindValue(':transaction_id', $insert_data->transactionId)
                                   ->bindValue(':request_uu_id', $insert_data->requestUuid)
                                   ->bindValue(':is_spent_bonus', 1)
                                   ->bindValue(':spent_bonus', $insert_data->spentBonus)
                                   ->bindValue(':ppo_id', trim($insert_data->ppoNumber))
                                   ->bindValue(':status', $status)
                                   ->bindValue(':id', '' . $order['id'] . '')
                                   ->execute();
                           } else {

                               \Yii::$app->db->createCommand('UPDATE orders 
                                SET 
                                    `transaction_id` = :transaction_id,
                                    `request_uu_id`  = :request_uu_id,
                                    `ppo_id`         = :ppo_id,
                                    `status`         = :status
                                WHERE `id` = :id')
                                   ->bindValue(':transaction_id', $insert_data->transactionId)
                                   ->bindValue(':request_uu_id', $insert_data->requestUuid)
                                   ->bindValue(':ppo_id', trim($insert_data->ppoNumber))
                                   ->bindValue(':status', $status)
                                   ->bindValue(':id', '' . $order['id'] . '')
                                   ->execute();
                           }

                        } else {
                            \Yii::$app->db->createCommand('UPDATE `orders` SET `status`=:status, `error`=:error WHERE `id` = :id')
                                          ->bindValues(
                                              [
                                                  ':status' => Orders::STATUS_ERROR,
                                                  ':error'  => 'Ошибка создания заказа в ФО: ' . $insert_data->message,
                                                  ':id'     => '' . $order['id'] . ''
                                              ]
                                          )
                                          ->execute()
                            ;
                        }
                    }
                }
            }
        }
    }
}