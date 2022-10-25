<?php

namespace app\controllers;

use app\models\Device;
use app\models\Data;
use Yii;
use yii\filters\Cors;
use yii\web\Controller;





class ApiController extends Controller
{
    public $enableCsrfValidation = false; // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

    public function behaviors()
    {
        return [
            'corsFilter' => [
                'class' => Cors::class,
                'cors' => [
                    'Origin' => ['*'],
                    // Allow only POST and PUT methods
                    'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                    // Allow only headers 'X-Wsse'
                    'Access-Control-Request-Headers' => ['*'],
                    // Allow credentials (cookies, authorization headers, etc.) to be exposed to the browser
                    'Access-Control-Allow-Credentials' => false,
                    // Allow OPTIONS caching
                    'Access-Control-Max-Age' => 3600,
                    // Allow the X-Pagination-Current-Page header to be exposed to the browser.
                    'Access-Control-Expose-Headers' => [],
                ],

            ],
        ];
    }


    public function actionGetData()
    {
        $allData = Device::find()
            ->asArray()
            ->all();

        return json_encode($allData);
    }


    public function actionDeviceData()
    {
        if (YII::$app->request->get()) {
            $params = Yii::$app->request->getBodyParams();

            $device_name = $params['device_name'];

            foreach ($params['packages'] as $item)
            {
                $model = new Data();

                foreach ($item as $key => $value)
                {
                    if ($key === 'date'){
                        $date_ = substr($value, 0, 16);
                        //$time = substr($value, 11, 8);
                        $model->$key = $date_;
                        $model->date_default = $value;
                    }else {
                        $model->$key = $value;
                    }
                }
                    $device = Device::find()->where(['device_name' => $device_name])->asArray()->one();
                    $model->device_id = $device['id'];

                //$model->date = date('Y-m-d G:i');    // дата по замовчуванню;
                $model->save(false);
            }
            return json_encode(['result' => 'ok']);
        }
        return json_encode(['result' => 'error']);
    }


    public function actionDeviceDataArduino()
    {
        if (YII::$app->request->get()) {
            $params = Yii::$app->request->getBodyParams();

            $device_name = $params['device_name'];

            $time = time();
            $startTime = $time - count($params['packages']);
            $dataInsert = array();
            $tableName = 'data';
            $x = 0;

            foreach ($params['packages'] as $item)
            {
                $item['date'] = date('Y-m-d H:i:s', $startTime);
                $startTime++;

                //$model = new Data();

                foreach ($item as $key => $value)
                {
                    if ($key === 'date'){
                        $date_ = substr($value, 0, 16);
                            //$time = substr($value, 11, 8);
                            // $model->$key = $date_;
                        $dataInsert[][$x] = date('Y-m-d G:i', strtotime($date_. " + 2 hour"));
                        $dataInsert[][$x] = $value;
                    }else {
                        $dataInsert[][$x] = $value;
                    }
                }
                $device = Device::find()->where(['device_name' => $device_name])->asArray()->one();
                $dataInsert[][$x] = $device['id'];

                $x++;
                // $model->save(false);
            }

            $insertCount = 0; // чи потрібно ?
            if(count($dataInsert)>0){
                $columnNameArray=['rotation', 'acceleration', 'date', 'device_id'];
                // below line insert all your record and return number of rows inserted
                $insertCount = Yii::$app->db->createCommand()
                    ->batchInsert(
                        $tableName, $columnNameArray, $dataInsert
                    )
                    ->execute();
            }

            return json_encode(['result' => 'ok', '$insertCount' => $insertCount]);
        }
        return json_encode(['result' => 'error']);
    }


    public function actionDeviceDataArduino_ОК()
    {
        if (YII::$app->request->get()) {
            $params = Yii::$app->request->getBodyParams();

            $device_name = $params['device_name'];

            $time = time();
            $startTime = $time - count($params['packages']);

            foreach ($params['packages'] as $item)
            {
                $item['date'] = date('Y-m-d H:i:s', $startTime);
                $startTime++;

                $model = new Data();

                foreach ($item as $key => $value)
                {
                    if ($key === 'date'){
                        $date_ = substr($value, 0, 16);
                        //$time = substr($value, 11, 8);
                        // $model->$key = $date_;
                        $model->$key = date('Y-m-d G:i', strtotime($date_. " + 2 hour"));
                        $model->date_default = $value;
                    }else {
                        $model->$key = $value;
                    }
                }
                $device = Device::find()->where(['device_name' => $device_name])->asArray()->one();
                $model->device_id = $device['id'];

                //$model->date = date('Y-m-d G:i');    // дата по замовчуванню;
                $model->save(false);
            }
            return json_encode(['result' => 'ok']);
        }
        return json_encode(['result' => 'error']);
    }


