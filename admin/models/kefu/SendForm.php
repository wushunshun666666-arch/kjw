<?php

/**
 * 客服发送消息表单。
 *
 * 接收文字或已通过上传安全检查的媒体元数据，最终限制仍由领域层复核。
 */

namespace app\models\kefu;

use common\models\FormModel;
use common\models\kefu\KefuMessage;

/** 客服发送消息表单模型。 */
final class SendForm extends FormModel
{
    /** @var int|null 消息类型 */
    public $messageType;

    /** @var string|null 文字或表情内容 */
    public $content;

    /** @var string|null 服务端安全上传后返回的媒体标识 */
    public $mediaKey;

    /** @var string|null 客户端幂等消息编号 */
    public $clientMessageId;

    /** 返回发送消息表单验证规则。 */
    public function rules(): array
    {
        return [
            [['messageType', 'clientMessageId'], 'required'],
            [['messageType'], 'in', 'range' => [
                KefuMessage::TYPE_TEXT,
                KefuMessage::TYPE_IMAGE,
                KefuMessage::TYPE_AUDIO,
                KefuMessage::TYPE_VIDEO,
            ]],
            [['content'], 'string', 'max' => 5000],
            [['mediaKey'], 'string', 'max' => 100],
            [['clientMessageId'], 'string', 'max' => 64],
        ];
    }
}
