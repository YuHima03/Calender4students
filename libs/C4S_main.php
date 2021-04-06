<?php

include_once "main.php";

class page{
    private $relPATH = "./";
    private $page_info = [
        "TITLE" => "Calender4Students",
        "DESC"  => "学生のためのカレンダー 名称は未定です...",
        "URL"   =>  "undecided",
        "IMAGE" =>  "undecided"
    ];
    private $account = null;
    private $account_info = [];
    private $gen_opt = [
        "header"        =>  true,
        "header_add"    =>  [],
        "footer"        =>  true,
    ];
    private $gen_flag = [
        "put_PHP_data"  =>  false,
        "head"  =>  false,
        "body"  =>  false,
        "html_start"    =>  false,
        "html_end"      =>  false
    ];
    private $lang_data = [];

    function __construct($auto_page_moving = true){
        $this->relPATH = URI::RELATIVE_PATH();

        $this->account = new account($auto_page_moving);
        $this->account_info = $this->account->getinfo();

        $lang = "";
        if(isset($_GET["lang"])){
            //GETが最優先
            $lang = $_GET["lang"];
        }
        else if(isset($_COOKIE["_lang"])){
            //GETがない場合はCookieを参照
            $lang = $_COOKIE["_lang"];
        }
        //チェック
        if(preg_match("/^(JA|ja|EN|en)$/", $lang)){
            switch($lang){
                case("JA"):
                case("ja"):
                    $lang = "JA";
                    break;
                case("EN"):
                case("en"):
                    $lang = "EN";
                    break;                    
            }
        }
        else{
            $lang = "JA";
        }
        //クッキーに保存
        setcookie("_lang", $lang, 0, "/");

        //言語データ取得
        $this->lang_data = json_decode(file_get_contents("{$this->relPATH}lang/{$lang}.json"), true);
    }

    public function set_info($arr){
        //ページ情報設定
        foreach($arr as $key => $value){
            $this->page_info[$key] = $value;
        }
    }

    public function get_info($key = null){
        return (isset($key)) ? $this->page_info[$key] : $this->page_info;
    }

    public function get_account_info(){
        return $this->account_info;
    }

    //ページ生成関連
    public function set_gen_option($arr){
        foreach($arr as $key => $value){
            $this->gen_opt[$key] = $value;
        }
        return true;
    }

    /**
     * ページ生成
     * @param string $mode モード``(_ALL,head,body)`` _オプションはスラッシュの後に_
     * @param string|array $inner_html
     */
    public function gen_page(string $mode = "_ALL", $inner_html = null) :bool {
        $tmp = preg_split("/\//", $mode);
        $mode = $tmp[0];
        $option = (isset($tmp[1])) ? $tmp[1] : null;

        $mode_list = ["head", "body"];

        if(in_array($mode, $mode_list, true)){
            include "{$this->relPATH}include/{$mode}.php";
        }
        //print_all
        else if($mode === "_ALL"){
            foreach($key as $mode_list){
                $this->gen_page($key, ((isset($option[$key])) ? $option[$key] : null));
            }
        }

        return true;
    }

    //add_css
    public function add_css($href) {
        if(is_array($href)){
            $ret_data = "";
            foreach($href as $v){
                $ret_data .= $this->add_css($v);
            }
            return $ret_data;
        }
        else{
            $href = $this->relPATH . $href;
            return "<link rel='stylesheet' href='{$href}' />";
        }

        return false;
    }

    //add_js
    public function add_js($href) {
        if(is_array($href)){
            $ret_data = "";
            foreach($href as $v){
                $ret_data .= $this->add_js($v);
            }
            return $ret_data;
        }
        else{
            $href = $this->relPATH . $href;
            return "<script src='{$href}'></script>";
        }
    }

    //SW登録
    public function sw_reg() : string{
        return $this->add_js("app/app.js");
    }

    /** jsに変数を渡す */
    public function put_data(array $data, bool $force = false) :string{
        if(!$this->gen_flag["put_PHP_data"] || $force){
            $ret = "<script>PHP_DATA = " . json_encode($data) . ";</script>";
            $this->gen_flag["put_PHP_data"] = true;
            return $ret;
        }
    }

    /**
     * JSON形式の言語データを取得
     * @return array
     */
    public function get_lang_data(){
        return $this->lang_data;
    }
}

class account{
    private $info = [
        "login"     =>  false,
        "uuid"      =>  null,
        "name"      =>  null,
        "error"    =>  [],
        "unclaimed" =>  false
    ];
    private $relPATH = "./";

