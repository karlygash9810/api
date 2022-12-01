<?php


namespace frontend\modules\api\models;

use frontend\models\helpers\LogFoHelper;

/**
 * Class for check quantity of products in currentLocation && otherLocations
 * Class ProductInfoService
 * @package frontend\modules\api\models
 */
class ProductInfoService
{
    /**
     * @param $cityFoId
     * @param $products
     * @return array
     */
    public static function getInfo($cityFoId, $products){
        $result = [];
        $opts = ['http' =>
            [
                'method' => 'GET',
                'header' => 'content-type: application/json',
                'content' => http_build_query
                (
                    [
                        'CityId' => $cityFoId,
                        "CartItems" => $products
                    ]
                )
            ]
        ];

        $context = stream_context_create($opts);
        $logRequestTime = date('Y-m-d H:i:s');
        $url = sprintf(
            \Yii::$app->params['webapi_url']['products']['remainsByCity'],
            \Yii::$app->params['webapi_host']
        );

        $data = @file_get_contents($url, false, $context);
        $logResponseTime = date('Y-m-d H:i:s');

        LogFoHelper::log($url, $opts, $logRequestTime, $data, $logResponseTime);
        if (!empty($data)) {
            $result = json_decode($data, true);
        }
        return $result;
    }
}