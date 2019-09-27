<?php
/**
 * Работа со свичами 2950, 2960, 3550, 3560, 3750, 4948 по SSH
 * включение/выключение,
 * смена скорости,
 * смена vlan,
 * смена max port security,
 * получение текущего vlan,
 * получение текущего max port security,
 * получение комплексного значения параметров порта
 * //пример вызова через get-запрос
 * //ug3.hostkey.ru/?r=dpc/main/hardwaretest&showDataSSH=1&ipSwitch=10.20.0.31&numPort=Gi0/27&serverlinkid=35350
 */

namespace common\services\hardware\base;

use yii;

/**
 * Description of Hardware
 * @author ArthurYorkin
 */
class HardwareMain implements HardwareInterface
{
    private $response;

//    private $config = [];
//    private $connection;
//    private $shell;
//    private $hardwareType;
//    private $testServer = 35138;      //Забиваем id тестового сервера на тот случай, если классу не передан id рабочего сервера, а в лог данные записать нужно
//
//    public $serverId;              //обязательный параметр (если не задан, подменяем на ID тестового сервера)
//    public $ipSwitch;              //обязательный параметр
//    public $numPort;               //обязательный параметр
//
//    public $changeSpeedPort = false;    //новое значение скорости порта. если =1, то скорость на порту выставляется в auto, иначе присваивается переданное значение
//    public $onoff = "";                 //Возможные значения: portoff - отключить порт, portoт - включить порт; иначе возвращается сообщение об ошибке
//    public $vlan1 = false;              //новое значение vlan1 используется в методе ChangeVlan
//    public $vlan2 = false;              //новое значение vlan2 используется в методе ChangeVlan
//    public $prtscr = 0;                 //новое значение maxportsecurity используется в методе setPortSecurity
//    public $serverlinkid = false;       //id коммутатора; используется для получения типа оборудования
//
    public $idNetI;

    private $model;

