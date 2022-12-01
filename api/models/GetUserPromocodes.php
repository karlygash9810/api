<?php


namespace frontend\modules\api\models;

use frontend\models\helpers\TranslationHelper;
use yii\base\Component;

class GetUserPromocodes extends Component
{

    public static function getPromoCodesFromFo($phone_number)
    {

        $response = [
            'data' => [],
            'codesCount' => 0,
            'message' => ''
        ];

        if (!empty($phone_number)) {

            $response_data = @file_get_contents(\Yii::$app->params['webapi_host'] . '/api/v1/promocodes/codes?phone_number=' . $phone_number);

            if (!empty($response_data)) {

                $response_data = json_decode($response_data);

                if ($response_data->result == 1) {
                    if(!empty($response_data->data) && is_array($response_data->data) && sizeof($response_data->data) > 0){
                        $response['userPromoCodes'] = $response_data->data;
                        $response['codesCount'] = count($response_data->data);
                    }else{
                        $response['message'] = TranslationHelper::get('Промо-коды не найдены.');
                    }

                } else if($response_data->result == 0) {
                    $response['message'] = TranslationHelper::get('Промо-коды не найдены.');
                } else {
                    $response['message'] = TranslationHelper::get('Сервис временно не доступен');
                }
            } else {
                $response['message'] = TranslationHelper::get('Сервис временно не доступен');
            }

        }


        return $response;

    }
}
