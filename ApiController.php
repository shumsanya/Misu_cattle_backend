<?php

namespace app\controllers;

use app\models\Device;
use app\models\SignupForm;
use app\models\LoginForm;
use app\models\Data;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use Yii;
use yii\base\InvalidConfigException;
use yii\filters\Cors;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\Controller;
use yii\rest\ActiveController;


class ApiController extends Controller
{

    /*DebugPanel::addData(\Yii::$app->request->post());
    DebugPanel::addData($user->getErrors());*/

    public $enableCsrfValidation = false; // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

    public function behaviors()
    {
        return [
            'corsFilter' => [
                'class' => Cors::class,
                'cors' => [
                    // restrict access to
                    //'Origin' => ['http://localhost:3000/about'],
                    'Origin' => ['*'],
                    // Allow only POST and PUT methods
                    //'Access-Control-Request-Origin' => 'http://localhost:3000/about',
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
        $result = array();
        $stepsArray = array();
        $allData = array();
        $sumSteps = 0;

        /*$sql = 'SELECT DISTINCT device_id FROM data';

        $arr['number'] = Data::findbysql($sql)->all();

        $quantity = count($arr['number']);

        for ($i = 1; $i <= $quantity; $i++) {

            $result[$i] = Data::find()->where(['device_id' => $i])->asArray()->all();

            foreach ($result[$i] as $steps) {
                $sumSteps += $steps['steps'];
            }

            $stepsArray[$i] = $sumSteps;
            $sumSteps = 0;
        }

        $allData = [[$result], [$stepsArray]];*/


        $allData = Device::find()
            //->select('id, device_id, device_name')
            ->asArray()
            ->all();

        return json_encode($allData);
    }


    public function actionDeviceData()
    {

        if (YII::$app->request->get()) {
            $params = Yii::$app->request->getBodyParams();

            $model = new Data();


            $device_name = $params[array_key_first($params)];

            foreach ($params['package'] as $item)
            {
                foreach ($item as $key => $value)
                {
                    $model->$key = $value;
                }

                if (array_key_first($params) === 'device_name') {
                    $device = Device::find()->where(['device_name' => $device_name])->asArray()->one();
                    $model->device_id = $device['id'];
                }

                //$model->date = date('Y-m-d G:i');    // дата по замовчуванню;
                $model->save(false);
            }
            return json_encode(['actionTestData' => 'ok']);
        }
        return json_encode(['actionTestData' => 'error']);
    }


    public function actionTestData()
    {
        if (YII::$app->request->get()) {
            $params = Yii::$app->request->getBodyParams();

            $device_name = $params[array_key_first($params)];

            foreach ($params['package'] as $item)
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

                if (array_key_first($params) === 'device_name') {
                    $device = Device::find()->where(['device_name' => $device_name])->asArray()->one();
                    $model->device_id = $device['id'];
                }

                //$model->date = date('Y-m-d G:i');    // дата по замовчуванню;
                $model->save(false);
            }

            return json_encode(['actionTestData' => 'ok']);
        }

        return json_encode(['$method' => 'error']);
    }


    public function actionBuildChart()
    {
        // отримуємо id девайса
        if (YII::$app->request->get()) {
            $device_id = Yii::$app->request->getBodyParams();

            $params = Data::find()
                ->where(['device_id' => $device_id['device_id']])
                ->asArray()
                ->all();

            $masLabels = array();

            foreach ($params as $key=>$value){

                if ( date('Y-m-d', strtotime($value['date_default'])) === date('Y-m-d') ){
                    $masLabels[] = 'сьогодні в '. date('G:i', strtotime($value['date']));
                }else{
                    $masLabels[] = date('d-m-Y G:i', strtotime($value['date']));
                }

            }
            return  json_encode( $x=['data'=>$params, 'label'=> $masLabels]);
        }else {
            return json_encode( $x=['result'=>'array_empty']);
        }
    }

    public function actionBuildChartParams()
    {
        // отримуємо id девайса
        if (YII::$app->request->get()) {
            $params = Yii::$app->request->getBodyParams();

            $startPeriod = $params['startPeriod'];
            $endPeriod = $params['endPeriod'];
            $deviceId = $params['device_id'];
            //$param = $params['param'];

            // добавляємо та віднімаємо 1 день для адекватної виборки
            $newDateStartPeriod = date('Y-m-d', strtotime($startPeriod."-1 day" ));
            $newDateEndPeriod = date('Y-m-d', strtotime($endPeriod."+1 day" ));
            $resultData = Data::deviceDateParams($newDateEndPeriod, $newDateStartPeriod, $deviceId);

            $masLabels = array();

            foreach ($resultData as $key=>$value){

                    // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!  строку нужно
                    if ( date('Y-m-d', strtotime($value['date_default'])) === date('Y-m-d') ){
                        $masLabels[] = 'сьогодні в '. date('G:i', strtotime($value['date']));
                    }else{
                        $masLabels[] = date('d-m-Y G:i', strtotime($value['date']));
                    }

            }


            // если масив result пуст (в заданом промежутке времени нету данных)
            if (empty($resultData)){
                return json_encode(['result' => 'array_empty']);
            } else {
                return json_encode( $x=['data'=>$resultData, 'label'=> $masLabels]);
            }


        }

        return json_encode(['result' => 'error_getBodyParams']);
    }


    public function actionCreateDevice()
    {
        // отримуємо данні девайса
        if (YII::$app->request->get()) {
            $params = Yii::$app->request->getBodyParams();

            $deviceName = $params['deviceName'];
            $deviceNumber = $params['deviceNumber'];

            return $result = Device::createDevice($deviceName, $deviceNumber);
        }
        return json_encode(['request->get() - error' => 'actionCreateDevice']);
    }


}