    public function actionBuildChart()
    {
        if (YII::$app->request->get()) {
            $params = Yii::$app->request->getBodyParams();

            $result = array();
            $new_array_params = array();

            if ( isset($params['startPeriod']) ){
                // якщо є заданий період
                $new_array_params = self::actionBuildChartParams($params);

            } else {
                // вибір даних за кількістю записів в БД
                $result = Data::getDataDeviceLimit( $params['device_id'], $params['limit'], date('Y-m-d G:i', $params['date']/1000));
                // перезаписується масив у зворотному порядку
                $new_array_params = array_reverse($result);
            }


            // якщо даних немає
            if (!$new_array_params){
                return json_encode( $x=['result'=>'array_empty', 'visual'=>$params['visualSwitch']]);
            }


            // пошук провалів в записі даних, чи є 60 записів в 1 хвилину
            $incomplete_data = self::actionIncompleteData($new_array_params);


            // розділення строк на числа
            $new_array_data = Data::getDataParse($new_array_params);

            // сума модулів
            if ($params['visualSwitch'] === 'module')
            {
                foreach ($new_array_data as $key=>$value){
                    $new_array_data[$key]['acceleration'] = Data::sumModule($value['acceleration']);
                    $new_array_data[$key]['rotation'] = Data::sumModule($value['rotation']);
                    $new_array_data[$key]['gravity'] = Data::sumModule($value['gravity']);
                }
            }

            // якщо все добре відправляється масив даних
            return  json_encode( $x=['newData'=>$new_array_data, 'visual'=>$params['visualSwitch'], 'incomplete_data'=>$incomplete_data,'date'=>date('Y-m-d G:i', $params['date']/1000)] );
        }else {
            // якщо не вдалося отримати запит  'data'=>$new_array_params,
            return json_encode( $x=['result'=>'array_error']);
        }
    }



    public static function actionBuildChartParams($params)
    {
        $startPeriod = $params['startPeriod'];
        $endPeriod = $params['endPeriod'];
        $deviceId = $params['device_id'];

        $newDateStartPeriod = date('Y-m-d H:i:s', strtotime($startPeriod ));
        $newDateEndPeriod = date('Y-m-d H:i:s', strtotime($endPeriod ));
        $resultData = Data::getDeviceDateParams($newDateEndPeriod, $newDateStartPeriod, $deviceId);

        // якщо даних немає
        if (count($resultData) === 0){
            return json_encode( $x=['result'=>'array_empty', 'visual'=>$params['visualSwitch']]);
        } else {
            return $resultData;
        }
    }


    public static function actionIncompleteData($array)
    {
        $count = 0;
        $array_incomplete = array();
        $newDate = '';
        $x = true;

        foreach($array as $key=>$value)
        {
            if ($x){
                $newDate = date('Y-m-d H:i', strtotime( $value['date'] ));
                $array_incomplete['date_s'] = $newDate;
                $array_incomplete['$key'] = date('Y-m-d H:i', strtotime( $value['date'] ));
                $x = false;
            }

            if ($newDate === date('Y-m-d H:i', strtotime( $value['date'] ))) {
                $count++;
                $array_incomplete[$key] = $count;
            } else {
                $array_incomplete['problems'.$key] = $newDate.'  всього записів - '.$count;
                $newDate = date('Y-m-d H:i', strtotime($newDate.'+ 1 minutes'));
                $array_incomplete['date_posle+1'] = $newDate;
                $count = 0;
            }

        }

        return $array_incomplete;
    }




    public function actionCreateDevice()
    {
        // отримуємо данні девайса
        if (YII::$app->request->get()) {
            $params = Yii::$app->request->getBodyParams();

            // перевірка чи існує така назва девайсу
            if (!Device::checkDeviceName($params['deviceName']) ){
                return json_encode(['CreateDevice' => 'deviceName already exists']);
            }

            $deviceName = $params['deviceName'];
            $deviceNumber = $params['deviceNumber'];
            $result = Device::createDevice($deviceName, $deviceNumber);

            return json_encode($result);
        }
        return json_encode(['CreateDevice' => 'error']);
    }

}
