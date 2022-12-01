<?php

namespace frontend\modules\api\models;

use yii\base\Component;

class ProductStatusInService extends Component
{

    public function getProductStatusInService($arb_number, $arb_date)
    {
        if (!empty($arb_number) && !empty($arb_date)) {

            $response = @file_get_contents( \Yii::$app->params['webapi_host'] . '/api/v1/psis/send?arb_number=' . $arb_number . '&arb_date='  . urlencode($arb_date));

            if(!empty($response)){
                return ['status' => true, 'message' => $response];
            }else{
                return ['status' => false, 'message' => 'По указанным данным ничего не найдено.'];
            }

        }

    }

}