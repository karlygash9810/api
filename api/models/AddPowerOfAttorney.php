<?php


namespace frontend\modules\api\models;

use common\models\Users;
use common\models\UsersBusinessAttorney;
use frontend\models\helpers\LogFoHelper;
use yii\base\Component;


class AddPowerOfAttorney extends Component
{
    public static function addPowerOfAttorney($userId)
    {
        $powerOfAttorney = UsersBusinessAttorney::findOne(['user_id' => $userId]);
        $user = Users::findOne(['id' => $userId]);

        if (!empty($powerOfAttorney)) {
            $opts = ['http' =>
                [
                    'method' => 'GET',
                    'header' => 'content-type: application/json',
                    'content' => http_build_query(
                        [
                            'client' => [
                                'phone_number' => $user->phone_number,
                                'first_name' => $user->first_name,
                                'email' => $user->email,
                                'bin' => $user->bin,
                                'bic' => $user->bic,
                            ],
                            'business' => [
                                'org_name' => $user->org_name,
                                'bin' => $user->bin,
                                'bic' => $user->bic,
                                'checking_account' => !empty($user->checking_account) ? $user->checking_account : '',
                                'org_address' => !empty($user->address) ? $user->address : '',
                            ],
                            'power_of_attorney' => [
                                'document_no' => !empty($powerOfAttorney->document_no) ? $powerOfAttorney->document_no : '',
                                'date_of_issue' => !empty($powerOfAttorney->date_of_issue) ? $powerOfAttorney->date_of_issue : '',
                                'date_of_expiry' => !empty($powerOfAttorney->date_of_expiry) ? $powerOfAttorney->date_of_expiry : '',
                                'passport_no' => !empty($powerOfAttorney->passport_no) ? $powerOfAttorney->passport_no : '',
                                'passport_date_of_issue' => !empty($powerOfAttorney->passport_date_of_issue) ? $powerOfAttorney->passport_date_of_issue : '',
                                'passport_issued_by' => !empty($powerOfAttorney->passport_issued_by) ? $powerOfAttorney->passport_issued_by : '',
                                'amount' => !empty($powerOfAttorney->amount) ? $powerOfAttorney->amount : '',
                                'third_party_full_name' => !empty($powerOfAttorney->third_party_full_name) ? $powerOfAttorney->third_party_full_name : '',
                                'encoded_file' => !empty($powerOfAttorney->filename) ? 'sadsadsad' : 'dasda', //TODO: Изменить на закодированный файл
                            ],
                        ]
                    )
                ],
            ];

            $context        = stream_context_create($opts);
            $logRequestTime = date('Y-m-d H:i:s');
            $url = sprintf(
                \Yii::$app->params['webapi_url']['orders']['saveOrder'],
                \Yii::$app->params['webapi_host']
            );
            $data = @file_get_contents($url, false, $context);
            $logResponseTime = date('Y-m-d H:i:s');
            LogFoHelper::log($url, $opts, $logRequestTime, $data, $logResponseTime);

        }
    }
}