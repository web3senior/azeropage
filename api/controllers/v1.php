<?php
//header("Access-Control-Allow-Origin: *");
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use function PHPSTORM_META\type;

header("Content-Type: application/json; charset=UTF-8");
//header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
//header("Access-Control-Max-Age: 3600");
//header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//Allow from any origin
if (isset($_SERVER['HTTP_ORIGIN'])) {
    // Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
    // you want to allow, and if so:
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
}

//Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        // may also be using PUT, PATCH, HEAD etc
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

    exit(0);
}

class V1 extends Controller
{
    private $_error = null;
    private $secretKey = 'secretd';
    private $version = 'V1';

    function __construct()
    {
        parent::__construct();

        // echo password_hash('1'.' رحیمی'.'abi', PASSWORD_DEFAULT);
    }

    protected function authorization()
    {
        if (!preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            header('HTTP/1.0 400 Bad Request');
            echo 'Token not found in request';
            exit;
        } else {
            // Verify user
            $token = substr($_SERVER['HTTP_AUTHORIZATION'], strlen("Bearer "), strlen($_SERVER['HTTP_AUTHORIZATION']));
            $result = (new JWTAuth)->decode($token);

            if (!$result['result']) {
                $this->_error = 'عدم معتبر بودن توکن';
                $this->_showError();
                exit();
            }
            return $result;
        }
    }

    function index()
    {
        echo 'It works!';
        die;
    }


    function page($operation)
    {
        $body = (array)json_decode(file_get_contents('php://input'));
        $table = ['page', 'wallet_addr'];

        switch ($operation) {
            case "get":
                $this->request_method('GET');
                $data = $this->model->page($_GET['wallet_addr']);
                if (!empty($data) && is_array($data)) {
                    (new Httpresponse)->set(200);
                    echo json_encode($data);
                    exit();
                } else {
                    $this->_error = "Not found any record!";
                    $this->Error();
                }
                break;
            case "add":
                $this->request_method('POST');
                $body['wallet_addr'] = $_GET['wallet_addr'];
                $result = $this->model->command("insert", $table, $body);
                if ($result) {
                    (new Httpresponse)->set(202);
                    echo json_encode([
                        "result" => true,
                        "message" => "new record added",
                        "added_id" =>  $result
                    ]);
                    exit();
                }
                break;
            case "update":
                $this->request_method('POST');
                $result = $this->model->command("update", $table, $body, $_GET['wallet_addr']);
                if ($result) {
                    (new Httpresponse)->set(202);
                    echo json_encode([
                        "result" => true,
                        "message" => "Updated"
                    ]);
                    exit();
                }
                break;
        }
    }

    function invoice($operation)
    {
        $body = (array)json_decode(file_get_contents('php://input'));
        $table = ['invoice', 'id'];

        switch ($operation) {
            case "get":
                $this->request_method('GET');
                $data = $this->model->invoice($_GET['wallet_addr']);
                if (!empty($data) && is_array($data)) {
                    (new Httpresponse)->set(200);
                    echo json_encode($data);
                    exit();
                } else {
                    $this->_error = "Not found any record!";
                    $this->Error();
                }
                break;
                case "detail":
                    $this->request_method('GET');
                    $data = $this->model->invoiceDetail($_GET['wallet_addr'], $_GET['invoice_id']);
                    if (!empty($data) && is_array($data)) {
                        (new Httpresponse)->set(200);
                        echo json_encode($data);
                        exit();
                    } else {
                        $this->_error = "Not found any record!";
                        $this->Error();
                    }
                    break;
            case "add":
                $this->request_method('POST');
                $body['wallet_addr'] = $_GET['wallet_addr'];
                $result = $this->model->command("insert", $table, $body);
                if ($result) {
                    (new Httpresponse)->set(202);
                    echo json_encode([
                        "result" => true,
                        "message" => "new record added",
                        "added_id" =>  $result
                    ]);
                    exit();
                }
                break;
            case "update":
                // $this->request_method('POST');
                $result = $this->model->command("update", $table, ["status" => 1, "txn" => $_GET['txn']], $_GET['id']);
                if ($result) {
                    (new Httpresponse)->set(202);
                    echo json_encode([
                        "result" => true,
                        "message" => "Updated"
                    ]);
                    exit();
                }
                break;
        }
    }





