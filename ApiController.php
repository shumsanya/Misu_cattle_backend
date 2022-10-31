<?php

namespace app\controllers;

use app\models\Device;
use app\models\Data;
use Yii;
use yii\filters\Cors;
use yii\web\Controller;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;




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

/**
* вставка в БД одним пакетом
*/
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

    /**
     * перевірка на пропуски записів до БД (чи всі дані записано)
     * @param $array
     * @return array
     */
    public static function actionIncompleteData($array)
    {
        $count = 0;
        $array_incomplete = array();
        $arrayDate = array();
        $x = 0;

        $start_item = current($array);
        $start_date = date('Y-m-d H:i', strtotime( $start_item['date'] ) );
        $newDate = $start_date;

        $and_item = end($array);
        $end_date = date('Y-m-d H:i', strtotime( $and_item['date'] ) );

        foreach($array as $key=>$value){

            $arrayDate[] = date('Y-m-d H:i', strtotime( $value['date'] ) );
            //$array_incomplete[] = date('Y-m-d H:i', strtotime( $value['date'] ) );

        }


        while ($newDate != $end_date)
        {
            if ( in_array( $newDate, $arrayDate ))
            {
                $newDate = date('Y-m-d H:i', strtotime($newDate.'+ 1 minute'));
                $count++;

            } else
            {
                $array_incomplete['problems'.$x] = $newDate.'  всього записів - '.$count;
                $newDate = date('Y-m-d H:i', strtotime($newDate.'+ 1 minute'));
                //$array_incomplete['$count'] = $count;
                $count = 0;
                $x ++;
            }
        }

       /* foreach($array as $key=>$value)
        {
            if ($x){
                $newDate = date('Y-m-d H:i', strtotime( $value['date'] ));
                $array_incomplete['date_s'] = $newDate;
                $array_incomplete['$key'] = date('Y-m-d H:i', strtotime( $value['date'] ));
                $x = false;
            }


            //if ($newDate === date('Y-m-d H:i', strtotime( $value['date'] ))) {
            if ( in_array($newDate, date('Y-m-d H:i', strtotime( $value['date'] )) )){
                $count++;
                $array_incomplete[$key] = $count;
            } else {
                $array_incomplete['problems'.$key] = $newDate.'  всього записів - '.$count;
                $newDate = date('Y-m-d H:i', strtotime($newDate.'+ 1 minutes'));
                $array_incomplete['date_posle+1'] = $newDate;
                $count = 0;
            }

        }*/

        return $array_incomplete;
    }


    /**
     * Збереження до БД нового девайсу
     * @return false|string
     * @throws \yii\base\InvalidConfigException
     */
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


    /**
     * Створення збереження файлу EXCEL, та відправка url на фронт
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionCreateExcel()
    {
        if (YII::$app->request->get()) {
            $params = Yii::$app->request->getBodyParams();

            $result = array();
            $new_array_params = array();

            if (isset($params['startPeriod'])) {
                // якщо є заданий період
                $new_array_params = self::actionBuildChartParams($params);

            } else {
                // вибір даних за кількістю записів в БД
                $result = Data::getDataDeviceLimit($params['device_id'], $params['limit'], date('Y-m-d G:i', $params['date'] / 1000));
                // перезаписується масив у зворотному порядку
                $new_array_params = array_reverse($result);
            }

            // якщо даних немає
            if (!$new_array_params) {
                return json_encode($x = ['result' => 'array_empty', 'visual' => $params['visualSwitch']]);
            }

            // розділення строк на числа
            $new_array_data = Data::getDataParse($new_array_params);

//************************************************************************************************************************************************

            //  CREATE A NEW SPREADSHEET
            $spreadsheet = new Spreadsheet();

            $spreadsheet->getActiveSheet()
                ->getColumnDimension('B')
                ->setAutoSize(true);
            $spreadsheet->getActiveSheet()
                ->getColumnDimension('C')
                ->setAutoSize(true);
            $spreadsheet->getActiveSheet()
                ->getColumnDimension('D')
                ->setAutoSize(true);
            $spreadsheet->getActiveSheet()
                ->getColumnDimension('E')
                ->setAutoSize(true);
            $spreadsheet->getActiveSheet()
                ->getColumnDimension('F')
                ->setAutoSize(true);
            $spreadsheet->getActiveSheet()
                ->getColumnDimension('G')
                ->setAutoSize(true);
            $spreadsheet->getActiveSheet()
                ->getColumnDimension('H')
                ->setAutoSize(true);
             $spreadsheet->getActiveSheet()
                ->getColumnDimension('I')
                ->setAutoSize(true);
             $spreadsheet->getActiveSheet()
                ->getColumnDimension('J')
                ->setAutoSize(true);

            $sheet = $spreadsheet->getActiveSheet();

            // SET CELL VALUE
            $sheet->setCellValue("B2", "DEVICE ID");
            $sheet->setCellValue("C2", "ACCELERATION X");
            $sheet->setCellValue("D2", "ACCELERATION Y");
            $sheet->setCellValue("E2", "ACCELERATION Z");
            $sheet->setCellValue("F2", "ROTATION X");
            $sheet->setCellValue("G2", "ROTATION Y");
            $sheet->setCellValue("H2", "ROTATION Z");
            $sheet->setCellValue("I2", "TEMPERATURE");
            $sheet->setCellValue("J2", "DATE");

            $count = 3;
            foreach ($new_array_data as $key => $value) {
                $sheet->setCellValue("B" . $count, $value['device_id']);
                $sheet->setCellValue("C" . $count, $value['acceleration'][0]);
                $sheet->setCellValue("D" . $count, $value['acceleration'][1]);
                $sheet->setCellValue("E" . $count, $value['acceleration'][2]);
                $sheet->setCellValue("F" . $count, $value['rotation'][0]);
                $sheet->setCellValue("G" . $count, $value['rotation'][1]);
                $sheet->setCellValue("H" . $count, $value['rotation'][2]);
              //  $sheet->setCellValue("I" . $count, $value['temperature']);
                $sheet->setCellValue("J" . $count, $value['label']);
                $count++;
            }

            $writer = new Xlsx($spreadsheet);


            // видалити попередній файл якщо він був створений більше години тому
            $files = glob('C:\OpenServer\domains\localhost\Misu_Cattle\assets\download_files\*');
            $date_time = date("Y-m-d G:i");
            if (count($files) > 0)
            {
                foreach ($files as $file) {
                    $test[$file] = fileatime($file);
                    if (file_exists($file) && strtotime($date_time . " - 1 hour") > fileatime($file)) {
                        unlink($file);
                    }
                }
            }

           /* header("HTTP/1.1 200 OK");
            header("Pragma: public");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: private", false);
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="test"');
            header('Content-Transfer-Encoding: binary');*/

            $date = date('d.m__H-i-s');
            $writer->save('C:\OpenServer\domains\localhost\Misu_Cattle\assets\download_files\info_' . $date . '_.xlsx');

            // якщо все добре відправляється масив даних
            return json_encode($x = ['newData' => $new_array_data, 'download_files' => 'http://localhost/Misu_Cattle/assets/download_files/info_' . $date . '_.xlsx']);
        } else {
            // якщо не вдалося отримати запит  'data'=>$new_array_params,
            return json_encode($x = ['result' => 'array_error']);
        }
    }

}
