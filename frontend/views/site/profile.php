<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model \frontend\models\UserDetails */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = 'Mój profil';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-signup">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>Szczegółowe dane użytkownika:</p>

    <div class="row">
        <div class="col-lg-5">
            <?php $form = ActiveForm::begin(['id' => 'form-profile']); ?>

                <?= $form->field($model, 'first_name')->textInput(['autofocus' => true]) ?>

                <?= $form->field($model, 'last_name') ?>

                <?= $form->field($model, 'phone') ?>

                <?= $form->field($model, 'address')->textarea() ?>

                <?= $form->field($model, 'city_size')->dropDownList([1 => 'wieś'], ['prompt' => 'wybierz ... ']) ?>

                <div class="form-group">
                    <?= Html::submitButton('Utwórz konto', ['class' => 'btn btn-primary', 'name' => 'signup-button']) ?>
                </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
