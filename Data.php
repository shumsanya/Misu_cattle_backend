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

    public static function getDeviceDateParams($dateEndPeriod, $dateStartPeriod, $deviceId)
    {
        return Data::find()
            ->where (['device_id' => $deviceId])
            ->andwhere (['between', 'date', $dateStartPeriod, $dateEndPeriod])
            ->orderby(['date'=>SORT_ASC])
            ->asArray()
            ->all();
    }


    public static function getDataDeviceLimit($device_id, $limit, $date)
    {
        if ($limit === 1){
            $dateEndPeriod = $date; // date('Y-m-d G:i', strtotime($date. " + 3 hour"));
            $dateStartPeriod = date('Y-m-d G:i', strtotime($dateEndPeriod. " - 1 hour"));
            return Data::find()
                ->where (['device_id' => $device_id])
                ->andwhere (['between', 'date', $dateStartPeriod, $dateEndPeriod])
                ->orderby(['id'=>SORT_DESC])
                ->asArray()
                ->all();
        }

        return Data::find()
            ->where(['device_id' => $device_id])
            ->orderby(['id'=>SORT_DESC])
            ->limit($limit)
            ->asArray()
            ->all();
    }


    public static function getDataParse($new_array_params){

        $array = array();

        foreach ($new_array_params as $key=>$value)
        {
            $array[$key]['acceleration'] = explode(' ', $value['acceleration']);
            $array[$key]['rotation'] = explode(' ', $value['rotation']);
            $array[$key]['gravity'] = explode(' ', $value['gravity']);

            if ( date('Y-m-d', strtotime($value['date'])) === date('Y-m-d') ){
                $array[$key]['label'] = 'сьогодні в '. date('G:i', strtotime($value['date']));
            }else{
                $array[$key]['label'] = date('d-m-Y G:i', strtotime($value['date']));
            }
        }
        return $array;
    }


    public static function sumModule($array_result){

        $sum = 0;

        foreach ($array_result as $key=>$value)
        {
            $float_value_of_var = floatval($value);
            $sum += abs($float_value_of_var);
        }

        return $sum;
    }

}