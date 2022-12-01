<?php

namespace app\api\modules\products\controllers;
use app\api\modules\products\models\Products;
use yii\db\Query;
use yii\filters\AccessControl;
use yii\helpers\Inflector;
use yii\web\Controller;
use yii\web\Response;
use yii\db\ActiveRecord;
use yii\data\Pagination;
use yii\web\Request;
use yii\web\UploadedFile;
use Yii;
use yii\base\Exception;
use yii\helpers\ArrayHelper;

/**
 * Default controller for the `orders` module
 */
class DefaultController extends Controller
{
    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['get-products', 'get-current-products', 'create', 'update', 'brand'],
//                        'roles' => ['user', '4'],
                    ],
                ],
            ],
        ];
    }

    /**

     * Get all products in db
     *
     */
//http://localhost:3003/api/products
    public function actionGetProducts()
    {
        $data =[];
        $products = Products::find()->select('*')->all();
        $data['products'] = $products;
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $data;
    }
    /**
     * @param $id
     * @return Products|null
     */
//http://localhost:3003/api/products/{id}
    public static function actionGetCurrentProducts($id = null)
    {
        $result = null;
        $result = Products::findOne($id);// (new Query())->select('*')->from('product')->where(['id' => $id])->one();
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $result;
    }

    /**
     * @return array
     * @throws \yii\db\Exception
     */
//http://localhost:3003/api/create
    public function actionCreate()
    {
        $model = new Products();

        if (!empty($_POST['name']))
        {
            $model->name = $_POST['name'];
        }

        if (!empty($_POST['category_name']))
        {
            $model->category_name = $_POST['category_name'];
        }

        if (!empty($_POST['brand_name']))
        {
            $model->brand_name =$_POST['brand_name'];
        }
        if (!empty($_POST['price']))
        {
            $model->price = $_POST['price'];
        }
        if (!empty($_POST['rrp_price']))
        {
            $model->rrp_price = $_POST['rrp_price'];
        }
        if (!empty($_POST['status']))
        {
            $model->status = $_POST['status'];
        }

        \Yii::$app->db->createCommand('INSERT INTO `product` SET name=:name, category_name=:category_name, brand_name=:brand_name, price=:price, rrp_price=:rrp_price, status=:status')->bindValues([
            ':name'     => $model->name,
            ':category_name'   => $model->category_name,
            ':brand_name'   => $model->brand_name,
            ':price'   => $model->price,
            ':rrp_price'   => $model->rrp_price,
            ':status'     => $model->status,
        ])->execute();

        $data          = [];
        $data['model'] = $model;
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $data;



    }

    /**
     * @param $id
     * @return void
     */
//http://localhost:3003/api/products/update/1
    public function actionUpdate($id)
    {
        $data =[];
        $request = Yii::$app->request;

        $product = Products::updateAll(['name' => $request->getBodyParam('name'),
            'category_name' => $request->getBodyParam('category_name'),
            'brand_name' => $request->getBodyParam('brand_name'),
            'price' => $request->getBodyParam('price'),
            'rrp_price' => $request->getBodyParam('rrp_price'),
            'status' => $request->getBodyParam('status')],
            ['id'=>$id]);
        print_r("saved");
    }

    /**
     * @param $name
     * @return array
     */
    //http://localhost:3003/api/products/brand/apple
    public function actionBrand($name)
    {
        $data =[];
        $min_price = Products::find()->min('price');
        $max_price = Products::find()->max('price');
        $min = Products::find()->select(['id', 'price'])->where(['=','price',$min_price])->asArray()->all();
        $max = Products::find()->select(['id', 'price'])->where(['=','price',$max_price])->asArray()->all();
        $data['max'] = $max;
        $data['min'] = $min;
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $data;

    }
}