    function login()
    {

        $entityBody = file_get_contents('php://input');
        $data = json_decode($entityBody);
        $this->request_method("POST");

        if (!empty($data->phone) && !empty($data->password)) {
            $result = $this->model->login($data->phone);


            if (is_array($result) && !empty($result)) {
                // Verify the hash against the password entered
                $verify = password_verify($data->password, $result[0]['password']);

                if ($verify) {
                    // $res = (new Email)->send($data->email, 'ورود به حسا کاربی', 'کارر گرمی شما هم کون اد حساب کاربی خود دید');
                    // echo $res;
                    (new Httpresponse)->set(202);
                    echo json_encode([
                        "result" => true,
                        "token" => (new JWTAuth)->encode(["id" => $result[0]['id'], "phone" => $data->phone])
                    ]);
                } else {
                    (new Httpresponse)->set(200);
                    $this->_error = "نام کاربری یا کلمه عبور صحیح نمی باشد";
                    echo json_encode(["result" => false, "message" => $this->_error]);
                }
            } else {
                (new Httpresponse)->set(200);
                $this->_error = "نام کاربری یا کلمه عبور صحیح نمی باشد";
                echo json_encode(["result" => false, "message" => $this->_error]);
            }
        }
    }


    function request($operation)
    {
        $auth = $this->authorization();
        $user_id = $auth['response']->data->id;
        $body = (array)json_decode(file_get_contents('php://input'));
        $table = ['request', 'id'];

        switch ($operation) {
            case "get":
                $this->request_method('GET');
                $data = $this->model->command('info', ['address', 'user_id'], null,  $user_id);
                (new Httpresponse)->set(200);
                echo json_encode($data);
                break;
            case "delete":
                $this->request_method("get");
                if (isset($_GET['id']) && is_numeric($_GET['id']) && !empty($_GET['id'])) {
                    $result = $this->model->command("delete", $table, false, $_GET['id']);
                    if ($result) {
                        (new Httpresponse)->set(202);
                        echo json_encode(["result" => true]);
                        exit();
                    } else {
                        $this->_error = "Not found any record!";
                        $this->Error();
                    }
                }
                break;
            case "add":
                $this->request_method('POST');
                $body['user_id'] = $user_id;
                $result = $this->model->command("insert", $table, $body);
                if ($result) {
                    (new Httpresponse)->set(202);
                    echo json_encode([
                        "result" => true,
                        "message" => "request added",
                        "added_id" =>  $result
                    ]);
                    exit();
                }
                break;
            case "update":
                $this->request_method('POST');
                $result = $this->model->command("update", $table, $body, $_GET['id']);
                if ($result) {
                    (new Httpresponse)->set(202);
                    echo json_encode([
                        "result" => true,
                        "message" => "Updated"
                    ]);
                    exit();
                }
                break;
        }
    }
    function address($operation)
    {
        $auth = $this->authorization();
        $user_id = $auth['response']->data->id;
        $body = (array)json_decode(file_get_contents('php://input'));
        $table = ['address', 'id'];

        switch ($operation) {
            case "get":
                $this->request_method('GET');
                $data = $this->model->command('info', ['address', 'user_id'], null,  $user_id);
                (new Httpresponse)->set(200);
                echo json_encode($data);
                break;
            case "delete":
                $this->request_method("get");
                if (isset($_GET['id']) && is_numeric($_GET['id']) && !empty($_GET['id'])) {
                    $result = $this->model->command("delete", $table, false, $_GET['id']);
                    if ($result) {
                        (new Httpresponse)->set(202);
                        echo json_encode(["result" => true]);
                        exit();
                    } else {
                        $this->_error = "Not found any record!";
                        $this->Error();
                    }
                }
                break;
            case "add":
                $this->request_method('POST');
                $body['user_id'] = $user_id;
                $result = $this->model->command("insert", $table, $body);
                if ($result) {
                    (new Httpresponse)->set(202);
                    echo json_encode([
                        "result" => true,
                        "message" => "Added"
                    ]);
                    exit();
                }
                break;
            case "update":
                $this->request_method('POST');
                $result = $this->model->command("update", $table, $body, $_GET['id']);
                if ($result) {
                    (new Httpresponse)->set(202);
                    echo json_encode([
                        "result" => true,
                        "message" => "Updated"
                    ]);
                    exit();
                }
                break;
        }
    }



    function dashboard()
    {
        $this->request_method("GET");
        $data = $this->model->dashboard($_GET['wallet_addr']);
        if (!empty($data) && is_array($data)) {
            (new Httpresponse)->set(200);
            echo json_encode($data);
        } else {
            $this->_error = "Not found any record!";
            $this->Error();
        }
    }
    //-=-=------------------=============================
    //-=-=------------------=============================
    //-=-=------------------=============================
    //-=-=------------------=============================

