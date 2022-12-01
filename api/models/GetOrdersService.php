<?php


namespace frontend\modules\api\models;

use frontend\models\helpers\OrdersHelper;
use yii\base\Component;
use yii\caching\TagDependency;
use yii\helpers\ArrayHelper;

class GetOrdersService extends Component
{

    public static function getOrdersFromFo($phone_number, $isMobile = false)
    {
        $cache_key = 'orders_from_fo_' . $phone_number . ($isMobile ? '_mobile' : '');

        $cached_html = \Yii::$app->cache->get($cache_key);

        if (empty($cached_html)) {
            $response = [
                'result' => 0,
                'data' => [],
                'data_count' => [],
                'message' => ''
            ];

            if (!empty($phone_number)) {

                $response_data = @file_get_contents(\Yii::$app->params['webapi_host']  . '/api/v1/orders/getorders?phone_number=' . $phone_number);

                if (!empty($response_data)) {

                    $response_data = json_decode($response_data);

                    $orders = [];

                    if ($response_data->result > 0 && $response_data->data->Code == 0 && !empty($response_data->data->Data) && is_array($response_data->data->Data) && sizeof($response_data->data->Data) > 0) {
                        if($isMobile) {
                            foreach ($response_data->data->Data as $key => $data) {
                                $statusName = OrdersHelper::getTitleFo($data->ppo_status_code);
                                $data->ppo_status_name = (!empty($statusName) ? $statusName : '');

                                $orders[$key] = $data;
                            }

                        } else {
                            foreach ($response_data->data->Data as $key => $data) {
                                $orders[$data->ppo_number]['delivery_method'] = ($data->target_delivery_name == 'Самовывоз') ? 1 : 0;
                                $orders[$data->ppo_number]['delivery_courier_address'] = $data->address;
                                $orders[$data->ppo_number]['created_at'] = $data->ppo_date;
                                $orders[$data->ppo_number]['total_sum'] = $data->total;
                                $orders[$data->ppo_number]['old_total_sum'] = $data->old_total;
                                $orders[$data->ppo_number]['status'] = $data->ppo_status_code;

                                $products_keywords = ArrayHelper::map(\Yii::$app->db->createCommand('SELECT * FROM `products` WHERE `sku` IN (' . implode(',', array_column($data->goods, 'good_article')) . ')')->cache(600)->queryAll(), 'sku', 'keyword');

                                foreach ($data->goods as $product) {
                                    $orders[$data->ppo_number]['order_products'][$product->good_article]['quantity'] = $product->amount;
                                    $orders[$data->ppo_number]['order_products'][$product->good_article]['price_per_item'] = $product->price;
                                    $orders[$data->ppo_number]['order_products'][$product->good_article]['old_price_per_item'] = $product->old_price;
                                    $orders[$data->ppo_number]['order_products'][$product->good_article]['total_sum'] = $product->total;
                                    $orders[$data->ppo_number]['order_products'][$product->good_article]['old_total_sum'] = $product->old_total;
                                    $orders[$data->ppo_number]['order_products'][$product->good_article]['keyword'] = (isset($products_keywords[$product->good_article]) ? $products_keywords[$product->good_article] : '');
                                    $orders[$data->ppo_number]['order_products'][$product->good_article]['title'] = $product->good_name;
                                }

                            }
                        }

                        $response['result'] = 1;
                        $response['data'] = $orders;
                        $response['data_count'] = count($orders);

                        $cached_html = $response;

                        \Yii::$app->cache->set($cache_key, $cached_html, 600, new TagDependency(['tags' => 'orders_from_fo_' . $phone_number]));

                    } else {
                        $response['message'] = 'Сервис временно не доступен';
                    }
                } else {
                    $response['message'] = 'Сервис временно не доступен';
                }

            }
        }

        return $cached_html;

    }
}