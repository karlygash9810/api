<?php

namespace frontend\modules\api\models;

use common\models\ElectronicKeys;
use yii\base\Component;
use yii\db\Query;

class ElectronicKeysService extends Component
{
    /**
     * @throws \yii\db\Exception
     */
    public static function getElectronicKeys($phoneNumber)
    {
        $data = [
            'message' => 'Электронных ключей/лицензий не найдено.',
        ];

        if (empty($phoneNumber)) {
            return $data;
        }

        $response = @file_get_contents(
            sprintf(
                \Yii::$app->params['webapi_url']['products']['electronicKeys'],
                \Yii::$app->params['webapi_host'],
                $phoneNumber
            )
        );

        if (empty($response)) {
            return $data;
        }

        $electronicKeysData = json_decode($response, true);

        if ($electronicKeysData['result'] != 1) {
            return $data;
        }

        $data['electronic_keys'] = $electronicKeysData['data'];
        $keysShowData            = (new Query())
            ->select(['id', 'show'])
            ->from(ElectronicKeys::tableName())
            ->where(['phone' => $phoneNumber])
            ->indexBy('id')
            ->all()
        ;
        $existingIds             = array_keys($keysShowData);
        $insertValues            = [];
        $showTo                  = strtotime(\Yii::$app->params['electronic_keys_show_date']);

        foreach ($data['electronic_keys'] as &$key) {
            if (strtotime($key['date']) < $showTo) {
                $key['show'] = 1;
            } else {
                $key['show'] = (int)!empty($keysShowData[$key['id']]['show']);
            }

            if (!in_array($key['id'], $existingIds)) {
                $insertValues[] = [
                    $key['id'],
                    $phoneNumber,
                    $key['show'],
                ];
            }
        }

        if ($insertValues) {
            \Yii::$app->db->createCommand()
                          ->batchInsert(
                              'electronic_keys',
                              [
                                  'id',
                                  'phone',
                                  'show',
                              ],
                              $insertValues
                          )
                          ->execute()
            ;
        }

        return $data;
    }
}