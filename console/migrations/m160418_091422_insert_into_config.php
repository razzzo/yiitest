<?php

use yii\db\Migration;

class m160418_091422_insert_into_config extends Migration
{
    public function up()
    {
        $this->insert('{{%config}}', ['category' =>'mail', 'key' =>'smtpHost', 'value' => serialize('smtp.razzo.pl')]);
        $this->insert('{{%config}}', ['category' =>'mail', 'key' =>'smtpPort', 'value' => serialize(587)]);
        $this->insert('{{%config}}', ['category' =>'mail', 'key' =>'auth', 'value' => serialize(1)]);
        $this->insert('{{%config}}', ['category' =>'mail', 'key' =>'username', 'value' => serialize('maileryiitest@razzo.pl')]);
        $this->insert('{{%config}}', ['category' =>'mail', 'key' =>'password', 'value' => serialize('0fACQ68R95')]);
        $this->insert('{{%config}}', ['category' =>'mail', 'key' =>'fromEmail', 'value' => serialize('maileryiitest@razzo.pl')]);
        $this->insert('{{%config}}', ['category' =>'mail', 'key' =>'fromName', 'value' => serialize('Warsztat Yii2')]);
        $this->insert('{{%config}}', ['category' =>'mail', 'key' =>'replyToEmail', 'value' => serialize('kontakt@warsztatyii2.pl')]);
        $this->insert('{{%config}}', ['category' =>'mail', 'key' =>'replyToName', 'value' => serialize('Obsługa warsztatu')]);
    }

    public function down()
    {
        $this->delete('{{%config}}');
    }
}
