<?php

use yii\db\Migration;

class m230115_069807_create_cart_items_table extends Migration
{


    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%cart_items}}', [
            'id' => $this->primaryKey(),

            'product_id' => $this->integer(11)->notNull(),

            'quantity' => $this->integer(2)->notNull(),
            'created_by' => $this->integer(11)

        ]);

        // creates index for column `product_id`
        $this->createIndex(
            '{{%idx-cart_items-product_id}}',
            '{{%cart_items}}',
            'product_id'
        );

        // add foreign key for table `{{%product}}`
        $this->addForeignKey(
            '{{%fk-cart_items-product_id}}',
            '{{%cart_items}}',
            'product_id',
            '{{%products}}',
            'id',
            'CASCADE'
        );

        $this->createIndex(
            '{{%idx-cart_items-created_by}}',
            '{{%cart_items}}',
            'created_by'
        );
        $this->addForeignKey(
            '{{%fk-cart_items_created_by}}',
            '{{%cart_items}}',
            'created_by',
            '{{%user}}',
            'id',
            'CASCADE'
        );




    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // drops foreign key for table `{{%products}}`
        $this->dropForeignKey(
            '{{%fk-cart_items-product_id}}',
            '{{%cart_items}}'
        );

        // drops index for column `product_id`
        $this->dropIndex(
            '{{%idx-cart_items-product_id}}',
            '{{%cart_items}}'
        );

        $this->dropTable('{{%cart_items}}');
    }

}