    function dateTime($timestamp)
    {
        $timestamp = strtotime($timestamp);
        $this->request_method("GET");
        echo json_encode([
            'year' => jdate('Y', $timestamp),
            'month_name' => jdate('F', $timestamp),
            'day' => jdate('j', $timestamp),
            'day_name' => jdate('l', $timestamp),
        ]);
    }

    function now()
    {
        $timestamp = time();
        $this->request_method("GET");
        echo json_encode([
            'year' => jdate('Y', $timestamp),
            'month_name' => jdate('F', $timestamp),
            'day' => jdate('j', $timestamp),
            'day_name' => jdate('l', $timestamp),
            'full' => jdate('Y-m-d', $timestamp)
        ]);
    }

    function getRequestCommission()
    {

        $this->authorization();
        $entityBody = file_get_contents('php://input');
        $table = ['p_request', 'id'];
        $this->request_method('POST');
        $data = $this->model->requestCommission();

        if (!empty($data) && is_array($data)) {
            (new Httpresponse)->set(200);
            echo json_encode($data);
            exit();
        } else {
            $this->_error = "Not found any record!";
            $this->Error();
        }
    }



    function sendSms()
    {
        if (isset($_GET['numbers']) && isset($_GET['content'])) {
            $numbers = explode(',', $_GET['numbers']);

            if (count($numbers) > 10) return false;

            //Create the client object
            $soapclient = new SoapClient('http://vesal.armaghan.net:8080/core/MessageRelayService?wsdl');

            //Use the functions of the client, the params of the function are in 
            //the associative array
            $params = [
                'username' => 'ardabilcity',
                'password' => 'A@c6057',
                'originator' => '50004537',
                'destination' => $numbers,
                'content' => $_GET['content']
            ];
            $response = $soapclient->sendMessageOneToMany($params);

            echo json_encode($response);
            exit;
        }
        echo json_encode([
            'result' => false,
            'message' => 'no parameters sent'
        ]);
    }

    function sendSmsDirectly($numbers, $content)
    {
        $numbers = explode(',', $numbers);

        if (count($numbers) > 10) return false;

        //Create the client object
        $soapclient = new SoapClient('http://vesal.armaghan.net:8080/core/MessageRelayService?wsdl');

        //Use the functions of the client, the params of the function are in 
        //the associative array
        $params = [
            'username' => 'ardabilcity',
            'password' => 'A@c6057',
            'originator' => '50004537',
            'destination' => $numbers,
            'content' =>  $content
        ];
        $response = $soapclient->sendMessageOneToMany($params);

        return $response;
    }


    function signUp()
    {
        $entityBody = file_get_contents('php://input');
        $data = json_decode($entityBody);
        $this->request_method("POST");

        if (!empty($data->email) && !empty($data->password)) {
            $checkDuplicatedEmail = $this->model->checkDuplicatedUser($data->email);

            if (is_numeric($checkDuplicatedEmail['total']) && $checkDuplicatedEmail['total'] <= 0) {
                $result = $this->model->command('insert', ['user', 'id'], ['email' => $data->email, 'password' => password_hash($data->password, PASSWORD_DEFAULT)]);
                if ($result) {
                    (new Httpresponse)->set(202);

                    echo json_encode([
                        "result" => true,
                        "message" => URL . 'panel',
                        //"user_info" => $result,
                        "token" => (new JWTAuth)->encode(["email" => $data->email, "user" => true])
                    ]);
                } else {
                    (new Httpresponse)->set(401);
                    $this->_error = "ام کربری یا لمه بور اشتباه است!";
                    echo json_encode(["result" => false, "message" => $this->_error]);
                }
            } else {
                $this->_error = 'ای ایل قبلا در ستم ثب شده است، لف از بخش رود ه حاب کاربری ارد وید';
                echo json_encode(["result" => false, "message" => $this->_error]);
            }

            //            if (!empty($result) && is_array($result)) {
            //
            //            } else {
            //
            //            }
        }
    }




    function requestFiltered()
    {
        $this->authorization();
        $entityBody = file_get_contents('php://input');
        $data = json_decode($entityBody);
        $table = ['p_request', 'id'];
        $this->request_method('POST');
        $data = $this->model->requestFiltered($data);
        if (!empty($data) && is_array($data)) {
            (new Httpresponse)->set(200);
            echo json_encode($data);
        } else {
            $this->_error = "Not found any record!";
            $this->Error();
        }
    }