    /**
     * HardwareMain constructor.
     */
    public function __construct($model = [])
    {
        $this->model = $model;
    }

//Включение/выключение порта
//требуется параметры:
//                      onoff
//                      idNetI
    public function DisableEnablePort()
    {
        $this->connect();

        $comand = [
            "configure terminal",
            "interface " . $this->model->numPort,
        ];

        switch ($this->model->onoff) {
            case "porton":
                $status = "port ON";
                $comand[] = "no shutdown";
                break;
            case "portoff":
                $status = "port OFF";
                $comand[] = "shutdown";
                break;
            default:
                $this->Set_Control($this->model->serverId, "Неизвестный action " . $this->model->onoff, 'error');
                $code = 'H1000011';
                $this->setErrors($code, "Parameter Action is unknown. Error code: $code.");

                return $this->response;
        }
        $comand[] = "exit";
        $comand[] = "exit";
        $comand[] = "wr";

        $result = $this->sendCommand($comand);
        $result = strtolower($result);

        if (stripos($result, "error:") === false) {
            $this->Set_Control($this->model->serverId, "Статус порта изменен {$status} netId: {$this->idNetI}", 'control');
            return ["numPort" => $this->model->numPort,
                "portstatus" => $status];
        } else {
            $this->Set_Control($this->model->serverId, "Ошибка изменения статуса порта netId: {$this->idNetI}; {$result}", 'error');
            $code="H1000021";
            $this->setErrors($code, 'Ошибка какая то...');
            $code="H1000020";
            $this->setErrors($code, $result." Error code: $code.");
            return $this->response;
        }
    }

//Смена скорости на порту
//требуется параметры:
//                      changeSpeedPort
    public function ChangeSpeed()
    {
        if ($this->model->changeSpeedPort) {
            $speed = (int)$this->model->changeSpeedPort;
            $speed = ($speed == 1) ? "auto" : $speed;
        } else {
            $this->Set_Control($this->model->serverId, "Не передали скорость, менять нечего " . $this->model->ipSwitch, 'error');
            $code = "H1000010";
            $this->setErrors($code, "Parameter Speed is missed. Error code: $code.");
            return $this->response;
        }

        $this->connect();
        $typehard = $this->getHardwareType();

        $comand = [
            "configure terminal",
            "interface " . $this->model->numPort,
        ];
        if ($typehard == "4948") {
            if ($speed == "auto") {
                $comand[] = "speed auto";
            } else {
                $comand[] = "speed auto " . $speed;
            }
        } elseif (in_array($typehard, ["2950", "2960", "3550", "3560", "3750"])) {
            $comand[] = "speed " . $speed;
        } else {
            $this->Set_Control($this->model->serverId, "Коммутатор неизвестного типа: {$this->hardwareType['name']}; ip: {$this->model->ipSwitch}; port: {$this->model->numPort};", 'error');
            $code = "H1000003";
            $this->setErrors($code, "Switch type unknown. Error code: $code.");
            return $this->response;
        }
        $comand[] = "exit";
        $comand[] = "exit";
        $comand[] = "wr";

        $result = $this->sendCommand($comand);
        $result = strtolower($result);

        if (stripos($result, "error:") === false) {
            $this->Set_Control($this->model->serverId, "Скорость порта изменена: {$this->hardwareType['name']}; ip: {$this->model->ipSwitch}; port: {$this->model->numPort};", 'control');
            return ["speed" => $speed];
        } else {
            $this->Set_Control($this->model->serverId, "Ошибка изменения скорости порта; {$result}", 'error');
            return "This command is not supported by the hardware.";
        }

        return true;
    }

//Смена влана
//требуется параметры:
//                      ChangeVlan
//                      vlan1
//                      vlan2
    public function ChangeVlan()
    {
        if (!$this->model->vlan1) {
            $this->Set_Control($this->model->serverId, "Не передали влан, менять нечего " . $this->model->ipSwitch, 'error');
            $code = "H1000012";
            $this->response['code'] = $code;
            $this->setErrors($code, "Parameter vlan1 is missed. Error code: $code.");
            return $this->response;
        }

        $this->connect();
        $typehard = $this->getHardwareType();

        $comand = [
            "configure terminal",
            "interface " . $this->model->numPort,
        ];

        if ($this->model->vlan2) {                                               //trunk
            $comand[] = "switchport trunk native vlan {$this->model->vlan1}";
            $comand[] = "switchport trunk allowed vlan {$this->model->vlan1},{$this->model->vlan2}";
            if (in_array($typehard, ["3750", "3560", "3550"])) {
                $comand[] = "switchport trunk encapsulation dot1q";
            }
            $comand[] = "switchport mode trunk";
            $comand[] = "no switchport access vlan";
            $comand[] = "spanning-tree portfast trunk";
        } else {                                                    //no trunk
            $comand[] = "switchport access vlan {$this->model->vlan1}";
            $comand[] = "switchport mode access";
            $comand[] = "no switchport trunk native vlan";
            $comand[] = "no switchport trunk allowed vlan";
            if (in_array($typehard, ["3750", "3560", "3550"])) {
                $comand[] = "no switchport trunk encapsulation dot1q";
            }
            $comand[] = "no spanning-tree portfast trunk";
            $comand[] = "spanning-tree portfast";
        }
        $comand[] = "exit";
        $comand[] = "exit";
        $comand[] = "wr";

        $result = $this->sendCommand($comand);
        $result = strtolower($result);

        if (stripos($result, "error:") === false) {
            $this->Set_Control($this->model->serverId, "Вланы изменены: {$this->hardwareType['name']}; ip: {$this->model->ipSwitch}; port: {$this->model->numPort}; vlan1: {$this->model->vlan1} vlan2: {$this->model->vlan2}", 'control');
            return ["vlan1" => $this->model->vlan1,
                "vlan2" => $this->model->vlan2];
        } else {
            $this->Set_Control($this->model->serverId, "Ошибка изменения вланов; {$result}", 'error');
            return "This command is not supported by the hardware.";
        }
    }

//Установка значения port-security
//требуется параметры:
//                      setPortSecurity
//                      prtscr
    public function setPortSecurity()
    {
        if ($this->model->prtscr < 1) {
            $this->Set_Control($this->model->serverId, "Не получили значение port-security maximum, операция отменена " . $this->model->ipSwitch, 'error');
            $code = "H1000014";
            $this->setErrors($code, "Parameter Port-Security is missed. Error code: $code.");
            return $this->response;
        }

        $this->connect();
        $comand = [
            "configure terminal",
            "interface {$this->model->numPort}",
            "switchport port-security maximum {$this->model->prtscr}",
            "exit",
            "exit",
            "wr",
        ];
        $result = $this->sendCommand($comand);
        $result = strtolower($result);

        if (stripos($result, "error:") === false) {
            $this->Set_Control($this->model->serverId, "Изменено значение switchport port-security maximum = {$this->model->prtscr}; ip = {$this->model->ipSwitch}; port = {$this->model->numPort}", 'control');
            return ["maxportsecurity" => trim($this->model->prtscr)];
        } else {
            $this->Set_Control($this->model->serverId, "Ошибка при выполнении команды switchport port-security maximum = {$this->model->prtscr}; ip = {$this->model->ipSwitch}; port = {$this->model->numPort}", 'error');
            return $result;
        }
    }

//Получение данных по влану
//требуется параметры:
//                      showVlan
    public function ShowVlan()
    {
        $this->connect();

        $comand = [
            "show run interface  {$this->model->numPort}",
        ];
        $result = $this->sendCommand($comand);
        $result = strtolower($result);

        $vlanInfo = $this->getVlanInfo($result);

        if (stripos($result, "error:") === false) {
            $this->Set_Control($this->model->serverId, "show run interfaces {$this->model->ipSwitch} {$this->model->numPort}", 'control');
            return ["vlan" => $vlanInfo];
        } else {
            $this->Set_Control($this->model->serverId, "Ошибка при выполнении команды show run interfaces {$this->model->ipSwitch} {$this->model->numPort}", 'error');
            return $result;
        }
    }

//Получение данных по port-security
//требуется параметры:
//                      showPortSecurity
    public function showPortSecurity()
    {
        $this->connect();
        $typehard = $this->getHardwareType();

        $comand = [
            "show run interface {$this->model->numPort}",
        ];
        $result = $this->sendCommand($comand);
        $result = strtolower($result);

        $portSec = $this->getPortSecurity($result, $typehard);

        if ($typehard == "4948") {
            $comand = [
                "sh mac add int {$this->model->numPort} | count dynamic|static",
            ];
        } else {
            $comand = [
                "sh mac add int {$this->model->numPort} | i Total",
            ];
        }
        $result = $this->sendCommand($comand);
        $result = strtolower($result);
        $macadd = $this->getMacAdd($result, $typehard);

        if (stripos($result, "error:") === false) {
            $this->Set_Control($this->model->serverId, "show run interfaces {$this->model->ipSwitch} {$this->model->numPort}", 'control');
            return ["maxportsecurity" => trim("{$macadd}/{$portSec}")];
        } else {
            $this->Set_Control($this->model->serverId, "Ошибка при выполнении команды show run interfaces {$this->model->ipSwitch} {$this->model->numPort}", 'error');
            return $result;
        }
    }

//Получение данных по по статусу порта
//требуется параметры:
//                      showDataSSH
    public function showDataSSH()
    {
        $this->connect();
        $typehard = $this->getHardwareType();

        $comand = [
            "show run interface  {$this->model->numPort}",
        ];
        $result = $this->sendCommand($comand);
        $result = strtolower($result);

        $vlanInfo = $this->getVlanInfo($result);
        $portSec = $this->getPortSecurity($result, $typehard);

        if ($typehard == "4948") {
            $comand = [
                "sh mac add int {$this->model->numPort} | count dynamic|static",
            ];
        } else {
            $comand = [
                "sh mac add int {$this->model->numPort} | i Total",
            ];
        }
        $result = $this->sendCommand($comand);
        $result = strtolower($result);
        $macadd = $this->getMacAdd($result, $typehard);

        $comand = [
            "show int statu | i {$this->model->numPort} ",
        ];
        $result = $this->sendCommand($comand);
        $result = strtolower($result);
        $status = $this->getStatusData($result);

        if (stripos($result, "error:") === false) {
            $this->Set_Control($this->model->serverId, "show run interfaces {$this->model->ipSwitch} {$this->model->numPort}", 'control');
            return ["vlan" => $vlanInfo,
                "speed" => $status[3],
                "duplex" => $status[2],
                "status" => $status[0],
                "maxportsecurity" => "{$macadd}/{$portSec}"];
        } else {
            $this->Set_Control($this->model->serverId, "Ошибка при выполнении команды show run interfaces {$this->model->ipSwitch} {$this->model->numPort}", 'error');
            return $result;
        }
    }

//Соединение с коммутатором
    private function connect()
    {
        $this->connection = ssh2_connect($this->model->ipSwitch, 22);
        if (!$this->connection) {
            $this->Set_Control($this->model->serverId, "Ошибка подключения к свичу ip: {$this->model->ipSwitch}; port: 22", 'error');
            $code = "H1000015";
            $this->setErrors($code, "Switch error connecting ip: {$this->model->ipSwitch}; port: 22. Error code: $code.");
            return $this->response;
        }

        if (!ssh2_auth_password($this->connection, $this->model->config['service']['CommutatorSSH']['login'], $this->model->config['service']['CommutatorSSH']['pass'])) {
            $this->Set_Control($this->model->serverId, "Ошибка авторизации при подключении к свичу " . $this->model->ipSwitch, 'error');
            $code = "H1000016";
            $this->setErrors($code, "Authorisation error on switch connecting ip: {$this->model->ipSwitch}; port: 22. Error code: $code.");
            return $this->response;
        }
        $this->shell = ssh2_shell($this->connection, 'xterm', NULL, 400, 400, SSH2_TERM_UNIT_CHARS);
        if (!$this->shell) {
            $this->Set_Control($this->model->serverId, "Ошибка открытия потока при подключении к свичу " . $this->model->ipSwitch, 'error');
            $code = "H1000017";
            $this->setErrors($code, "Error opening stream on switch connecting ip: {$this->model->ipSwitch}; port: 22. Error code: $code.");
            return $this->response;
        }
        stream_set_blocking($this->shell, FALSE);
    }

//Отправка команд на коммутатор
    private function sendCommand($comands)
    {
        $continueCommand = [
            "sh mac add int",
            "show run inter",
            "show int statu",
            "wr",
        ];
        try {
            $res = "";
            foreach ($comands as $cmm) {
                $usek = substr($cmm, 0, 14);
                fwrite($this->shell, $cmm . PHP_EOL);
                if (in_array($usek, $continueCommand)) {
                    usleep(500000);
                } else {
                    usleep(300000);
                }
                while ($buff = fgets($this->shell)) {
                    $res .= $buff;
                }

                if (!(stripos($res, "Invalid") === false) || !(stripos($res, "Incomplete") === false)) {
                    $res = "Error: " . $res;
                    break;
                }
            }

            $res = str_ireplace("\n", "", $res);
            $res = str_ireplace("\r", "", $res);
            $res = preg_replace('|[\s]+|s', ' ', $res);

//Может быть проще разбирать вывод в массив
//            $res = explode("\r\n", $res);
//            $res = array_diff($res, array(''));
//            $strI = array_search("!", $res);
//            $res = array_splice($res, $strI);
            return $res;
        } catch (Exception $e) {
            return ("Error port On procedure: " . $e);
        }
    }

//Получить тип оборудования из таблицы hardware
    private function getHardwareType()
    {
        if (!$this->model->serverlinkid) {
            $this->Set_Control($this->model->serverId, "Не получен параметр: serverLinkId; IP=" .
            $this->model->ipSwitch ?: "none" . "; Port=" . $this->model->numPort ?: "none", 'error');
            $code = "H1000018";
            $this->setErrors($code, "Missing parameter serverLinkId. Error code: $code. IP=".
                             $this->model->ipSwitch ?: "none" . "; Port=" . $this->model->numPort ?: "none");
            return $this->response;
        }

        $hardwareLinks = \common\models\HardwareLinks::findOne(['Component_fk_id' => $this->model->serverlinkid]);
        $this->hardwareType = $hardwareLinks->hardwarefk;
        /*
                $hardware_query = "SELECT hardware.name AS name
                                     FROM hardware
                                     INNER JOIN hardware_links ON hardware_links.hardware_fk_id=hardware.id
                                     where hardware_links.Component_fk_id=" . $this->model->serverlinkid . "
                                     ORDER BY hardware.type";
                $this->hardwareType = Zero_DB::Select_Row($hardware_query);
        */

        if (isset($this->hardwareType['name'])) {
            if (preg_match("/(2950)|(2960)|(4948)|(3550)|(3560)|(3750)/", $this->hardwareType['name'], $typehard)) {
                return $typehard[0];
            } else {
                $this->Set_Control($this->model->serverId, "Неизвестный тип оборудования в таблице hardware " . $this->model->ipSwitch, 'error');
                $code = "H1000019";
                $this->setErrors($code, "Equipment type unknown. Error code: $code.");
                return $this->response;
            }
        } else {
            $this->Set_Control($this->model->serverId, "Не найден тип оборудования в таблице hardware " . $this->model->ipSwitch, 'error');
            $code = "H1000020";
            $this->setErrors($code, "Equipment type unknown. Error code: $code.");
            return $this->response;
        }

        return "";
    }

//Выбрать из строки возврата инфу по вланам
    private function getVlanInfo($result)
    {
        function isMode($result, $mode)
        {
            if ($mode == "trunk") {
                $isk1 = "trunk allowed vlan";
                $isk2 = "trunk native vlan";
                $offset1 = 18;
                $offset2 = 17;
                $prefix1 = "allowed: ";
                $prefix2 = "native: ";
            } else {
                $isk1 = "access vlan";
                $isk2 = "";
                $offset1 = 11;
                $offset2 = 0;
                $prefix1 = "native: ";
                $prefix2 = "";
            }

            $startpos = strripos($result, $isk1);
            if ($startpos) {
                $strvlan = trim(substr($result, $startpos + $offset1));
            } else {
                $strvlan = "1 ";

//                $result .= "error: ";
//                $this->Set_Control($this->serverId, "Ошибка при выполнении команды show run interfaces {$this->ipSwitch} {$this->numPort}");
//                echo $result;
//                exit;
            }

            $finpos = stripos($strvlan, " ");
            if ($finpos) {
                $strvlan1 = $prefix1 . trim(substr($strvlan, 0, $finpos));
            } else {
                $result .= " error: ";
                $this->Set_Control($this->model->serverId, "Ошибка при выполнении команды show run interfaces {$this->model->ipSwitch} {$this->model->numPort}", 'error');
                $code = "H1000004";
                $this->setErrors($code, $result." executing command Show Run Interfaces. Error code: $code.");
                return $this->response;
            }

            if ($isk2 <> "") {
                $startpos = strripos($result, $isk2);

                if ($startpos) {
                    $strvlan = trim(substr($result, $startpos + $offset2));
                } else {
                    $strvlan = "none ";                 //если в выдаче строка mode trunk есть, а строки trunk native vlan, значит возвращаем "native: none"
                }

                $finpos = stripos($strvlan, " ");
                if ($finpos) {
                    $strvlan2 = $prefix2 . trim(substr($strvlan, 0, $finpos));
                    $strvlan1 = " | " . $strvlan1;
                } else {
                    $result .= " error: ";
                    $this->Set_Control($this->model->serverId, "Ошибка при выполнении команды show run interfaces {$this->model->ipSwitch} {$this->model->numPort}", 'error');
                    $code = "H1000005";
                    $this->setErrors($code, $result." executing command Show Run Interfaces. Error code: $code.");
                    return $this->response;
                }
            } else {
                $strvlan2 = "";
            }

            return $strvlan2 . $strvlan1;
        }

        function isNoMode($result)
        {
            $startpos = strripos($result, "vlan");
            if ($startpos) {
                $strvlan = substr($result, $startpos);
            } else {
                $result .= " error: ";
                $this->Set_Control($this->model->serverId, "Ошибка при выполнении команды show run interfaces {$this->model->ipSwitch} {$this->model->numPort}", 'error');
                $code = "H1000006";
                $this->setErrors($code, $result." executing command Show Run Interfaces. Error code: $code.");
                return $this->response;
            }

            $finpos = stripos($strvlan, "switchport");
            if ($finpos) {
                $strvlan = substr($strvlan, 4, $finpos - 4);
            } else {
                $result .= " error: ";
                $this->Set_Control($this->model->serverId, "Ошибка при выполнении команды show run interfaces {$this->model->ipSwitch} {$this->model->numPort}", 'error');
                $code = "H1000007";
                $this->setErrors($code, $result." executing command Show Run Interfaces. Error code: $code.");
                return $this->response;
            }
            return $strvlan;
        }

        $startpos = strripos($result, "mode");
        if ($startpos) {
            $strvlan = trim(substr($result, $startpos + 4));
            $finpos = stripos($strvlan, " ");
            if ($finpos) {
                $mode = trim(substr($strvlan, 0, $finpos));
                $itog = isMode($result, $mode);
            } else {
                $itog = isNoMode($result);
            }
        } else {
            $itog = isNoMode($result);
        }


        return trim($itog);
    }

//Выбрать из строки возврата инфу по port security
    private function getPortSecurity($result, $typehard)
    {
        $startpos = stripos($result, "port-security maximum");
        if ($startpos) {
            $strPS = trim(substr($result, $startpos + 21));
        } else {
            return "none";
        }

        $finpos = stripos($strPS, " ");
        if ($finpos) {
            $strPS = substr($strPS, 0, $finpos);
        } else {
            $result .= " error: ";
            $this->Set_Control($this->model->serverId, "Ошибка при выполнении команды show run interfaces {$this->model->ipSwitch} {$this->model->numPort}", 'error');
            $code = "H1000008";
            $this->setErrors($code, $result." executing command Show Run Interfaces. Error code: $code.");
            return $this->response;
        }
        return trim($strPS);
    }

//Выбрать из строки возврата инфу мак-адресам
    private function getMacAdd($result, $typehard)
    {
        if ($typehard == "4948") {
            $needle = "=";
        } else {
            $needle = ":";
        }
        $startpos = strripos($result, $needle);
        if ($startpos) {
            $strPS = trim(substr($result, $startpos + 1));
            if (preg_match("/^\d+/", $strPS, $strPS)) {
                $strPS = $strPS[0];
            } else {
                $strPS = "";
            }
        } else {
            if ($typehard <> "4948") {
                $strPS = "0";
            } else {
                $result .= " error: ";
                $this->Set_Control($this->model->serverId, "Ошибка при выполнении команды show run interfaces {$this->model->ipSwitch} {$this->model->numPort}", 'error');
                $code = "H1000009";
                $this->setErrors($code, $result." executing command Show Run Interfaces. Error code: $code.");
                return $this->response;
            }
        }
        return trim($strPS);
    }

//Выбрать из строки возврата инфу по port security
    private function getStatusData($result)
    {
        $status = substr($result, strripos($result, $this->model->numPort) + strlen($this->model->numPort) + 1);
        return explode(" ", $status);
    }

    /*
     *Перенос параметров из $_REQUEST в свойства класса
     */

    private function Set_Control($serverId, $description, $typ = '')
    {
        settype($serverId, 'int');
        if (!$serverId)
            return 0;

        if (!$this->model->login && isset(Yii::$app->user->identity->Login)) {
            $login = Yii::$app->user->identity->Login;
            $name = Yii::$app->user->identity->Name;
        } elseif(!$this->model->login) {
            $login = 'system';
            $name = 'system';
        } else {
            $login = $this->model->login;
            $name = $this->model->name;
        }

        $history1 = new \common\models\History1;
        $history1->Component_fk_id = $serverId;
        $history1->description = "[" . $name . "] " . trim($description);
        $history1->Typ = trim($typ);
        $history1->login = trim($login);
        $res = $history1->save();
        if (!$res) {
            return $history1->errors();
        }
        return $res;
    }

    /**
     * Set errors
     * @param mixed $errorNumber
     * @param string $errorMsg
     * @return array
     */
    private function setErrors($errorNumber, $errorMsg) {
        $this->response['errors'][$errorNumber] = $errorMsg;
        return $this->response['errors'];
    }
}
