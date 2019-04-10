<?php

namespace app\modules\forms\models\forms;

use app\modules\forms\models\settings\TargetUrl;
use app\modules\forms\models\settings\CrmFields;
use app\modules\forms\models\settings\MailFields;
use app\modules\forms\models\settings\FormFields;
use app\modules\forms\models\turn\GroupsManagersSearch;
use yii\helpers\ArrayHelper;
use Yii;

class BaseForm extends \yii\base\Model {

    const NAME_EMPTY = "Без имени ";
    const TITLE_EMPTY = "заявка с сайта";

    public $name;
    public $phone;
    public $url;
    public $target;
    public $utm_source;
    public $utm_medium;
    public $utm_campaign;
    public $utm_term;
    public $utm_content;

    public function rules() {
        return [
            [['name', 'target', 'url', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'], 'string'],
            ['phone', 'required', 'message' => 'Пожалуйста, введите телефонный номер'],
            ['phone', 'match', 'pattern' => '/^[+]{1}[0-9]{11}$/', 'message' => ' Пожалуйста введите правильный номер телефона'],
        ];
    }

    public function send($obB24App, $formSettings) {

        $managerId = GroupsManagersSearch::getNextManager($formSettings->igroup_id);
        $crmFields = CrmFields::find()->where(['iforms_id' => $formSettings->iid])->andWhere(['ctype' => $formSettings->ccrm])->all();
        $formFields = FormFields::find()->where(['iform_id' => $formSettings->iid])->all();
        $crmFieldsArray = $this->fieldsPars($crmFields);
        $mailFields = MailFields::find()->where(['iforms_id' => $formSettings->iid])->all();
        $mailFieldsArray = $this->fieldsPars($mailFields);

        $arrTargetUrl = $this->targetUrl;
        $nameText = $this->nameText;
        $phoneArray = $this->phoneArray;
        $titleText = $this->parsTitleText($arrTargetUrl->cname, $this->phone);
        $contactFieldsArray = [
            "NAME" => $nameText,
            "PHONE" => $phoneArray,
            "SOURCE_ID" => $arrTargetUrl->csource_id,
        ];
        $baseFieldsArray = [
            "TITLE" => $titleText,
            "ASSIGNED_BY_ID" => $managerId,
            "SOURCE_ID" => $arrTargetUrl->csource_id,
            "UTM_TERM" => $this->utm_term,
            "UTM_SOURCE" => $this->utm_source,
            "UTM_MEDIUM" => $this->utm_medium,
            "UTM_CONTENT" => $this->utm_content,
            "UTM_CAMPAIGN" => $this->utm_campaign,
        ];
        $setTo = explode(",", $arrTargetUrl->cmail);
        if (\Yii::$app->controller->module->params['mail_test']) {
            array_push($setTo, \Yii::$app->controller->module->params['mail_test']);
        }
        if ($formSettings->bemail) {
            if ($arrTargetUrl->cmail) {
                Yii::$app->mailer->compose()
                        ->setFrom(\Yii::$app->controller->module->params['mail'])
                        ->setTo($setTo)
                        ->setSubject($mailFieldsArray['TITLE'])
                        ->setTextBody('Текст сообщения')
                        ->setHtmlBody($mailFieldsArray['BODY'])
                        ->send();
            }
        }

        switch ($formSettings->ccrm) {
            case 'none':
                //return 'не создавать';
                break;
            case 'lead':
                //return 'Лид';
                $leadFieldsArray = array_merge($baseFieldsArray, [$crmFieldsArray, $contactFieldsArray]);
                $obB24Lead = new \Bitrix24\CRM\Lead($obB24App);
                $lead = $obB24Lead->add($leadFieldsArray);
                if (!$lead) {
                    Yii::error('создать Лид не удалось', __METHOD__);
                    Yii::warning($this, __METHOD__);
                }
                break;
            case 'deal':
                //return 'Сделка';                
                $secondaryContactFieldsArray = [
                    'ASSIGNED_BY_ID' => $managerId,
                    'TYPE_ID' => 'CLIENT',
                ];
                $obB24Contact = new \Bitrix24\CRM\Contact($obB24App);
                $contact = $obB24Contact->add(array_merge($contactFieldsArray, $secondaryContactFieldsArray), ['REGISTER_SONET_EVENT' => 'Y']);
                $dealFieldsArray = array_merge($baseFieldsArray, $crmFieldsArray, ['CONTACT_ID' => $contact['result']]);
                $obB24Deal = new \Bitrix24\CRM\Deal\Deal($obB24App);
                $deal = $obB24Deal->add($dealFieldsArray);
                if (!$deal) {
                    Yii::error('создать сделку не удалось', __METHOD__);
                    Yii::warning($this, __METHOD__);
                }
                break;
            default :
                //return 'не создавать';
                break;
        }
    }

    public function getTargetUrl() {
        if (TargetUrl::find()->where(['ctarget_url' => $this->target . '_' . $this->url])->count()) {
            return TargetUrl::find()->where(['ctarget_url' => $this->target . '_' . $this->url])->one();
            ;
        } else {
            return TargetUrl::find()->where(['ctarget_url' => 'default'])->one();
            ;
        }
    }

    protected function getNameText() {
        if ($this->name) {
            return $this->name;
        } else {
            return 'Без имени';
        }
    }

    protected function getPhoneArray() {
        if (!$this->phone) {
            return [];
        }
        $phoneArray = array(array("VALUE" => $this->phoneText, "VALUE_TYPE" => "WORK"));
        return $phoneArray;
    }

    protected function parsTitleText($title = '', $phone = '') {
        $name = '';
        if ($this->name) {
            $name = $this->name;
        } else {
            $name = self::NAME_EMPTY;
        }

        $text = '';
        if ($phone) {
            $text = $name . ' ' . $phone . ' ';
        } else {
            $text = $name . ' ';
        }

        return $text . ' ' . self::TITLE_EMPTY;
    }

    protected function getPhoneText() {
        $phoneText = "+" . preg_replace("/[^0-9]/", '', $this->phone);
        return $phoneText;
    }

    protected function parsFieldsToText($param) {
        $text = '';
        foreach ($param as $key => $value) {
            $text .= /* $key.': '. */$value . '<br>';
        }
        return $text;
    }

    protected function generateEmailBodyText($fields) {
        $text = 'Имя: ' . $this->nameText . '<br>';
        $text .= 'Телефон: ' . $this->phoneText . '<br>';
        $text .= 'Страница: ' . $this->targetUrl->cname . '<br>';
        $text .= $this->parsFieldsToText($fields);
        return $text;
    }

    private function fieldsPars($fields) {
        $res = [];
        foreach (ArrayHelper::map($fields, 'cfields_type', 'ctext', 'cfield') as $key => $value) {
            $res[$key] = $this->parsTemplate($value);
        }
        return $res;
    }

    private function parsTemplate($arr) {
        if (array_key_exists('string', $arr)) {
            $string = $arr['string'];
            //$string = 'ghd fh fg f {=name} bxvhbxv bxv {=event}{=name} g bvbxvhb{=name}';
            preg_match_all('/{=\w+}/', $string, $code);
            $str = preg_split('/{=\w+}/', $string);
            $i = 1;
            $res = $str[0];
            while ($i < count($str)) {
                $res .= $this->{substr($code[0][$i - 1], 2, -1)};
                $res .= $str[$i];
                $i++;
            }
            return $res;
        }
        if (array_key_exists('date', $arr)) {
            $date_string = $arr['date'];
            //$string = 'ghd fh fg f {=name} bxvhbxv bxv {=event}{=name} g bvbxvhb{=name}';
            preg_match_all('/{=\w+}/', $date_string, $code);
            if (count($code) != 1) {
                return false;
            }
            $res = date($this->{substr($code[0][0], 2, -1)});
            return $res;
        }
        return false;
    }

}