    function updateRequestFormContent($id)
    {
        $this->authorization();
        $entityBody = file_get_contents('php://input');
        $data = json_decode($entityBody);
        $table = ['p_request', 'id'];
        $this->request_method('POST');
        $data = $this->model->updateRequestFormContent($table, $data, $id);
        if (!empty($data) && is_array($data)) {
            (new Httpresponse)->set(200);
            echo json_encode($data);
        } else {
            $this->_error = "Not found any record!";
            $this->Error();
        }
    }

    function proceedings()
    {
        $this->authorization();
        $entityBody = file_get_contents('php://input');
        $data = json_decode($entityBody);
        $table = ['p_proceedings', 'id'];
        $this->request_method('POST');
        $data = $this->model->proceedings($table, $data);
        if (!empty($data) && is_array($data)) {
            (new Httpresponse)->set(200);
            echo json_encode($data);
        } else {
            $this->_error = "Not found any record!";
            $this->Error();
        }
    }

    function final_proceedings($id)
    {
        $this->authorization();
        $entityBody = file_get_contents('php://input');
        $data = json_decode($entityBody);
        $table = ['p_proceedings', 'id'];
        $this->request_method('POST');
        $data = $this->model->final_proceedings($id);
        if (!empty($data) && is_array($data)) {
            (new Httpresponse)->set(200);
            echo json_encode($data);
        } else {
            $this->_error = "Not found any record!";
            $this->Error();
        }
    }

    function printData()
    {
        $entityBody = file_get_contents('php://input');
        $table = ['print_data', 'id'];
        $this->request_method('GET');
        $data = $this->model->printData($table);
        if (!empty($data) && is_array($data)) {
            (new Httpresponse)->set(200);
            echo json_encode($data);
        } else {
            $this->_error = "Not found any record!";
            $this->Error();
        }
    }

    function robotQuery()
    {
        $this->authorization();
        $entityBody = file_get_contents('php://input');
        $table = ['p_layer', 'id'];
        $this->request_method('POST');
        $data = $this->model->robotQuery($table);
        if (!empty($data) && is_array($data)) {
            (new Httpresponse)->set(200);
            echo json_encode($data);
        } else {
            $this->_error = "Not found any record!";
            $this->Error();
        }
    }
    function layer()
    {
        $this->authorization();
        $entityBody = file_get_contents('php://input');
        $data = json_decode($entityBody);
        $table = ['p_layer', 'id'];
        $this->request_method('POST');
        $data = $this->model->layer($table, $data);
        if (!empty($data) && is_array($data)) {
            (new Httpresponse)->set(200);
            echo json_encode($data);
        } else {
            $this->_error = "Not found any record!";
            $this->Error();
        }
    }
    function passageList()
    {
        $table = ['p_request', 'id'];
        $this->request_method('GET');
        $data = $this->model->passageList($table);
        if (!empty($data) && is_array($data)) {
            (new Httpresponse)->set(200);
            echo json_encode($data);
        } else {
            $this->_error = "Not found any record!";
            $this->Error();
        }
    }

    function passageEdit()
    {
        $table = ['p_request', 'id'];
        $this->request_method('GET');
        $data = $this->model->passageEdit($table);
        if (!empty($data) && is_array($data)) {
            (new Httpresponse)->set(200);
            echo json_encode($data);
        } else {
            $this->_error = "Not found any record!";
            $this->Error();
        }
    }

    function passageUpdate($id)
    {
        $entityBody = file_get_contents('php://input');
        $data = (array) json_decode($entityBody);
        $table = ['p_request', 'id'];
        $this->request_method('POST');


        $data = $this->model->command('update', $table, $data, $id);
        if (($data)) {
            (new Httpresponse)->set(200);
            echo json_encode($data);
        } else {
            $this->_error = "Not found any record!";
            $this->Error();
        }
    }

