<?php

namespace frontend\modules\api\models;

use common\models\Users;
use frontend\models\helpers\LogFoHelper;
use yii\base\Component;

class BonusesService extends Component
{

    public static function getLikeIt($phone_number)
    {
        $response = [
            'result' => 0,
            'data' => [],
            'message' => ''
        ];

        if(!empty($phone_number)){

//            $logRequestTime = date('Y-m-d H:i:s');
            $url = \Yii::$app->params['webapi_host'] . '/api/v1/bonus/getbonuses?phone_number='.$phone_number;
            $bonus_cart_data = @file_get_contents($url);
//            $logResponseTime = date('Y-m-d H:i:s');
//            LogFoHelper::log($url, $phone_number, $logRequestTime, $bonus_cart_data, $logResponseTime);

            if (!empty($bonus_cart_data)) {

                $bonus_cart_data = json_decode($bonus_cart_data);

                if($bonus_cart_data->result > 0 && !empty($bonus_cart_data->data->BonusCardModels) && is_array($bonus_cart_data->data->BonusCardModels) && sizeof($bonus_cart_data->data->BonusCardModels) > 0){
                    foreach ($bonus_cart_data->data->BonusCardModels as $bonus) {
                        $bonus_data[$bonus->ActiveBalance] = $bonus;
                    }

                    $bonus_key_max = max(array_keys($bonus_data));
                    $response['result'] = 1;
                    $response['data'] = $bonus_data[$bonus_key_max]->ActiveBalance;

                    if ($bonus_data[$bonus_key_max]->ActiveBalance > 0) {
                        $response['message'] = 'У вас нет активных бонусов!';
                    }
                    
                }else{
                    $response['message'] = 'Сервис временно не доступен';
                }
            }else{
                $response['message'] = 'Сервис временно не доступен';
            }

        }

        return $response;

    }

    public static function getSpentBonus($phoneNumber, $totalSum)
    {
        $response = [
            'result' => 0,
            'ActiveBalance' => 0,
            'SpentBalance' => 0,
            'message' => ''
        ];

        if(!empty($phoneNumber)){

            $url = \Yii::$app->params['webapi_host'] . '/api/v1/bonus/spent-bonuses?phoneNumber=' . $phoneNumber . '&totalSum=' . $totalSum;
            $bonusCartModel = @file_get_contents($url);

            if (!empty($bonusCartModel)) {
                $bonusCartModel = json_decode($bonusCartModel);
                if ($bonusCartModel->result == 1 && count($bonusCartModel->data->BonusCardModels) > 0) {
                    foreach ($bonusCartModel->data->BonusCardModels as $bonus) {
                        $response['ActiveBalance'] = $bonus->ActiveBalance;

                        if(isset($bonus->SpentBalance))
                            $response['SpentBalance'] = $bonus->SpentBalance;
                    }
                    $response['result'] = 1;
                } else {
                    $response['message'] = 'Сервис временно не доступен';
                }
            } else {
                $response['message'] = 'Сервис временно не доступен';
            }
        }

        return $response;
    }

    public static function getCheckGoBonus($phone_number)
    {
        $response = [
            'result' => 0,
            'data' => [],
            'message' => ''
        ];

        if(!empty($phone_number)){

//            $logRequestTime = date('Y-m-d H:i:s');
            $url = \Yii::$app->params['webapi_host'] . '/api/v1/bonus/getgobonus?phone_number='.$phone_number;
            $data = @file_get_contents($url);
//            $logResponseTime = date('Y-m-d H:i:s');
//            LogFoHelper::log($url, $phone_number, $logRequestTime, $data, $logResponseTime);

            if (!empty($data)) {

                $data = json_decode($data);

                if(!empty($data->data) && $data->result == 1){
                    if($data->data->RespCode == 1) {
                        $response['result'] = $data->data->RespCode;
                        $response['data'] = $data->data->Balance;
                    }else{
                        $response['message'] = 'Ошибка- Сервис Банка временно не доступен, повторите позднее.';
                    }

                }
            }else{
                $response['message'] = 'Ошибка- Сервис Банка временно не доступен, повторите позднее.';
            }

        }

        return $response;

    }

    public static function convertGoBonus($phone_number,$bonus)
    {
        $response = [
            'result' => 0,
            'message' => 'Ошибка- Сервис Банка временно не доступен, повторите позднее.',
        ];

        $logRequestTime = date('Y-m-d H:i:s');
        $url = \Yii::$app->params['webapi_host'] . '/api/v1/bonus/convertgobonus?phone_number='.$phone_number.'&bonus='.$bonus;
        $opts = [$phone_number, $bonus];
        $data = @file_get_contents($url);
        $logResponseTime = date('Y-m-d H:i:s');
        LogFoHelper::log($url, $opts, $logRequestTime, $data, $logResponseTime);

        if (!empty($data)) {

            $data = json_decode($data);

            $response['result'] = $data->result;

            if(!empty($data->data) && $data->result == 1 && $data->data->RespMsg === "Success"){
                    $response['message'] = 'Спасибо, Ваши бонусы успешно сконвертированы! Удачных покупок.';
                    $response['goBonusSum'] = $data->data->Data->GoBonusSum;
                    $response['likeITSum'] = $data->data->Data->LikeITSum;
            }
        }

        return $response;

    }

