<?php
namespace app\models;

use yii\db\ActiveRecord;

/**
 * @package app\models
 *
 * @property int $id
 * @property int $device_id
 * @property string $description
 * @property string $date_start
 * @property string $date_end
 * @property string $record_date
 */


class Surveillance extends ActiveRecord
{
    public static function tableName()
    {
        return 'surveillance';
    }

    public function rules()
    {
        return [
            [['device_id','description', 'date_start', 'date_end'], 'required'],
            [['record_date'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'device_id' => 'Device ID',
            'description' => 'Description',
            'date_start' => 'Date start',
            'date_end' => 'Date end',
            'record_date' => 'Date',
        ];
    }

    public static function saveDescription($device_id, $description, $dateStartPeriod, $dateEndPeriod, $recordDate)
    {
        $model = new Surveillance();
        $model->device_id = $device_id;
        $model->description = $description;
        $model->date_start = $dateStartPeriod;
        $model->date_end = $dateEndPeriod;
        $model->record_date = $recordDate;
        $model->save();



        //return Device::find()->where(['device_name' => $deviceName])->asArray()->one();
        return true;
    }

    public static function getDescription($device_id){
        $result = Surveillance::find()->where(['device_id' => $device_id])->orderby(['id'=>SORT_DESC])->asArray()->all();

        return $result;
    }
/*
UPDATE data `temperature` SET `temperature`=3
WHERE date = "2022-11-12 12:25:49" */

}