    function updateLayer($id)
    {
        $this->authorization();
        $entityBody = file_get_contents('php://input');
        $data = json_decode($entityBody);
        $table = ['p_layer', 'id'];
        $this->request_method('POST');
        $data = $this->model->updateLayer($data, $id);
        if (($data)) {
            (new Httpresponse)->set(200);
            echo json_encode($data);
        } else {
            $this->_error = "Not found any record!";
            $this->Error();
        }
    }
    function allLayer()
    {
        $this->authorization();
        $entityBody = file_get_contents('php://input');
        $table = ['p_layer', 'id'];
        $this->request_method('POST');
        $data = $this->model->allLayer('fetch', $table);
        if (!empty($data) && is_array($data)) {
            (new Httpresponse)->set(200);
            echo json_encode($data);
        } else {
            $this->_error = "Not found any record!";
            $this->Error();
        }
    }

    function allCategory()
    {
        $entityBody = file_get_contents('php://input');
        $table = ['p_category', 'id'];
        $this->request_method('GET');
        $data = $this->model->command('fetch', $table);
        if (!empty($data) && is_array($data)) {
            (new Httpresponse)->set(200);
            echo json_encode($data);
        } else {
            $this->_error = "Not found any record!";
            $this->Error();
        }
    }
    function delLayer($id)
    {
        $this->authorization();
        $table = ['p_layer', 'id'];
        $this->request_method('POST');
        $data = $this->model->command('delete', $table, false, $id);

        if (!empty($data) && is_numeric($data)) {
            (new Httpresponse)->set(200);
            echo json_encode($data);
        } else {
            $this->_error = "Not found any record!";
            $this->Error();
        }
    }

    function deleteCategory($id)
    {
        $this->authorization();
        $table = ['p_category', 'id'];
        $this->request_method('POST');
        $data = $this->model->command('delete', $table, false, $id);

        if (!empty($data) && is_numeric($data)) {
            (new Httpresponse)->set(200);
            echo json_encode($data);
        } else {
            $this->_error = "Not found any record!";
            $this->Error();
        }
    }

    // سمت شهروند اجرا میشه
    function deletePassage($id)
    {
        $table = ['p_request', 'id'];
        $this->request_method('POST');
        $data = $this->model->command('delete', $table, false, $id);

        if (!empty($data) && is_numeric($data)) {
            (new Httpresponse)->set(200);
            echo json_encode($data);
        } else {
            $this->_error = "Not found any record!";
            $this->Error();
        }
    }




    function delProceeding($id)
    {
        $this->authorization();
        $table = ['p_proceedings', 'id'];
        $this->request_method('POST');
        $data = $this->model->command('delete', $table, false, $id);

        if (!empty($data) && is_numeric($data)) {
            (new Httpresponse)->set(200);
            echo json_encode($data);
        } else {
            $this->_error = "Not found any record!";
            $this->Error();
        }
    }

    function requestDetail($id)
    {
        // $this->authorization();
        $entityBody = file_get_contents('php://input');
        $table = ['p_request', 'id'];
        $this->request_method('GET');
        $data = $this->model->requestDetail($table, $id);
        if (!empty($data) && is_array($data)) {
            (new Httpresponse)->set(200);
            //$data['request'][0]['dt'] = jdate('Y-m-d', strtotime($data['request'][0]['dt']));
            echo json_encode($data);
        } else {
            $this->_error = "Not found any record!";
            $this->Error();
        }
    }
    function requestCommissionDetail($id)
    {
        $this->authorization();
        $entityBody = file_get_contents('php://input');
        $table = ['p_request', 'id'];
        $this->request_method('GET');
        $data = $this->model->requestCommissionDetail($table, $id);
        if (!empty($data) && is_array($data)) {
            (new Httpresponse)->set(200);
            echo json_encode($data);
        } else {
            $this->_error = "Not found any record!";
            $this->Error();
        }
    }

    function tile()
    {
        $table = ['p_tile', 'id'];
        $this->request_method('GET');
        $data = $this->model->tile($table);
        if (!empty($data) && is_array($data)) {
            (new Httpresponse)->set(200);
            echo json_encode($data);
        } else {
            $this->_error = "Not found any record!";
            $this->Error();
        }
    }


    function requestDetailConfirmation($ids, $token)
    {
        $entityBody = file_get_contents('php://input');
        $table = ['p_request', 'id'];
        $this->request_method('GET');
        $data = $this->model->requestDetailConfirmation($table, $ids, $token);
        if (!empty($data) && is_array($data) && !empty($data['request']) && !empty($data['commission'])) {
            (new Httpresponse)->set(200);

            echo json_encode($data);
        } else {
            $this->_error = "Not found any record!";
            $this->Error();
        }
    }