    public function applyBonuses($hash_id)
    {
        $order = \Yii::$app->db->createCommand('SELECT * FROM `orders` WHERE `hash_id`=:hash_id')->bindValues([':hash_id' => $hash_id])->queryOne();

        if (!empty($order)) {

            $user = Users::find()->select(['phone_number'])->where(['id' => $order['user_id']])->one();

            if (isset($user['phone_number'])){
                $bonus_balance = self::getBonuses($user['phone_number']);

            if (isset($bonus_balance) && isset($bonus_balance->ActiveBalance) && $bonus_balance->ActiveBalance > 0) {

                $orders_products_maps = \Yii::$app->db->createCommand('SELECT * FROM `orders_products_map` WHERE `order_id`=:order_id')->bindValues([':order_id' => $order['id']])->queryAll();

                $products_ids = [];

                if (!empty($orders_products_maps) && is_array($orders_products_maps) && sizeof($orders_products_maps) > 0) {

                    foreach ($orders_products_maps as $orders_products_map) {
                        $products_ids[] = $orders_products_map['product_id'];
                        $products_maps[$orders_products_map['product_id']] = $orders_products_map;
                    }

                    $products = \Yii::$app->db->createCommand('SELECT * FROM `products` WHERE `id` IN (' . implode(',', $products_ids) . ')')->queryAll();

                    $data = [];
                    $count_products = [];
                    $potential_bonus_amount = [];

                    if (!empty($products) && is_array($products) && sizeof($products) > 0) {

                        foreach ($products as $product) {
                            if (isset($products_maps[$product['id']])) {
                                $count_products[] = $products_maps[$product['id']]['quantity'];
                                $potential_bonus_amount[] = $products_maps[$product['id']]['price_per_item']*$products_maps[$product['id']]['quantity'];
                            }
                        }

                        $total_potential_bonus_amount = array_sum($potential_bonus_amount);

                        foreach ($products as $product) {
                            if (isset($products_maps[$product['id']])) {

                                $bonus_payment_amount_field = ($products_maps[$product['id']]['quantity']*$products_maps[$product['id']]['price_per_item']/$total_potential_bonus_amount)*$order['bonuses'];

                                $data['items_field'][] = ['bonusPaymentAmountField' => ''.$bonus_payment_amount_field.'', 'bonusPaymentAmountFieldSpecified' => 'true', 'discountField' => '0.0',
                                    'discountFieldSpecified' => 'true', 'goodArticleField' => '' . $product['sku'] . '', 'goodPriceField' => ''.$products_maps[$product['id']]['price_per_item'].'', 'goodPriceFieldSpecified' => 'true',
                                    'hasBeenPaidByBonusField' => 'false', 'isMayBePaidByBonusField' => 'true', 'isMayBePaidByBonusFieldSpecified' => 'true', 'isTestTransactionField' => 'false', 'lineNumberField' => '1', 'lineNumberFieldSpecified' => 'true',
                                    'quantityField' => ''. $products_maps[$product['id']]['quantity'].'', 'quantityFieldSpecified' => 'true', 'summField' => ''.$products_maps[$product['id']]['total_sum'].'',
                                    'summFieldSpecified' => 'true', 'summDiscountedField' => ''.$products_maps[$product['id']]['total_sum'].'', 'summDiscountedFieldSpecified' => 'true'];
                            }

                        }

                        $opts = ['http' => [
                            'method' => 'GET',
                            'header' => 'content-type: application/json',
                            'content' => http_build_query([['businessUnitField' => '10105', 'cardNumberField' => (isset($bonus_balance->CardNumber) ? ''.$bonus_balance->CardNumber.'' : ''),
                                'checkboxNumberField' => 'SWK00033269', 'checkTypeIdField' => '1', 'checkTypeIdFieldSpecified' => 'true', 'checkTotalField' => '' . $order['total_sum'] . '', 'checkTotalFieldSpecified' => 'true', 'discountField' => '0.0', 'discountFieldSpecified' => 'true',
                                'isTestTransactionField' => 'false', 'checkNumberField' => '' . $hash_id . '', 'itemsField' => $data['items_field'], 'organizationField' => 'Alser', 'paidByBonusField' => (isset($order['bonuses']) ? ''.intval($order['bonuses']).'.0' : '0.0'), 'paidByBonusFieldSpecified' => 'true', 'requestIDField' => ''.$user['phone_number'].'',
                                'sumDiscountedField' => '' . $order['total_sum'] . '', 'sumDiscountedFieldSpecified' => 'true', 'transactionDateTimeField' => '' . $order['created_at'] . '', 'transactionDateTimeFieldSpecified' => 'true']])
                        ]
                        ];

                        $context = stream_context_create($opts);

                        $logRequestTime = date('Y-m-d H:i:s');
                        $url = \Yii::$app->params['webapi_host'] . '/api/v1/bonus/apply';
                        $data = @file_get_contents( $url, false, $context);
                        $logResponseTime = date('Y-m-d H:i:s');
                        LogFoHelper::log($url, $opts, $logRequestTime, $data, $logResponseTime);

                        if (!empty($data) && $data == true) {

                            $total_sum = $order['total_sum'] - $order['bonuses'];
                            $old_total_sum = $order['total_sum'];

                            // Обновление записи в orders
                            \Yii::$app->db->createCommand('UPDATE orders 
                                SET 
                                    `total_sum` = :total_sum,
                                    `old_total_sum` = :old_total_sum
                                WHERE `id` = :id')
                                ->bindValue(':total_sum', $total_sum)
                                ->bindValue(':old_total_sum', $old_total_sum)
                                ->bindValue(':id', '' . $order['id'] . '')
                                ->execute();

                        }

                    }
                }
            }
            }
        }


    }


}