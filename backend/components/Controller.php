<?php
/**
 * @link http://www.razzo.pl/
 * @copyright Copyright (c) 2015 Razzo
 */

namespace backend\components;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller as BaseController;

/**
 * Klasa bazowa kontrolera w backend.
 * W metodzie defaultAccessBehaviors() definiuje zestaw domyślnych reguł dla filtra `accessControl`.
 *
 * @author Piotr Mróz <mroz.piotrek@gmail.com>
 */
class Controller extends BaseController
{
    /**
     * Wszystkie klasy kontrolerów które dziedziczą z klasy tego domyślnego kontrolera
     * powinny w metodzie behaviors(); dla behaviora `access` użyć zapisu:
     * ```
     * public function behaviors()
     * {
     *     return [
     *         'access' => parent::defaultAccessBehaviors([
     *             'class' => yii\filters\AccessControl::className(),
     *             'rules' => [
     *                 ['actions' => ['basic'], 'roles' => ['configBasic'], 'allow' => true],
     *             ],
     *         ])
     *     ];
     * }
     * ```
     * a więc jako parametr podać zawartość tablicy 'access' określającej dostęp do danego kontrolera i akcji.
     * Tablice zostaną połączone a domyślne uprawnienia będą sprawdzane zawsze jako pierwsze.
     *
     * Poniższe ustawienia umożliwiają dostęp użytkownikom z uprawnieniem 'admin'.
     * Reguła akcji `error` musi wystąpić przed regułą `matchCalback` aby móc poprawnie wyświetlić akcję `error` gdy np.
     * użytkownik nie będzie miał dostępu do aplikacji `backend` (brak uprawnienia `admin`) lub uprawnienie zostanie mu
     * zabrane w trakcie pracy (gdy będzie zalogowany).
     *
     * @param array $controllerAccessRules reguły dostępu kontrolera który dziedziczy z tej klasy.
     * @return array reguły dla kontrolera dziedziczącego połączone z domyślnymi regułami
     */
    public function defaultAccessBehaviors($controllerAccessRules)
    {
        return \yii\helpers\ArrayHelper::merge([
            'class' => AccessControl::className(),
            'rules' => [
                [
                    'actions' => ['error', 'login', 'logout'],
                    'allow' => true,
                ],
                [
                    'matchCallback' => function ($rule, $action) {
                        return !Yii::$app->user->can('admin');
                    },
                    'allow' => false,
                ],
            ],
        ], $controllerAccessRules);
    }
}