    function requestStatus()
    {
        $entityBody = file_get_contents('php://input');
        $data = json_decode($entityBody);
        $this->request_method("POST");

        if (!empty($data->id) && !is_null($data->val)) {
            $result = $this->model->command('update', ['p_request', 'id'], ['status' => $data->val], $data->id);

            if ($result) {
                (new Httpresponse)->set(202);

                echo json_encode([
                    "result" => true,
                    "message" => 'با موفقیت اپدیت شد',
                    "admin_info" => $result
                ]);
            } else {
                (new Httpresponse)->set(401);
                $this->_error = "error!";
                echo json_encode(["result" => false, "message" => $this->_error]);
            }
        }
    }
    function requestCategory()
    {
        $entityBody = file_get_contents('php://input');
        $data = json_decode($entityBody);
        $this->request_method("POST");

        if (!empty($data->id) && !is_null($data->val)) {
            $result = $this->model->command('update', ['p_request', 'id'], ['category_id' => (empty($data->val) ? null : $data->val)], $data->id);

            if ($result) {
                (new Httpresponse)->set(202);

                echo json_encode([
                    "result" => true,
                    "message" => 'با موفقیت اپدیت شد',
                    "admin_info" => $result
                ]);
            } else {
                (new Httpresponse)->set(401);
                $this->_error = "error!";
                echo json_encode(["result" => false, "message" => $this->_error]);
            }
        }
    }

    function saveFormContent()
    {
        $entityBody = file_get_contents('php://input');
        $data = json_decode($entityBody);
        $this->request_method("POST");

        if (!empty($data->ids)) {
            $result = $this->model->saveFormContent($data);

            if ($result) {
                (new Httpresponse)->set(202);

                echo json_encode([
                    "result" => true,
                    "message" => 'با موفقیت اپدیت شد',
                    "admin_info" => $result
                ]);
            } else {
                (new Httpresponse)->set(401);
                $this->_error = "error!";
                echo json_encode(["result" => false, "message" => $this->_error]);
            }
        }
    }

    function saveLayer()
    {
        $entityBody = file_get_contents('php://input');
        $data = json_decode($entityBody);
        $this->request_method("POST");

        if (!empty($data->name) && !is_null($data->layers)) {
            $result = $this->model->command('insert', ['p_layer', 'id'], [
                'name' => $data->name,
                'p_request_type_id' => $data->p_request_type_id,
                'dt' => jdate('Y-m-d H:m:s', '', '', '', 'en'),
                'layers' => json_encode($data->layers)
            ]);

            if ($result) {
                (new Httpresponse)->set(202);

                echo json_encode([
                    "result" => true,
                    "message" => 'با موفقیت اپدیت شد',
                    "admin_info" => $result
                ]);
            } else {
                (new Httpresponse)->set(401);
                $this->_error = "error!";
                echo json_encode(["result" => false, "message" => $this->_error]);
            }
        }
    }


    function newCategory()
    {
        $this->authorization();
        $entityBody = file_get_contents('php://input');
        $data = json_decode($entityBody);
        $this->request_method("POST");

        if (!empty($data->name)) {
            $result = $this->model->command('insert', ['p_category', 'id'], [
                'name' => $data->name
            ]);

            if ($result) {
                (new Httpresponse)->set(202);

                echo json_encode([
                    "result" => true,
                    "message" => 'با موفقیت اپدیت شد'
                ]);
            } else {
                (new Httpresponse)->set(401);
                $this->_error = "error!";
                echo json_encode(["result" => false, "message" => $this->_error]);
            }
        }
    }

    function requestSignatureSave()
    {
        $entityBody = file_get_contents('php://input');
        $data = json_decode($entityBody);
        $this->request_method("POST");

        if (!empty($data->commission_id) && !is_null($data->request_id)) {

            $check = $this->model->checkSignatureCount($data->request_id, $data->commission_id);

            if (!empty($check) && is_array($check) && count($check) > 0) exit();


            $result = $this->model->command(
                'insert',
                ['p_request_commission', 'id'],
                [
                    'p_request_id' => $data->request_id,
                    'p_commission_id' => $data->commission_id,
                ]
            );

            if ($result) {
                (new Httpresponse)->set(202);

                echo json_encode([
                    "result" => true,
                    "message" => 'با موفقیت اپدیت شد',
                    "admin_info" => $result
                ]);
            } else {
                (new Httpresponse)->set(401);
                $this->_error = "error!";
                echo json_encode(["result" => false, "message" => $this->_error]);
            }
        }
    }


