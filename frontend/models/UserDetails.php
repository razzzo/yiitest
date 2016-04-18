<?php

namespace frontend\models;

use yii\behaviors\TimestampBehavior;
use yii\helpers\HtmlPurifier;
use common\models\User;

/**
 * This is the model class for table "{{%user_details}}".
 *
 * @property integer $user_id
 * @property string $first_name
 * @property string $last_name
 * @property string $phone
 * @property string $address
 * @property integer $city_size
 * @property integer $created_at
 * @property integer $updated_at
 *
 * @property User $user
 */
class UserDetails extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_details}}';
    }

    /**
     * Definicje behaviors modelu.
     */
    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => TimestampBehavior::className()
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['first_name', 'last_name', 'phone', 'city_size'], 'required'],
            [['city_size'], 'integer', 'min' => 1, 'max' => 4, 'tooBig' => 'Nieprawidłowa wartość.', 'tooSmall' => 'Nieprawidłowa wartość.'],
            [['first_name', 'last_name'], 'string', 'max' => 32],
            [['phone'], 'string', 'max' => 9],
            [['address'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'user_id' => 'User ID',
            'first_name' => 'Imię',
            'last_name' => 'Nazwisko',
            'phone' => 'Telefon',
            'address' => 'Adres',
            'city_size' => 'Wielkość miejscowości',
            'updated_at' => 'Ostatnio edytowany',
        ];
    }

    /**
     * @inheritdoc
     */
    public function beforeValidate()
    {
        $purifierConfig = [
            'HTML.Allowed' => '',
        ];

        $this->address = HtmlPurifier::process($this->address, $purifierConfig);

        return parent::beforeValidate();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }
}
