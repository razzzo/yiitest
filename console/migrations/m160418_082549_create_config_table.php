<?php

use yii\db\Migration;

class m160418_082549_create_config_table extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%config}}', [
            'id' => $this->primaryKey(),
            'category' => $this->string()->notNull()->defaultValue('app'),
            'key' => $this->string()->notNull(),
            'value' =>$this->string()->notNull()
        ], $tableOptions);

        $this->createIndex('category_key', '{{%config}}', 'category, key');
    }

    public function down()
    {
        $this->dropIndex('category_key', '{{%config}}');

        $this->dropTable('{{%config}}');
    }
}