    function requestFormContent()
    {
        $this->authorization();
        $entityBody = file_get_contents('php://input');
        $data = json_decode($entityBody);
        $this->request_method("POST");

        if (!empty($data->id)) {
            $post = [
                'fullname' => $data->fullname,
                'tel' => $data->tel,
                'address' => $data->address,
                'address_location' => $data->address_location,
                'title' => $data->title,
                'suggestion_names' => $data->suggestion_names,
                'form_content' => $data->form_content,
                'description' => $data->description,
                'accepted_name' => $data->accepted_name,
                'main_attachment' => $data->main_attachment,
                'other_attachment' => $data->other_attachment,

                'description' => $data->description,
                'accepted_name' => $data->accepted_name
            ];
            $result = $this->model->command('update', ['p_request', 'id'], $post, $data->id);

            if ($result) {
                (new Httpresponse)->set(202);

                echo json_encode([
                    "result" => true,
                    "message" => 'با موفقیت اپدیت شد',
                    "admin_info" => $result
                ]);
            } else {
                (new Httpresponse)->set(401);
                $this->_error = "error!";
                echo json_encode(["result" => false, "message" => $this->_error]);
            }
        }
    }

    function updateProceeding($id)
    {
        $this->authorization();
        $entityBody = file_get_contents('php://input');
        $data = json_decode($entityBody);
        $this->request_method("POST");

        if (!empty($id)) {
            $data =  json_decode(json_encode($data), true);

            $result = $this->model->command('update', ['p_proceedings', 'id'],  $data, $id);

            if ($result) {
                (new Httpresponse)->set(202);

                echo json_encode([
                    "result" => true,
                    "message" => 'با موفقیت اپدیت شد',
                    "admin_info" => $result
                ]);
            } else {
                (new Httpresponse)->set(401);
                $this->_error = "error!";
                echo json_encode(["result" => false, "message" => $this->_error]);
            }
        }
    }


    function newProceeding()
    {
        $this->authorization();
        $entityBody = file_get_contents('php://input');
        $data = json_decode($entityBody);
        $this->request_method("POST");

        if (!empty($data)) {


            //   var_dump(json_encode($data->request_list));
            //   die;
            $result = $this->model->command('insert', ['p_proceedings', 'id'], [
                "request_list" => json_encode($data->request_list)
            ]);

            if ($result) {
                (new Httpresponse)->set(202);

                echo json_encode([
                    "result" => true,
                    "message" => 'با موفقیت ایجاد شد'
                ]);
            } else {
                (new Httpresponse)->set(401);
                $this->_error = "error!";
                echo json_encode(["result" => false, "message" => $this->_error]);
            }
        }
    }

    function report()
    {
        $this->authorization();
        $table = ['p_layer', 'id'];
        $this->request_method("GET");
        $data = $this->model->report($table);
        if (!empty($data) && is_array($data)) {
            (new Httpresponse)->set(200);
            echo json_encode($data);
        } else {
            $this->_error = "Not found any record!";
            $this->Error();
        }
    }

    function reportAllRequest()
    {
        $this->authorization();
        $entityBody = file_get_contents('php://input');
        $data = json_decode($entityBody);
        $this->request_method("POST");

        $data = $this->model->reportAllRequest($data);

        if (!empty($data) && is_array($data)) {
            (new Httpresponse)->set(200);
            echo json_encode($data);
        } else {
            $this->_error = "Not found any record!";
            $this->Error();
        }
    }

    function commission()
    {
        $this->authorization();
        $table = ['p_commission', 'id'];
        $this->request_method("GET");
        $data = $this->model->commission($table);
        if (!empty($data) && is_array($data)) {
            (new Httpresponse)->set(200);
            echo json_encode($data);
        } else {
            $this->_error = "Not found any record!";
            $this->Error();
        }
    }


    function commissionAll()
    {
        $this->authorization();
        $table = ['p_commission', 'id'];
        $this->request_method("GET");
        $data = $this->model->command('fetch', $table);
        if (!empty($data) && is_array($data)) {
            (new Httpresponse)->set(200);
            echo json_encode($data);
        } else {
            $this->_error = "Not found any record!";
            $this->Error();
        }
    }

    function requestType()
    {
        $table = ['p_request_type', 'id'];
        $this->request_method("GET");
        $data = $this->model->command('fetch', $table);
        if (!empty($data) && is_array($data)) {
            (new Httpresponse)->set(200);
            echo json_encode($data);
        } else {
            $this->_error = "Not found any record!";
            $this->Error();
        }
    }


