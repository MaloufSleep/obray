<?php

use Illuminate\Contracts\Container\Container;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class OObject
{
    public $data = null;
    public $errors = null;

    protected array $permissions = [];

    // private data members
    private $delegate = FALSE;                                                                    // does this object have a delegate [TO BE IMPLEMENTED]
    private $starttime;                                                                            // records the start time (time the object was created).  Cane be used for performance tuning
    private $is_error = FALSE;                                                                    // error bit
    private $status_code = 200;                                                                    // status code - used to translate to HTTP 1.1 status codes
    private $content_type = 'application/json';                                                    // stores the content type of this class or how it should be represented externally
    private $path = '';                                                                            // the path of this object
    private $missing_path_handler;                                                                // if path is not found by router we can pass it to this handler for another attempt
    private $missing_path_handler_path;                                                            // the path of the missing handler
    private $access;
    public static $handleExceptions = true;

    /**
     * @var \Illuminate\Contracts\Container\Container
     */
    private static $container = null;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;

    // public data members
    public $object = ''; // stores the name of the class
    protected Throwable $lastException;
    public $objectType;
    public $components;
    public $rPath;
    public $path_to_object;
    public $namespaced_path;
    public $namespaced_controller_path;
    public $deprecated_controller_path;
    public $namespaced_model_path;
    public $deprecated_model_path;
    public $params;
    public $runtime;

    public function console(...$args)
    {
        if (is_null($this->output)) {
            $this->output = new ConsoleOutput();
        }

        if (PHP_SAPI !== 'cli' || empty($args)) {
            // We only log to console when we are in console and there is actually something to log
            return;
        }

        if (is_array($args[0]) || is_object($args[0])) {
            $this->output->write(print_r($args[0], true));
        } elseif (count($args) === 3 && $args[1] !== NULL && $args[2] !== NULL) {
            $colors = array(
                // text color
                "Black" => "\033[30m",
                "Red" => "\033[31m",
                "Green" => "\033[32m",
                "Yellow" => "\033[33m",
                "Blue" => "\033[34m",
                "Purple" => "\033[35m",
                "Cyan" => "\033[36m",
                "White" => "\033[37m",
                // text color bold
                "BlackBold" => "\033[30m",
                "RedBold" => "\033[1;31m",
                "GreenBold" => "\033[1;32m",
                "YellowBold" => "\033[1;33m",
                "BlueBold" => "\033[1;34m",
                "PurpleBold" => "\033[1;35m",
                "CyanBold" => "\033[1;36m",
                "WhiteBold" => "\033[1;37m",
                // text color muted
                "RedMuted" => "\033[2;31m",
                "GreenMuted" => "\033[2;32m",
                "YellowMuted" => "\033[2;33m",
                "BlueMuted" => "\033[2;34m",
                "PurpleMuted" => "\033[2;35m",
                "CyanMuted" => "\033[2;36m",
                "WhiteMuted" => "\033[2;37m",
                // text color muted
                "BlackUnderline" => "\033[4;30m",
                "RedUnderline" => "\033[4;31m",
                "GreenUnderline" => "\033[4;32m",
                "YellowUnderline" => "\033[4;33m",
                "BlueUnderline" => "\033[4;34m",
                "PurpleUnderline" => "\033[4;35m",
                "CyanUnderline" => "\033[4;36m",
                "WhiteUnderline" => "\033[4;37m",
                // text color blink
                "BlackBlink" => "\033[5;30m",
                "RedBlink" => "\033[5;31m",
                "GreenBlink" => "\033[5;32m",
                "YellowBlink" => "\033[5;33m",
                "BlueBlink" => "\033[5;34m",
                "PurpleBlink" => "\033[5;35m",
                "CyanBlink" => "\033[5;36m",
                "WhiteBlink" => "\033[5;37m",
                // text color background
                "RedBackground" => "\033[7;31m",
                "GreenBackground" => "\033[7;32m",
                "YellowBackground" => "\033[7;33m",
                "BlueBackground" => "\033[7;34m",
                "PurpleBackground" => "\033[7;35m",
                "CyanBackground" => "\033[7;36m",
                "WhiteBackground" => "\033[7;37m",
                // reset - auto called after each of the above by default
                "Reset" => "\033[0m"
            );
            $color = $colors[$args[2]];
            $this->output->write(sprintf($color . array_shift($args) . "\033[0m", array_shift($args)));
        } elseif (count($args) === 2) {
            $this->output->write(sprintf(array_shift($args), array_shift($args)));
        }
    }

    public function setOutput(?OutputInterface $output)
    {
        $this->output = $output;
        return $this;
    }

    /***********************************************************************
     * ROUTE FUNCTION
     ***********************************************************************/

    public function route($path, $params = array(), $direct = TRUE)
    {
        if (!$direct) {
            $params = array_merge($params, $_GET, $_POST);
        }
        $cmd = $path;
        $this->params = $params;
        $components = parse_url($path);
        $this->components = $components;
        if (array_key_exists('query', $components)) {
            if (is_string($params)) {
                $params = array("body" => $params);
            }
            parse_str($components['query'], $tmp);
            $params = array_merge($tmp, $params);
        }
        if (array_key_exists('scheme', $components) && in_array($components['scheme'], ['http', 'https'])) {
            $port = $components['port'] ?? null;
            $path = $components["scheme"] . "://" . $components["host"] . (!empty($port) ? ':' . $port : '') . $components["path"];
        }

        /*********************************
         * handle remote HTTP(S) calls
         *********************************/
        if (isset($components['host']) && $direct) {
            $timeout = 5;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            // SET HEADERS
            $headers = array();
            $headers[] = "Expect: ";
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            if (defined('__OBRAY_REMOTE_HOSTS__') && defined('__OBRAY_TOKEN__') && in_array($components['host'], unserialize(__OBRAY_REMOTE_HOSTS__))) {
                $headers[] = 'Obray-Token: ' . __OBRAY_TOKEN__;
            }
            if (!empty($params['http_headers'])) {
                $headers = $params['http_headers'];
                unset($params["http_headers"]);
            }
            if (!empty($params['http_content_type'])) {
                $headers[] = 'Content-type: ' . $params['http_content_type'];
                $content_type = $params['http_content_type'];
                unset($params['http_content_type']);
            }
            if (!empty($params['http_accept'])) {
                $headers[] = 'Accept: ' . $params['http_accept'];
                unset($params['http_accept']);
            }
            if (!empty($params['http_username'])) {
                if (!empty($params['http_password'])) {
                    curl_setopt($ch, CURLOPT_USERPWD, $params['http_username'] . ":" . $params['http_password']);
                    unset($params['http_password']);
                } else {
                    curl_setopt($ch, CURLOPT_USERPWD, $params['http_username'] . ":");
                }

                unset($params['http_username']);
            }
            if (!empty($params['http_raw'])) {
                $show_raw_data = TRUE;
                unset($params['http_raw']);
            }
            if (!empty($params['http_debug'])) {
                $debug = TRUE;
                unset($params["http_debug"]);
            } else {
                $debug = FALSE;
            }
            if (!empty($params['http_user_agent'])) {
                curl_setopt($ch, CURLOPT_USERAGENT, $params["http_user_agent"]);
                unset($params["http_user_agent"]);
            }
            if ((!empty($this->params) && empty($params['http_method'])) || (!empty($params['http_method']) && $params['http_method'] == 'post')) {
                unset($params["http_method"]);
                if (count($params) == 1 && !empty($params["body"])) {
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $params["body"]);
                } else {
                    if (!empty($content_type) && $content_type == "application/json") {
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                        $json = json_encode($params);
                        $headers[] = 'Content-Length: ' . strlen($json);
                        curl_setopt($ch, CURLOPT_POST, 1);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
                    } else {
                        if ($debug) {
                            $this->console("\n\nPost Fields\n");
                            $this->console("Count: " . count($params) . "\n");
                            $this->console($params);
                        }
                        curl_setopt($ch, CURLOPT_POST, count($params));
                        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
                    }
                }
            } else if (!empty($params['http_method']) && $params['http_method'] == 'patch') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params["body"]);
            } else {
                if (!empty($params["http_method"])) {
                    unset($params["http_method"]);
                }
                if (!empty($components["query"])) {
                    $path .= "?" . $components["query"];
                }
                if ($debug) {
                    $this->console($path);
                }
                curl_setopt($ch, CURLOPT_HTTPGET, TRUE);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            }
            if ($debug) {
                $this->console($params);
            }

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_URL, $path);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_TIMEOUT, 400); //timeout in seconds

            $this->data = curl_exec($ch);
            if ($debug) {
                $this->console($this->data);
            }

            $info = curl_getinfo($ch);
            if ($debug) {
                $this->console("Info: " . print_r($info, true));
            }

            $data = json_decode($this->data);

            $info["http_code"] = intval($info["http_code"]);
            if ($info["http_code"] < 200 || $info["http_code"] >= 300) {
                //echo "HTTP CODE IS NOT 200";
                if (!empty($data->Message)) {
                    $this->throwError($data->Message, $info["http_code"]);
                } elseif (!empty($data->error)) {
                    $this->throwError($data->error);
                } elseif (!empty($data->errors)) {
                    $this->throwError("");
                    $this->errors = $data->errors;
                } else {
                    $this->throwError("An error has occurred with no message.", $info["http_code"]);
                }
                if (empty($this->data)) {
                    $this->data = array();
                }
                return $this;
            } else {
                if (!empty($data)) {
                    $this->data = $data;
                } else {
                    return $this;
                }
                if (!empty($this->data)) {
                    if (isset($this->data->errors)) {
                        $this->errors = $this->data->errors;
                    }
                    if (isset($this->data->html)) {
                        $this->html = $this->data->html;
                    }
                    if (isset($this->data->data) && empty($show_raw_data)) {
                        $this->data = $this->data->data;
                    }
                }
            }
        } else {
            /*********************************
             * Parse Path & setup params
             *********************************/

            $_REQUEST = $params;

            $path_array = preg_split('[/]', $components['path'], -1, PREG_SPLIT_NO_EMPTY);
            $base_path = $this->getBasePath($path_array);


            /*********************************
             * Validate Remote Application
             *********************************/

            $this->validateRemoteApplication($direct);

            /*********************************
             * SET CONTENT TYPE FROM ROUTE
             *********************************/

            if (isset($params['ocsv'])) {
                $this->setContentType('text/csv');
                unset($params['ocsv']);
            }
            if (isset($params['otsv'])) {
                $this->setContentType('text/tsv');
                unset($params['otsv']);
            }
            if (isset($params['otable'])) {
                $this->setContentType('text/table');
                unset($params['otable']);
            }

            /*********************************
             * CALL FUNCTION
             *********************************/

            if (empty($base_path) && count($path_array) == 1 && !empty($this->object) && $this->object != $path_array[0]) {

                return $this->executeMethod($path, $path_array, $direct, $params);
            }

            /*********************************
             * CREATE OBJECT
             *********************************/

            $obj = $this->createObject($path_array, $base_path, $params, $direct);
            if (empty($this->errors)) {
                return $obj;
            }

            /*********************************
             * FIND MISSING ROUTE
             *********************************/
            if ($this->status_code == 404) {
                return $this->findMissingRoute($components['path'], $params);
            }
        }

        return $this;
    }

    /***********************************************************************
     * VALIDATE REMOTE APPLICATION
     ***********************************************************************/
    public function validateRemoteApplication(&$direct)
    {
        $headers = getallheaders();

        if (isset($headers['Obray-Token'])) {
            $otoken = $headers['Obray-Token'];
            unset($headers['Obray-Token']);
            if (defined('__OBRAY_TOKEN__') && $otoken === __OBRAY_TOKEN__ && __OBRAY_TOKEN__ != '') {
                $direct = TRUE;
            }
        }
    }

    private function _namespacedClassExists($path, $obj_name)
    {
        $namespace_components = explode('/', $this->path);
        $namespace_components = array_filter($namespace_components, function ($item) {
            return !empty($item) && $item !== 'app';
        });
        array_pop($namespace_components);
        $namespace_str = '/' . implode('/', $namespace_components);
        $namespace = str_replace('/', '\\', str_replace(__OBRAY_NAMESPACE_ROOT__, '\\' . __OBRAY_APP_NAME__ . '\\', $namespace_str));
        $this->namespaced_path = $namespace . '\\' . $obj_name;
        return class_exists($this->namespaced_path);
    }

    /**
     * @return \Illuminate\Contracts\Container\Container
     */
    public static function getContainerSingleton()
    {
        return static::$container;
    }

    public static function setContainerSingleton(?Container $container)
    {
        static::$container = $container;
    }

    private function createObject($path_array, $base_path, &$params, $direct)
    {
        $path = '';
        $isNamespacedPath = false;
        $deprecatedControllersPath = __OBRAY_SITE_ROOT__ . 'controllers/';
        $namespacedControllersPath = __OBRAY_SITE_ROOT__ . "app/controllers/";
        $deprecatedModelsPath = $base_path;
        $namespacedModelsPath = __OBRAY_SITE_ROOT__ . "app/models/";
        $rPath = array();
        $obj_name_loop_counter = 0;
        $obj_name_loop_name_check = "";

        if (empty($path_array) && empty($this->object) && empty($base_path)) {
            $path_array[] = "index";
        }

        while (count($path_array) > 0) {
            if (
                empty($base_path)
                && (
                    is_dir($deprecatedControllersPath . implode('/', $path_array))
                    || is_dir($namespacedControllersPath . implode('/', $path_array))
                )
            ) {
                $path_array[] = $path_array[(count($path_array) - 1)];
            }

            $obj_name = array_pop($path_array);

            $this->namespaced_controller_path = $namespacedControllersPath . implode('/', $path_array) . '/c' . str_replace(' ', '', ucWords(str_replace('-', ' ', $obj_name))) . '.php';
            $this->deprecated_controller_path = $deprecatedControllersPath . implode('/', $path_array) . '/c' . str_replace(' ', '', ucWords(str_replace('-', ' ', $obj_name))) . '.php';

            $this->namespaced_model_path = $namespacedModelsPath . implode('/', $path_array) . '/' . $obj_name . '.php';
            $this->deprecated_model_path = $deprecatedModelsPath . implode('/', $path_array) . '/' . $obj_name . '.php';

            if (file_exists($this->namespaced_model_path)) {
                $objectType = "model";
                $this->path = $this->namespaced_model_path;
                $isNamespacedPath = true;
            } else if (file_exists($this->deprecated_model_path)) {
                $objectType = "model";
                $this->path = $this->deprecated_model_path;
            } else if (file_exists($this->namespaced_controller_path)) {
                $objectType = "controller";
                $obj_name = "c" . str_replace(' ', '', ucWords(str_replace('-', ' ', $obj_name)));
                $this->path = $this->namespaced_controller_path;
                if (empty($path)) {
                    $path = '/index/';
                }
                $isNamespacedPath = true;
            } else if (file_exists($this->deprecated_controller_path)) {
                $objectType = "controller";
                $obj_name = "c" . str_replace(' ', '', ucWords(str_replace('-', ' ', $obj_name)));
                $this->path = $this->deprecated_controller_path;
                // include the root controller
                if (file_exists(__OBRAY_SITE_ROOT__ . "controllers/cRoot.php")) {
                    if (class_exists("cRoot") || class_exists("\cRoot")) {
                        // do nothing
                    } else {
                        require_once __OBRAY_SITE_ROOT__ . "controllers/cRoot.php";
                    }
                }

                if (empty($path)) {
                    $path = '/index/';
                }
            }

            if (!empty($objectType)) {
                $doesNamespaceClassExist = $this->_namespacedClassExists($this->path, $obj_name);
                if (!class_exists($obj_name) && !$doesNamespaceClassExist) {
                    require_once $this->path;
                }
                $class_exists = false;

                if (class_exists($obj_name)) {
                    $class_exists = true;
                } else if ($doesNamespaceClassExist) {
                    $class_exists = true;
                    $obj_name = $this->namespaced_path;
                }
                if ($class_exists) {
                    try {
                        //	CREATE OBJECT
                        if ($isNamespacedPath && !is_null(static::getContainerSingleton())) {
                            $container = static::getContainerSingleton();
                            $obj = $container->make($obj_name, [
                                'params' => $params,
                                'direct' => $direct,
                                'rPath' => $rPath
                            ]);
                        } else {
                            $obj = new $obj_name($params, $direct, $rPath);
                        }
                        /** @var OObject $obj */
                        $obj->objectType = $objectType;
                        $obj->setObject(get_class($obj));
                        $obj->setContentType($obj->content_type);
                        $obj->path_to_object = implode('/', $path_array);
                        array_pop($rPath);
                        $obj->rPath = $rPath;
                        $obj->setOutput($this->output);

                        //	CHECK PERMISSIONS
                        $obj->checkPermissions('object', $direct);

                        //	SETUP DATABASE CONNECTION
                        if (method_exists($obj, 'setDatabaseConnection')) {
                            $obj->setDatabaseConnection(getDatabaseConnection());
                            $obj->setReaderDatabaseConnection(getReaderDatabaseConnection());
                        }

                        if ($path === $this->components['path']) {
                            $path = substr($path, strlen($this->components['path']));
                        }

                        //	ROUTE REMAINING PATH - function calls
                        if (!empty($path)) {
                            $obj->route($path, $params, $direct);
                        }

                        return $obj;
                    } catch (Exception $e) {
                        $code = (!empty($e->getCode())) ? $e->getCode() : 'general';
                        $this->throwError($e->getMessage(), 500, $code);
                        $this->logError(oCoreProjectEnum::OOBJECT, $e);
                    }
                }
                break;
            } else {
                $rPath[] = strtolower($obj_name);
                $path = '/' . $obj_name;

                if ($obj_name_loop_name_check === $obj_name) {
                    $obj_name_loop_counter++;
                    if ($obj_name_loop_counter > 10) {
                        break;
                    }
                }

                $obj_name_loop_name_check = $obj_name;
            }
        }
        //exit();
        $this->throwError('Route not found object: ' . $path, 404, 'notfound');
        return $this;
    }

    protected function getDeprecatedControllerPath(array $pathArray, string $objectName)
    {
        $deprecatedControllersPath = __OBRAY_SITE_ROOT__ . "controllers/";

        if (is_dir($deprecatedControllersPath . implode('/', $pathArray) . '/' . $objectName)) {
            $pathArray[] = $objectName;
        }

        return $deprecatedControllersPath . implode('/', $pathArray) . '/c' . str_replace(' ', '', ucWords(str_replace('-', ' ', $objectName))) . '.php';
    }

    /***********************************************************************
     * EXECUTE METHOD
     ***********************************************************************/
    private function executeMethod($path, $path_array, $direct, &$params)
    {

        $path = str_replace('-', '', $path_array[0]);

        if (method_exists($this, $path)) {
            try {
                $this->checkPermissions($path, $direct);
                if (!$this->isError()) {
                    $this->$path($params);
                }
            } catch (Exception $e) {
                $code = (!empty($e->getCode())) ? $e->getCode() : 'general';
                $this->throwError($e->getMessage(), 500, $code);
                $this->logError(oCoreProjectEnum::ODBO, $e);
            }
            return $this;
        } else if (method_exists($this, "index")) {
            try {
                $this->checkPermissions("index", $direct);
                if (!$this->isError()) {
                    $this->index($params);
                }
            } catch (Exception $e) {
                $code = (!empty($e->getCode())) ? $e->getCode() : 'general';
                $this->throwError($e->getMessage(), 500, $code);
                $this->logError(oCoreProjectEnum::ODBO, $e);
            }
            return $this;
        } else {
            return $this->findMissingRoute($path, $params);
        }
    }

    /***********************************************************************
     * CHECK PERMISSIONS
     ***********************************************************************/
    private function checkPermissions($object_name, $direct): void
    {
        // Only restrict permissions if the call is come from and HTTP request through router $direct === FALSE
        if ($direct) {
            return;
        }

        $perms = $this->getPermissions();

        // If specific action has no permissions defined, attempt to authorize on the generic "method" permission
        if (!isset($perms[$object_name]) && isset($perms['method'])) {
            $object_name = 'method';
        }

        //This is to add greater flexibility for using custom session variable for storage of user data
        $user_session_key = isset($this->user_session) ? $this->user_session : 'ouser';

        switch (true) {
            case !isset($perms[$object_name]):
                $this->throwError('You cannot access this resource.', 403, 'Forbidden');
                break;
            case $perms[$object_name] === 'any':
                break;
            case $perms[$object_name] === 'user':
                if (!isset($_SESSION[$user_session_key]) && isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
                    $this->route('/obray/OUsers/login/', array('ouser_email' => $_SERVER['PHP_AUTH_USER'], 'ouser_password' => $_SERVER['PHP_AUTH_PW']), TRUE);
                }

                if (!isset($_SESSION[$user_session_key])) {
                    $this->throwError('You cannot access this resource.', 401, 'Unauthorized');
                }
                break;
            case !isset($_SESSION[$user_session_key]): // Everything past this point requires an authenticated user
                $this->throwError('You cannot access this resource.', 401, 'Unauthorized');
                break;
            case is_int($perms[$object_name]):
                if (!isset($_SESSION[$user_session_key]->ouser_permission_level)) {
                    $this->throwError('You cannot access this resource.', 401, 'Unauthorized');
                } elseif (defined("__OBRAY_GRADUATED_PERMISSIONS__")) {
                    if ($_SESSION[$user_session_key]->ouser_permission_level < $perms[$object_name]) {
                        $this->throwError('You cannot access this resource.', 401, 'Unauthorized');
                    }
                } else {
                    if ($_SESSION[$user_session_key]->ouser_permission_level != $perms[$object_name]) {
                        $this->throwError('You cannot access this resource.', 401, 'Unauthorized');
                    }
                }
                break;
            case is_array($perms[$object_name]):
                $userPermissions = $_SESSION[$user_session_key]->permissions ?? [];
                $userRoles = $_SESSION[$user_session_key]->roles ?? [];

                if (array_key_exists('permissions', $perms[$object_name])) {
                    $allowedPermissions = $perms[$object_name]['permissions'];

                    if ($this->checkPermissionOrRole($allowedPermissions, $userPermissions)) {
                        break;
                    }
                }

                if (array_key_exists('roles', $perms[$object_name])) {
                    $allowedRoles = $perms[$object_name]['roles'];

                    if ($this->checkPermissionOrRole($allowedRoles, $userRoles)) {
                        break;
                    }
                }

                if (is_array($userRoles) && in_array("SUPER", $userRoles)) {
                    break;
                }

                $this->throwError('You cannot access this resource.', 403, 'Forbidden');
                break;
            default:
                $this->throwError('You cannot access this resource.', 403, 'Forbidden');
        }
    }

    /***********************************************************************
     * PARSE PATH
     ***********************************************************************/
    public function parsePath($path)
    {
        $path = preg_split('([\][?])', $path);
        if (count($path) > 1) {
            parse_str($path[1], $params);
        } else {
            $params = array();
        }
        $path = $path[0];

        $path_array = preg_split('[/]', $path, -1, PREG_SPLIT_NO_EMPTY);
        $path = '/';

        $routes = unserialize(__OBRAY_ROUTES__);
        if (!empty($path_array) && isset($routes[$path_array[0]])) {
            $base_path = $routes[array_shift($path_array)];
        } else {
            $base_path = '';
        }

        return array('path_array' => $path_array, 'path' => $path, 'base_path' => $base_path, 'params' => $params);
    }

    /***********************************************************************
     * GET BASE PATH - returns the path of a specified route
     ***********************************************************************/

    private function getBasePath(&$path_array)
    {
        $routes = unserialize(__OBRAY_ROUTES__);
        if (!empty($path_array) && isset($routes[$path_array[0]])) {
            $base_path = $routes[array_shift($path_array)];
        } else {
            $base_path = '';
        }
        return $base_path;
    }

    /***********************************************************************
     * CLEANUP FUNCTION - removes parameters form object for output
     *
     * The idea here is to prevent infromation from 'leaking'
     * that's not explicitly intended.
     ***********************************************************************/
    public function cleanUp()
    {
        if (!in_array($this->content_type, ['text/csv', 'text/tsv', 'text/table'])) {
            // remove all object keys not white listed for output - this is so we don't expose unnecessary information
            $keys = ['object', 'errors', 'data', 'runtime', 'html', 'recordcount'];
            if (__OBRAY_DEBUG_MODE__) {
                $keys[] = 'sql';
                $keys[] = 'filter';
            }
            foreach ($this as $key => $value) {
                if (!in_array($key, $keys)) {
                    unset($this->$key);
                }
            }
        }
    }

    /***********************************************************************
     * IS OBJECT - Determines if path is an object
     ***********************************************************************/
    public function isObject($path)
    {
        $components = $this->parsePath($path);
        $obj_name = array_pop($components['path_array']);
        if (count($components['path_array']) > 0) {
            $seperator = '/';
        } else {
            $seperator = '';
        }
        $path = $components['base_path'] . implode('/', $components['path_array']) . $seperator . $obj_name . '.php';
        if (file_exists($path)) {
            if (!class_exists($obj_name)) {
                require_once $path;
            }
            if (class_exists($obj_name)) {
                return TRUE;
            }
        }

        return FALSE;
    }

    /***********************************************************************
     * FIND MISSING ROUTE
     ***********************************************************************/
    private function findMissingRoute($path, $params)
    {
        if (isset($this->missing_path_handler)) {
            include $this->missing_path_handler_path;

            /** @var \OObject $obj */
            $obj = new $this->missing_path_handler();
            $obj->setObject($this->missing_path_handler);

            $obj->setContentType($obj->content_type);

            //	SETUP DATABSE CONNECTION
            if (method_exists($obj, 'setDatabaseConnection')) {
                $obj->setDatabaseConnection(getDatabaseConnection());
                $obj->setReaderDatabaseConnection(getReaderDatabaseConnection());
            }

            //	CHECK PERMISSIONS
            $obj->checkPermissions('object', false);

            if ($obj->isError()) {
                return $obj;
            }

            //	ROUTE REMAINING PATH - function calls
            $obj->missing('/' . ltrim(rtrim($path, '/'), '/') . '/', $params, FALSE);

            return $obj;
        }

        return $this;
    }

    /***********************************************************************
     * ERROR HANDLING FUNCTIONS
     ***********************************************************************/
    public function throwError($message, $status_code = 500, $type = 'general')
    {
        $this->is_error = TRUE;
        if (empty($this->errors) || !is_array($this->errors)) {
            $this->errors = [];
        }
        $this->errors[$type][] = $message;
        $this->status_code = $status_code;
    }

    public function isError()
    {
        return $this->is_error;
    }

    public function getStackTrace($exception)
    {
        $stackTrace = "";
        $count = 0;
        foreach ($exception->getTrace() as $frame) {
            $args = "";
            if (isset($frame['args'])) {
                $args = array();
                foreach ($frame['args'] as $arg) {
                    if (is_string($arg)) {
                        $args[] = "'" . $arg . "'";
                    } elseif (is_array($arg)) {
                        $args[] = "Array";
                    } elseif (is_null($arg)) {
                        $args[] = 'NULL';
                    } elseif (is_bool($arg)) {
                        $args[] = ($arg) ? "true" : "false";
                    } elseif (is_object($arg)) {
                        $args[] = get_class($arg);
                    } elseif (is_resource($arg)) {
                        $args[] = get_resource_type($arg);
                    } else {
                        $args[] = $arg;
                    }
                }
                $args = join(", ", $args);
            }
            $stackTrace .= sprintf(
                "#%s %s(%s): %s(%s)\n",
                $count,
                $frame['file'],
                $frame['line'],
                $frame['function'],
                $args
            );
            $count++;
        }
        return $stackTrace;
    }

    /***********************************************************************
     * ROLES & PERMISSIONS FUNCTIONS
     ***********************************************************************/
    public function hasRole($code)
    {
        if ((!empty($_SESSION['ouser']->roles) && in_array($code, $_SESSION["ouser"]->roles)) || (!empty($_SESSION["ouser"]->roles) && in_array("SUPER", $_SESSION["ouser"]->roles))) {
            return TRUE;
        }
        return FALSE;
    }

    public function errorOnRole($code)
    {
        if (!$this->hasRole($code)) {
            $this->throwError("Permission denied", 403);
            return true;
        }
        return false;
    }

    public function hasPermission($code)
    {
        if ((!empty($_SESSION['ouser']->permissions) && in_array($code, $_SESSION["ouser"]->permissions)) || (!empty($_SESSION["ouser"]->roles) && in_array("SUPER", $_SESSION["ouser"]->roles))) {
            return TRUE;
        }
        return FALSE;
    }

    public function errorOnPermission($code)
    {
        if (!$this->hasPermission($code)) {
            $this->throwError("Permission denied", 403);
            return true;
        }
        return false;
    }

    /***********************************************************************
     * GETTER AND SETTER FUNCTIONS
     ***********************************************************************/
    private function setObject($obj)
    {
        $this->object = $obj;
    }

    public function getStatusCode()
    {
        return $this->status_code;
    }

    public function getContentType()
    {
        return $this->content_type;
    }

    public function setContentType($type)
    {
        if ($this->content_type != 'text/html') {
            $this->content_type = $type;
        }
    }

    public function getPermissions()
    {
        return isset($this->permissions) ? $this->permissions : array();
    }

    public function setMissingPathHandler($handler, $path)
    {
        $this->missing_path_handler = $handler;
        $this->missing_path_handler_path = $path;
    }

    public function dumpster($data, $force = false)
    {
        if ((defined("__LOCAL__") && __LOCAL__) || $force) {
            echo '<pre>';
            print_r($data);
            echo '</pre>';
        }
    }

    public function redirect($location = "/")
    {
        header('Location: ' . $location);
        die();
    }

    public function switchDB($db, $uname, $psswd)
    {
        global $conn;

        $conn = new PDO('mysql:host=' . __OBRAY_DATABASE_HOST__ . ';dbname=' . $db . ';charset=utf8', $uname, $psswd, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $conn;
    }

    /***********************************************************************
     *
     * RUN ROUTE IN BACKGROUND
     ***********************************************************************/

    public function routeBackground($route)
    {
        shell_exec(PHP_BINARY . " -d memory_limit=-1 " . __SELF__ . "tasks.php \"" . $route . "\" > /dev/null 2>&1 &");
    }

    /***********************************************************************
     *
     * LOGGING FUNCTIONS
     ***********************************************************************/

    public function logError($oProjectEnum, Exception $exception, $customMessage = "")
    {
        if (!static::$handleExceptions) {
            throw $exception;
        }
        $this->lastException = $exception;
        $logger = new oLog();
        $logger->logError($oProjectEnum, $exception, $customMessage);
    }

    public function logInfo($oProjectEnum, $message)
    {
        $logger = new oLog();
        $logger->logInfo($oProjectEnum, $message);
    }

    public function logDebug($oProjectEnum, $message)
    {
        $logger = new oLog();
        $logger->logDebug($oProjectEnum, $message);
    }

    public function getAppFolderName()
    {
        $namespaceRoot = explode('/', realpath(__OBRAY_NAMESPACE_ROOT__));
        return array_pop($namespaceRoot);
    }

    public function getLastException(): Throwable
    {
        return $this->lastException;
    }

    /**
     * @param $allowedList
     * @param array $list
     * @return void
     */
    protected function checkPermissionOrRole($allowedList, array $list): bool
    {
        if (is_array($allowedList)) {
            foreach ($allowedList as $allowedPermission) {
                if (in_array($allowedPermission, $list, true)) {
                    return true;
                }
            }
        }
        return false;
    }
}
