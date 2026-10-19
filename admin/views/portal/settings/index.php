<?php
/** 站点与广告配置完整表单。@var app\models\portal\settings\SettingsForm $model */
use app\helpers\Html;
use app\helpers\Url;
use app\widgets\ActiveForm;
use common\resource\PortalImageResource;
use common\widgets\FileUploadWidget;
use yii\helpers\Json;
use yii\web\JsExpression;

$uploadOptions = ['action' => Url::to(['upload']), 'extensions' => PortalImageResource::EXTENSIONS,
    'maxSize' => PortalImageResource::MAX_SIZE, 'hiddenInputs' => [Yii::$app->request->csrfParam => Yii::$app->request->getCsrfToken()]];
?>
<?= \app\widgets\TabMenu::widget(['menus' => ['站点与广告配置' => ['index'], '开奖记录' => ['/portal/draw/index']], 'active' => '站点与广告配置']) ?>
<div class="card shadow-sm mb-3"><div class="card-header"><h1 class="card-title h6 mb-0">站点与广告配置</h1></div>
<?php $form = ActiveForm::begin(['id' => 'portal-settings-form', 'layout' => 'horizontal', 'validationUrl' => ['check']]); ?>
<div class="card-body">
<?= $form->field($model, 'landing')->textInput(['maxlength' => 2048, 'placeholder' => 'https://'])->hint('前台“登录或注册！”与登录二维码使用此地址。') ?>
<?= $form->field($model, 'show_qrcode')->dropDownList([1 => '显示', 0 => '隐藏']) ?>
<?= $form->field($model, 'website')->textInput(['maxlength' => 128]) ?>
<?= $form->field($model, 'title')->textInput(['maxlength' => 128]) ?>
<?= $form->field($model, 'notice_scroll')->textarea(['rows' => 2]) ?>
<?= $form->field($model, 'ad_wx')->textInput(['maxlength' => 2048, 'placeholder' => 'https://']) ?>
<?= $form->field($model, 'ad_ww')->textInput(['maxlength' => 2048, 'placeholder' => 'https://']) ?>
<?php foreach (['banner', 'icon', 'ad_icon'] as $attribute): $inputId = Html::getInputId($model, $attribute); ?>
<div class="mb-3 portal-image-field">
<?= $form->field($model, $attribute)->hiddenInput()->label() ?>
<?= Html::img($model->$attribute ? Url::to(['image', 'reference' => $model->$attribute]) : '', ['id' => $inputId . '-preview', 'alt' => $model->getAttributeLabel($attribute), 'style' => 'max-width:100%;max-height:110px;' . ($model->$attribute ? '' : 'display:none;')]) ?>
<?= FileUploadWidget::widget($uploadOptions + ['buttonContent' => '上传' . $model->getAttributeLabel($attribute), 'clientOptions' => [
    'before' => new JsExpression("function() { $('#portal-settings-form').trigger('portalUploadStart'); return true; }"),
    'error' => new JsExpression("function() { $('#portal-settings-form').trigger('portalUploadEnd'); }"),
    'success' => new JsExpression("function(data) { $('#portal-settings-form').trigger('portalUploadEnd'); $('#{$inputId}').val(data.reference).trigger('change'); $('#{$inputId}-preview').attr('src',data.url).show(); }")]]) ?>
<?php if ($attribute === 'ad_icon'): ?><?= Html::button('清除联系图标', ['type' => 'button', 'class' => 'btn btn-sm btn-light', 'id' => 'portal-clear-contact-icon']) ?><?php endif; ?>
</div>
<?php endforeach; ?>
<hr><h2 class="h6">广告图片与跳转链接</h2>
<p class="text-muted">支持 JPG、PNG、GIF 动图和 WebP，单张不超过 5 MB。可设置最多 20 条广告，按下方顺序显示；关闭的广告不会在前台展示。上传后点击“保存配置”才生效。</p>
<?= $form->field($model, 'ads_json')->hiddenInput()->label(false) ?>
<div id="portal-ad-rows"></div>
<?= Html::button('添加广告', ['type' => 'button', 'class' => 'btn btn-outline-primary', 'id' => 'portal-ad-add']) ?>
<?= $form->field($model, 'version')->hiddenInput()->label(false) ?>
</div><div class="card-footer bg-transparent">
<?= Html::button('保存配置', ['type' => 'button', 'class' => 'btn btn-primary', 'id' => 'portal-settings-submit']) ?>
</div><?php ActiveForm::end(); ?></div>
<script>
seajs.use('admin.portal-settings', function (settings) {
    settings.init(<?= Json::htmlEncode($uploadOptions) ?>, <?= Json::htmlEncode(Url::to(['image'])) ?>);
});
</script>