    function requestTypeFilter()
    {
        $table = ['p_request_type', 'id'];
        $this->request_method("GET");
        $data = $this->model->requestTypeFilter($table);
        if (!empty($data) && is_array($data)) {
            (new Httpresponse)->set(200);
            echo json_encode($data);
        } else {
            $this->_error = "Not found any record!";
            $this->Error();
        }
    }


    function subscription()
    {
        $entityBody = file_get_contents('php://input');
        $data = json_decode($entityBody);
        $this->request_method("POST");

        if (!empty($data->endpoint) && !empty($data->keys)) {
            $data = [
                'push_subscription' => json_encode($data),
                'ip' => (new Ip)->get()
            ];
            $result = $this->model->subscription($data);

            if (!empty($result) && is_numeric($result)) {
                (new Httpresponse)->set(202);

                echo json_encode([
                    "result" => true,
                    "message" => 'با موفقیت ض شدید',
                    "admin_info" => $result
                ]);
            } else {
                (new Httpresponse)->set(401);
                $this->_error = "error!";
                echo json_encode(["result" => false, "message" => $this->_error]);
            }
        }
    }

    function requestSend()
    {
        $entityBody = file_get_contents('php://input');
        $data = json_decode($entityBody);
        $this->request_method("POST");


        if (!empty($_POST['fullname']) && !empty($_POST['tel'])) {
            $result = $this->model->requestSend($_POST);
            if (!empty($result) && is_numeric($result)) {

                (new Httpresponse)->set(202);
                echo json_encode([
                    "result" => true,
                    "message" => 'با موفقیت ثبت شد'
                ]);
                $this->sendSmsDirectly(
                    $_POST['tel'],
                    "شهروند گرامی؛ درخواست شما ثبت و در حال بررسی می باشد.
                \n
                 با تشکر
                 \n
                  شورای نام گذاری معابر عمومی شهرداری اردبیل"
                );
            } else {
                (new Httpresponse)->set(401);
                $this->_error = "error!";
                echo json_encode(["result" => false, "message" => $this->_error]);
            }
        }
    }

    function uploadRequestDoc($id, $field)
    {
        $entityBody = file_get_contents('php://input');
        $data = json_decode($entityBody);
        $this->request_method("POST");


        if (!empty($_FILES[$field]['name'])) {
            $_POST[$field] = (new Upload)->documentUpload($field, 0, time());
            $result = $this->model->uploadRequestDoc($_POST[$field], $id, $field);
            if ($result) {
                (new Httpresponse)->set(202);
                echo json_encode([
                    "result" => true,
                    "message" => 'با موفقیت ثبت شد'
                ]);
            } else {
                (new Httpresponse)->set(401);
                $this->_error = "error!";
                echo json_encode(["result" => false, "message" => $this->_error]);
            }
        } else {
            echo "no file selected";
        }
    }

    function delDoc($id, $field)
    {
        $entityBody = file_get_contents('php://input');
        $data = json_decode($entityBody);
        $this->request_method("POST");

        if (!empty($data->name)) {
            $filename = URL . "upload/doc/" .    $data->name;
            if (file_exists($filename)) {
                unlink($filename);
                (new Httpresponse)->set(202);
                echo json_encode([
                    "result" => true,
                    "message" => 'با موفقیت ثبت شد'
                ]);
            } else {
                (new Httpresponse)->set(401);
                $this->_error = "error!";
                echo json_encode(["result" => false, "message" => $this->_error]);
            }
        } else {
            echo "no file selected";
        }
    }



    private function request_method($arg)
    {
        //header("Access-Control-Allow-Methods: " . $arg);
        if (strtolower($_SERVER['REQUEST_METHOD']) !== strtolower($arg)) {
            (new Httpresponse)->set(405);
            echo (json_encode(["message" => "Request method must be correct set!"]));
            exit();
        }
    }

    private function _showError()
    {
        if (!empty($this->_error))  $this->Error();
    }


    /**
     * Authorization
     * @param String $key
     */
    private function Error()
    {
        if (isset($this->_error)) {
            if (!empty($this->_error)) {
                (new Httpresponse)->set(400);
                echo json_encode([
                    "result" => false,
                    "message" => $this->_error
                ]);
            }
        } else {
            (new Httpresponse)->set(400);
            echo ('{"message":"Please contact with programmer!"}');
            exit();
        }
    }
}
