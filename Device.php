<?php

namespace app\models;

use yii\db\ActiveRecord;
use Yii;
use yii\helpers\Url;

/**
 * Class Users
 * @package app\models
 *
 * @property int $id
 * @property string $device_name
 * @property int $device_number
 * @property string $date
 */


    class Device extends ActiveRecord
    {
        public static function tableName()
        {
            return 'device';
        }

        public function rules()
        {
            return [
                [['device_name'], 'required'],
                [['device_name'], 'string', 'max' => 100],
                [['date'], 'safe'],
            ];
        }

        /**
         * @inheritdoc
         */
        public function attributeLabels()
        {
            return [
                'id' => 'ID',
                'device_name' => 'Device name',
                'device_number' => 'Device number',
                'date' => 'Date',
            ];
        }


        public static function checkDeviceName($deviceName)
        {
            $result = Device::find()->where(['device_name' => $deviceName])->asArray()->one();

            if ( empty($result['device_name']) ){
                return true;
            }

            return false;
        }

        public static function createDevice($deviceName, $deviceNumber)
        {
            $model = new Device();
            $model->device_name = $deviceName;
            $model->device_number = $deviceNumber;
            $model->date = date('Y-m-d G:i');
            $model->save();

            return Device::find()->where(['device_name' => $deviceName])->asArray()->one();
        }

    }