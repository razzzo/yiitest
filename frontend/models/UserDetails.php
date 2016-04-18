<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "{{%user_details}}".
 *
 * @property integer $user_id
 * @property string $first_name
 * @property string $last_name
 * @property string $phone
 * @property string $address
 * @property integer $city_size
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
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['first_name', 'last_name', 'phone', 'city_size', 'updated_at'], 'required'],
            [['city_size', 'updated_at'], 'integer'],
            [['first_name', 'last_name'], 'string', 'max' => 32],
            [['phone'], 'string', 'max' => 9],
            [['address'], 'string', 'max' => 255],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
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
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }
}
