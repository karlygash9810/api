<?php

namespace app\api\modules\products\models;
use yii\base\Model;
use Yii;

    /**
     * This is the model class for table "products".
     *
     * @property int    $id Идентификатор
     * @property string $name Название
     * @property string $category_name Название категория
     * @property string $brand_name Название бренда
     * @property int    $price Сумма
     * @property int    $rrp_price РРП Прайс
     * @property int    $status Статус
     * @property string $created_at Время создания
     * @property string $updated_at Время обновления
     */
class Products extends \yii\db\ActiveRecord
{
    public $price = 0;
    public $rrp_price;
    public $status;
    public $name;
    public $category_name;
    public $brand_name;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'price', 'rrp_price', 'status'], 'integer', 'required'],
            [['name', 'category_name', 'brand_name'], 'string','required'],
        ];
    }


    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'              => 'Id',
            'price'           => 'Сумма',
            'rrp_price'       => 'РРП Прайс',
            'name'            => 'Название',
            'category_name'   => 'Название категория',
            'brand_name'      => 'Название бренда',
            'status'          => 'Статус',
            'created_at'      => 'Время создания',
            'updated_at'      => 'Время обновления',
        ];
    }



}