    function __construct($auto_page_moving = true){
        $this->relPATH = URI::RELATIVE_PATH();

        $DB = new database();

        //失敗時にリダイレクトする相対パス
        $moveto = null;

        if(isset($_SESSION['_token']) && isset($_COOKIE['_token'])){
            try{
                $DB->connect();
                //ログイン情報をDBと照合
                $token = [$_SESSION['_token'], $_COOKIE['_token']];

                $sql = "SELECT * FROM `account`, `login_session` WHERE `account`.`uuid` = `login_session`.`uuid` AND `login_session`.`session_token`=?";
                $stmt = $DB->getPDO()->prepare($sql);
                $stmt->execute([$token[0]]);
                $data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if($data["cookie_token"] == $token[1]){
                    if(strtotime($data["start_date"])+6*30*24*60*60 > time()){
                        //認証完了
                        $this->info["uuid"] = $data["uuid"];
                        $this->info["unclaimed"] = (bool)$data["unclaimed"];
                        $this->info["name"] = ($this->info["unclaimed"]) ? "Guest" : $data["name"];
                        $this->info["login"] = true;

                        //最終ログイン日時更新
                        $sql = "UPDATE `login_session` SET `start_date`=? WHERE `session_token`=? AND `cookie_token`=?";
                        $stmt = $DB->prepare($sql);
                        $stmt->execute([date("Y-m-d", time()), $_SESSION["_token"], $_COOKIE["_token"]]);
                    }
                    else{
                        //セッション切れ
                        $this->info["error"][] = "ERR_SESSION_EXPIRED";

                        $moveto = URI::LOGIN_PAGE(true, ["mode"=>"update"]);
                    }
                }
                else{
                    //不正ログイン？
                    $this->info["error"][] = "BAD_LOGIN_REQUEST";
                    echo "ログアウトしました";

                    //ページ再読み込み
                    $moveto = URI::LOGIN_PAGE(true, ["mode"=>"retry"]);
                }

                //切断
                $DB->disconnect();
            }
            catch(Exception $e){
                //DBに接続できなかったとき
                $this->info["error"][] = "DB_CONNECTION_REFUSED";
                return;
            }
        }
        else{
            $moveto = URI::LOGIN_PAGE(true);
        }

        if(is_string($moveto) && $auto_page_moving){
            header("Location: {$moveto}");
        }
    }

    /** 
     * @return array
     * @comment ログインしてるかは ``isset(obj名->getstatus()["uuid"])`` で確認可能
     * */
    public function getinfo(){
        return $this->info;
    }

    /**
     * ログアウトする
     * @param string $mode モード("force"で強制ログアウト)
     * @param string|false $moveto
     */
    public function logout($mode = "normal", $moveto = null){
        if(isset($this->info["uuid"]) || $mode == "force"){
            $DB = new database();
            if($DB->connect()){
                //DBから削除
                $sql = "DELETE FROM `login_session` WHERE `session_token`=?";
                $stmt = $DB->getPDO()->prepare($sql);
                $stmt->execute([$_SESSION['_token']]);
                unset($stmt);

                if(isset($this->info["uuid"]) && $this->info["unclaimed"]){
                    //仮登録アカウントはアカウント情報もDBから削除
                    $sql = "DELETE FROM `account` WHERE `uuid`=?";
                    $stmt = $DB->getPDO()->prepare($sql);
                    $stmt->execute([$this->info["uuid"]]);
                }

                //セッション吹っ飛ばす
                unset($_SESSION['_token']);
                //クッキー吹っ飛ばす
                setcookie("__session", "", time()-1800, "/");
                setcookie("_token", "", time()-1800, "/");

                $DB->disconnect();
            }

            if($moveto !== false){
                header("Location: {$moveto}");
            }
        }
    }
}

//////////////////////////////////////////////////
//アカウント作成
class create_account{
    private $relPATH = "./";

    function __construct(){
        $this->relPATH = URI::RELATIVE_PATH();
    }

    /** 
     * アカウント作成（ログイン）
     * @param string $name アカウント名(他アカウントとの重複不可)
     * @param string $pass パスワード(ハッシュ化してないやつ)※垢名との重複不可
     * @param bool $unclaimed ```true```で仮登録
     * @param bool $login ログイン処理もするかどうか(true/false)
     * */
    public function create($name, $pass, $unclaimed = false, $login = true){
        $DB = new database();
        $pass = hash("sha512", $pass);
        $uuid = "";

        //アカウント登録
        try{
            $DB->connect();
            $sql = "INSERT INTO `account` (`uuid`, `name`, `password`, `unclaimed`) VALUES (?, ?, ?, ?)";
            $stmt = $DB->getPDO()->prepare($sql);
            do{
                $uuid = genUUID();
                if($pass == $uuid){
                    continue;
                }
                else{
                    $arr = [$uuid, $name, $pass, (int)$unclaimed];
                }
            }while(!$stmt->execute($arr));

            //データ保存フォルダ作成
            $dataPath = $this->relPATH . "data/" . $uuid;
            mkdir($dataPath, 0700);
            $dataJSON = fopen($dataPath . "/_sch.json", "w+");
            fclose($dataJSON);

            //ログイン処理
            if($login){
                //アカウントのIDを取得
                $sql = "SELECT `uuid` FROM `account` WHERE `name`=? AND `password`=?";
                $stmt = $DB->getPDO()->prepare($sql);
                $stmt->execute([$name, $pass]);
                $uuid = $stmt->fetch(PDO::FETCH_ASSOC)["uuid"];

                //セッションに登録
                $sql = "INSERT INTO `login_session` (`uuid`, `start_date`, `session_token`, `cookie_token`, `auto_login`) VALUES (?, ?, ?, ?, 1)";
                $stmt = $DB->getPDO()->prepare($sql);
                do{
                    $start_date = date("Y-m-d", time());
                    $session_token = rand_text();
                    $cookie_token = rand_text();
                    if($session_token == $cookie_token){
                        continue;
                    }
                    else{
                        $arr = [$uuid, $start_date, $session_token, $cookie_token];
                    }
                }while(!$stmt->execute($arr));

                //クッキー&セッションに登録
                $_SESSION['_token'] = $session_token;
                setcookie("_token", $cookie_token, time()+60*60*24*30*6, "/", "", false, true); //セッションは半年保持
            }

            $DB->disconnect();
        }
        catch(Exception $e){
            return false;
        }

        return true;
    }
}

