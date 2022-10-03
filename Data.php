<?php
namespace app\models;

use yii\base\Exception;
use yii\db\ActiveRecord;
use Yii;
use yii\helpers\Url;

/**
 * Class Users
 * @package app\models
 *
 * @property int $id
 * @property int $device_id
 * @property int $steps
 * @property string $location
 * @property string $acceleration
 * @property string $rotation
 * @property string $heading
 * @property string $gravity
 * @property string $date
 * @property string $date_default
 * @property string $timestamp
 */


class Data extends ActiveRecord
{

    public static function tableName()
    {
        return 'data';
    }

    public function rules()
    {
        return [
            [['device_id','steps'], 'required'],
            [['location', 'acceleration', 'rotation', 'heading', 'gravity'], 'string', 'max' => 100],
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
            'device_id' => 'Device ID',
            'steps' => 'Steps',
            'location' => 'location',
            'acceleration' => 'acceleration',
            'rotation' => 'rotation',
            'heading' => 'heading',
            'gravity' => 'gravity',
            'date' => 'Date',
        ];
    }

    public static function deviceDateParams($newDateEndPeriod, $newDateStartPeriod, $deviceId)
    {
        return Data::find()
            ->where (['device_id' => $deviceId])
            ->andwhere (['between', 'date', $newDateStartPeriod, $newDateEndPeriod])
            ->orderby(['date'=>SORT_ASC])
            ->asArray()
            ->all();
    }
}