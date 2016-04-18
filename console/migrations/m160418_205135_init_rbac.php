<?php

use yii\db\Migration;
use common\models\User;

include(Yii::getAlias('@yii/rbac/migrations/m140506_102106_rbac_init') . '.php');

/**
 * Migracja uruchamia migrację '@yii/rbac/migrations/m140506_102106_rbac_init', która tworzy tabele dla
 * komponentu RBAC oraz przypisuje wszystkie uprawnienia użytkownikowi 'serwis'.
 *
 * @author Piotr Mróz <mroz.piotrek@gmail.com>
 */
class m160418_205135_init_rbac extends Migration
{

    public function up()
    {
        $rbac = new m140506_102106_rbac_init();
        $rbac->up();

        $this->createPermissions();
    }

    public function down()
    {
        $rbac = new m140506_102106_rbac_init();
        $rbac->down();
    }

    /**
     * Użytkownik 'serwis'->'mroz.piotrek@gmail.com' otrzymuje wszystkie
     * uprawnienia (są one dziedziczone przez rolę 'root').
     * Użytkownik 'serwis' może tworzyć nowych użytkowników w systemie
     * i przydzielać im odpowiednie uprawnienia.
     */
    private function createPermissions()
    {
        $auth = Yii::$app->authManager;

        // tworzę uprawnienie 'admin'
        $perm = $auth->createPermission('admin');
        $perm->description = 'Dostęp do panelu administratora';
        $auth->add($perm);

        // przypisuję rolę 'admin' użytkownikowi 'admin'
        $admin = User::findOne(['username' => 'admin']);
        if ($admin != null) {
            $auth->assign($perm, $admin->id);
        }
    }
}
