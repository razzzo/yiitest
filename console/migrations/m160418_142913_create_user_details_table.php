<?php

use yii\db\Migration;

class m160418_142913_create_user_details_table extends Migration
{
    public function up()
    {
        $this->createTable('user_details', [
            'user_id' => $this->primaryKey(),
            'first_name' => $this->string(32)->notNull() . " COMMENT 'Imię'",
            'last_name' => $this->string(32)->notNull() . " COMMENT 'Nazwisko'",
            'phone' => $this->string(9)->notNull() . " COMMENT 'Telefon'",
            'address' => $this->string() . " COMMENT 'Adres'",
            'city_size' => $this->integer()->notNull() . " COMMENT 'Wielkość miejscowości'",
            'created_at' => $this->integer()->notNull() .  " COMMENT 'Utworzony'",
            'updated_at' => $this->integer()->notNull() .  " COMMENT 'Edytowany'",
        ]);

        $this->addForeignKey('fk_user-details_user', '{{%user_details}}', 'user_id', '{{%user}}', 'id', 'CASCADE', 'CASCADE');
    }

    public function down()
    {
        $this->dropTable('user_details');
    }
}