//////////////////////////////////////////////////
//データベース
class database{
    private $mysql = null;
    private $ini_data = null;
    private $relPath = "./";

    function __construct(){
        $this->relPath = URI::RELATIVE_PATH();
        $this->ini_data = parse_ini_file($this->relPath . "libs/PDO_data.ini");
    }

    public function is_connected(){
        return isset($this->mysql);
    }

    public function connect($auto_err_proc = true){
        //ini_dataはiniファイルから取得するデータベース情報ね
        if(isset($this->ini_data["user"]) && isset($this->ini_data["pass"])){
            try{
                $this->mysql = new PDO("mysql:dbname=C4S;host=localhost", $this->ini_data["user"], $this->ini_data["pass"]);
            }
            catch(Exception $e){
                if($auto_err_proc){
                    header("Location: {$this->relPath}err/?errcode=ERR_DB_CONNECTING_REFUSED&to={$_SERVER['PHP_SELF']}");
                    exit();
                }
                else{
                    throw new Exception("ERR_DB_CONNECTING_REFUSED");
                    return false;
                }
            }
        }
        else{
            return false;
        }

        return true;
    }

    public function disconnect(){
        $this->mysql = null;
        return true;
    }

    public function execute($text){
        if($this->is_connected()){
            $fst = preg_split("/ /", $text)[0];
            $result = null;
            try{
                if ($fst == "SELECT" or $fst == "select"){ //select文
                    $result = $this->mysql->query($text)->fetchAll(PDO::FETCH_ASSOC);
                    return $result;
                    
                }
                if ($fst == "INSERT" or $fst == "insert"){ //insert文
                    try{
                        $this->mysql->query($text);
                    }
                    catch(PDOException $e){
                        return false;
                    }
                    return true;
                }
                else {
                    $this->mysql->query($text);
                    return true;
                }
            }
            catch (Exception $e){
                echo $e;
                $this->errors[] = "DB_PROC_ERROR";
                return false;
            }
        }
        else{
            throw new Exception("ERR_DB_CONNECTION_REFUSED");
            return false;
        }
    }

    public function prepare(string $sql){
        return (($this->is_connected()) ? $this->mysql->prepare($sql) : false);
    }

    public function getPDO(){
        return $this->mysql;
    }
}

//////////////////////////////////////////////////
//ランダムな文字列を返す($lenの長さの文字列を$modeでハッシュ化する)
function rand_text($len = 128, $mode = "sha256"){
    $rand_b = openssl_random_pseudo_bytes($len);
    return ($mode == "none") ? bin2hex($rand_b) : hash($mode, $rand_b);
}

//////////////////////////////////////////////////
/**
 * UUIDを生成 (version4)
 * @return string
 */
function genUUID(){
    //128バイトの乱数生成 (バージョン等指定するために論理積を取る)
    //RRRRRRRR-RRRR-4RRR-rRRR-RRRRRRRRRRRR -> (hex)FFFFFFFF-FFFF-4FFF-rFFF-FFFFFFFFFFFF (r=8,9,A,B)
    $binText_OR  = hex2bin('00000000'.'0000'.'4000'.'8000'.'000000000000');
    $binText_AND = hex2bin('FFFFFFFF'.'FFFF'.'4FFF'.'BFFF'.'FFFFFFFFFFFF');
    $uuidStr     = bin2hex((random_bytes(16) | $binText_OR) & $binText_AND);

    return substr($uuidStr, 0, 8) . '-' . substr($uuidStr, 8, 4) . '-' . substr($uuidStr, 12, 4) . '-' . substr($uuidStr, 16, 4) . '-' . substr($uuidStr, 20);
}

?>