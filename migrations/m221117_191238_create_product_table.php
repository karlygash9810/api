<?php

use yii\db\Migration;
use yii\db\Schema;

/**
 * Handles the creation of table `{{%product}}`.
 */
class m221117_191238_create_product_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('product', [
            'id'                => $this->primaryKey()->comment('Идентификатор'),
            'name'           => Schema::TYPE_STRING  . ' NULL COMMENT "Продукт"',
            'category_name'           => Schema::TYPE_STRING  . ' NULL COMMENT "Категория"',
            'brand_name' => Schema::TYPE_STRING  . ' NULL COMMENT "Бренд"',
            'price'           => Schema::TYPE_INTEGER  . ' COMMENT "Прайс"',
            'rrp_price'            => Schema::TYPE_INTEGER  . ' COMMENT "ррп прайс"',
            'status'        => Schema::TYPE_SMALLINT . ' COMMENT "статус"DEFAULT "0"',
            'created_at'        => Schema::TYPE_DATETIME . ' NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT "Время создания"',
            'updated_at'        => Schema::TYPE_DATETIME . ' NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT "Время обновления"'
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('product');
    }
}
