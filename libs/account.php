<?php

require_once __DIR__."/main.php";
require_once __DIR__."/db.php";

/**
 * アカウント管理
 * 同じページ内で複数存在するとバグるかも？
 */
class account{
    //アカウント全般のエラー
    /**不明なエラー */
    public const ERROR_UNKNOWN = 0;
    /**間違ったIDかパスワード */
    public const ERROR_LOGIN_INCORRECT = 1;
    /**削除されたアカウントへのログイン */
    public const ERROR_DELETED_ACCOUNT = 2;
    /**不正なログインリクエスト(不正なセッション情報) */
    public const ERROR_BAD_LOGIN_REQUEST = 3;
    /**セッション切れ */
    public const ERROR_SESSION_EXPIRED = 4;
    /**多すぎる再試行回数(無限ループの可能性) */
    public const ERROR_TOO_MUCH_RETRY = 5;

    //アカウント作成のエラー
    /**既に使用されている名前 */
    public const ERROR_USED_NAME = 10;
    /**条件を満たさない不正な書式 */
    public const ERROR_INCORRECT_FORMAT = 11;
    /**利用できない単語 */
    public const ERROR_BANNED_WORD = 12;


    //////////////////////////////////////////////////
    // 動的メソッド

    /**uuid */
    private ?string $uuid = null;

    private ?string $userName = null;

    /**マーカー */
    private int $marker = 0;

    /**権限 */
    private int $permission = 0;

    /**暗号化キー(32文字) */
    private ?string $encryptKey = null;

    /** @var array<int> */
    private ?int $lastError = null;

    /**ログインの種類 (`false` -> 新規, `true` -> 継続) */
    private bool $loginMode = false;

    /**アカウント情報を初期状態に(ログアウト時に実行すること多め) */
    private function setInitAccountInfo(){
        $this->uuid = null;
        $this->userName = null;
        $this->permission = 0;
        $this->loginMode = false;
        $this->encryptKey = null;
    }


    public function __construct(?string $userName = null, ?string $pass = null, bool $autoLogin = false){
        $this->login($userName, $pass, $autoLogin);
    }

    public function getUUID() :?string{
        return $this->uuid;
    }

    public function getEncryptKey() :?string{
        return $this->encryptKey;
    }

    public function getUserName() :?string{
        return $this->userName;
    }

    /**ログインしてるか否か */
    public function getLoginStatus() :bool {
        return is_string($this->uuid);
    }

    /**管理者かどうか */
    public function isAdmin() :bool {
        return ($this->permission === 5);
    }

    /**デベロッパかどうか */
    public function isDeveloper() :bool {
        return ($this->permission === 4);
    }

    /**エラー情報(番号)の取得 */
    public function getLastError() :?int{
        return $this->lastError;
    }

    /**デバッグ用の目印を取得 */
    public function getMarker() :int{
        return $this->marker;
    }

    /**
     * ログインする (ログイン情報の更新時のみ使用)
     * @param string|null $userName nullの場合はcookieとセッションより再ログイン
     * @param string|null $pass SHA256で暗号化したものをぶち込む
     * @return bool ログインの成功/失敗
     */
    public function login(?string $userName = null, ?string $pass = null, bool $autoLogin = false) :bool {
        try{
            $DB = new DB();

            if($DB->connect()){
                if(is_null($userName)){
                    //ログイン状態の継続
                    $this->loginMode = true;

                    //cookieとセッションの情報を照合して再ログイン
                    if(isset($_SESSION["_token"])){
                        $sql = "SELECT * FROM `account`, `login_session` WHERE `login_session`.`token`=? AND `account`.`uuid` = `login_session`.`uuid`";
                        $stmt = $DB->prepare($sql);

                        if($stmt->execute([$_SESSION["_token"]])){
                            if($stmt->rowCount() === 1){
                                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                                //判別用
                                $expireCheck = ((int)$result["auto_login"] === 1 && strtotime($result["last_login"]) + 60*60*24*30*6 >= time());
                                $autoLoginCheck = ((int)$result["auto_login"] === 0 && isset($_COOKIE["_session_flag"]) && $_COOKIE["_session_flag"] == "1");

                                if($expireCheck || $autoLoginCheck){
                                    if(is_null($result["delete_date"])){
                                        //再ログイン成功
                                        $token = $result["token"];
                                    }
                                    else{
                                        //アカウント削除済み
                                        $this->logout(true, account::ERROR_DELETED_ACCOUNT);
                                        return false;
                                    }
                                }
                                else{
                                    //セッション切れ
                                    $this->logout(true, account::ERROR_SESSION_EXPIRED);
                                    return false;
                                }
                            }
                            else{
                                //不正なリクエスト(セッションが存在しない)
                                $this->logout(true, account::ERROR_BAD_LOGIN_REQUEST);
                                return false;
                            }
                        }
                        else{
                            throw new Exception();
                        }
                    }
                    else{
                        //ログイン失敗(もとから非ログイン状態)
                        return false;
                    }
                }
                else if(is_string($userName)){
                    //新規ログイン
                    $this->loginMode = false;

                    if(strlen($pass) === 128){
                        $this->logout(true);

                        $sql = "SELECT * FROM `account` WHERE `name`=? AND `pass`=?";
                        $stmt = $DB->prepare($sql);

                        if($stmt->execute([$userName, $pass])){
                            if($stmt->rowCount() === 1){
                                //認証成功
                                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                                if(is_null($result["delete_date"])){
                                    $sql = "INSERT INTO `login_session` (`uuid`, `last_login`, `token`, `auto_login`) VALUES (?, ?, ?, ?)";
                                    $stmt = $DB->prepare($sql);

                                    //login_sessionに登録
                                    $retryConter = 0;
                                    do{
                                        if($retryConter < 10){
                                            $token = getRandStr(128, "sha256");
                                            $retryConter++;
                                        }
                                        else{
                                            //無限ループの可能性アリなのでエラーはいて終了
                                            $this->lastError = account::ERROR_TOO_MUCH_RETRY;
                                            return false;
                                        }
                                    }while(!$stmt->execute([$result["uuid"], date("Y-m-d"), $token, (int)$autoLogin]));

                                    unset($retryConter);

                                    //セッションにトークンを保存
                                    $_SESSION["_token"] = $token;

                                    //セッションのフラグを立てる(自動ログイン無効時の判定用)
                                    if(!$autoLogin){
                                        setcookie("_session_flag", "1", 0, "/", "", false, true);
                                    }

                                    //セッションID再生成
                                    session_regenerate_id(true);

                                    //最終ログイン日時(DBに保存してるやつより詳細なやつ)をセッションにも保存
                                    $_SESSION["_lastLoginTimestamp"] = time();
                                }
                                else{
                                    //削除されたアカウント
                                    $this->logout(true, account::ERROR_DELETED_ACCOUNT);
                                    return false;
                                }
                            }
                            else{
                                //認証失敗(入力情報に誤り?)
                                $this->logout(true, account::ERROR_LOGIN_INCORRECT);
                                return false;
                            }
                        }
                        else{
                            throw new Exception();
                        }
                    }
                    else{
                        throw new TypeError("`pass` must be hashed! (SHA512)");
                    }
                }
                else{
                    throw new TypeError("`userName` must be string or null!");
                }

                //ログイン成功
                $this->uuid = $result["uuid"];
                $this->userName = $result["name"];
                $this->permission = (int)$result["permission"];
                $this->encryptKey = $result["encrypt_key"];

                if($this->loginMode && $result["last_login"] !== date("Y-m-d")){
                    //DBのセッション情報更新
                    $sql = "UPDATE `login_session` SET `last_login`=? WHERE `token`=?";
                    $stmt = $DB->prepare($sql);

                    if(!$stmt->execute([date("Y-m-d"), $token])){
                        throw new Exception();
                    }
                }

                //resultを明示的に破棄
                unset($result);

                $DB->disconnect();

                return true;
            }
            else{
                throw new Exception();
            }
        }
        catch(PDOException $e){
            //重大なエラー
            exitWithErrorPage(FATAL_ERRORS::DB_OPERATION_FAILED);
        }
        catch(Exception $e){
            //不明なエラー
            $this->logout(true, account::ERROR_UNKNOWN);
            return false;
        }
    }

    /**
     * @param bool $force 強制ログアウト(セッション情報削除)
     */
    public function logout(bool $force = true, ?int $errorNum = null) :bool{
        //エラー処理
        if(is_int($errorNum)){
            $this->lastError = $errorNum;
        }

        //ログアウト処理
        if(isset($this->uuid) || $force){
            try{
                //セッションのトークンを消す
                if(isset($_SESSION["_token"])){
                    //ログインセッションから削除
                    $DB = new DB();
                    if($DB->connect()){
                        $sql = "DELETE FROM `login_session` WHERE `token`=?";
                        $stmt = $DB->prepare($sql);

                        if(!$stmt->execute([$_SESSION["_token"]])){
                            throw new Exception();
                        }
                    }
                    else{
                        throw new Exception();
                    }

                    unset($_SESSION["_token"]);
                }

                //自動ログインのフラグを消す
                if(isset($_COOKIE["_session_flag"])){
                    setcookie("_session_flag", "", time()-3600, "/");
                }

                $this->setInitAccountInfo();
            }
            catch(PDOException $e){
                //重大なエラー
                exitWithErrorPage(FATAL_ERRORS::DB_OPERATION_FAILED);
            }
            catch(Exception $e){
                //不明なエラー
                $this->lastError = account::ERROR_UNKNOWN;
                return false;
            }
        }

        return true;
    }

    /**
     * アカウント新規作成
     * @param string|null $userName 任意の文字列(半角英数字とアンダーバーで構成される4~256文字のID) (`null`で仮登録)
     * @param string|null $pass (SHA512での暗号化前)
     * @param array $option `birthday`: 誕生日(YYYY-MM-DD), `email`: メールアドレス
     * @param bool $login アカウント作成後に自動ログイン
     */
    public function create(?string $userName = null, ?string $pass = null, array $option = [], bool $login = true) :bool{
        try{
            if($this->getLoginStatus()){
                $this->logout(true);
            }

            $DB = new DB();

            if($DB->connect()){
                switch(checkNameExist($userName)){
                    case(false):
                        if(is_null($userName)){
                            //仮登録アカウント
                            $sql = "INSERT INTO `account` (`uuid`, `name`, `pass`, `encrypt_key`, `unclaimed`) VALUES (?, ?, ?, ?, 1)";
                            $stmt = $DB->prepare($sql);

                            $retryConter = 0;
                            do{
                                if($retryConter < 10){
                                    $uuid = getUUID();
                                    $name = getRandStr(32);
                                    $pass = getRandStr(128, "sha512");
                                    $encryptKey = getRandStr(32, null, true);

                                    $retryConter++;
                                }
                                else{
                                    $this->lastError = account::ERROR_TOO_MUCH_RETRY;
                                    return false;
                                }
                            }while($stmt->execute([$uuid, $name, $pass, $encryptKey]));
                        }
                        else{
                            $birthday = (isset($option["birthday"])) ? $option["birthday"] : null;
                            $email = (isset($option["email"])) ? $option["email"] : null;

                            //$userNameと$passの条件
                            //  $userName
                            //      - 半角英数字とアンダーバーの4~32文字
                            //  $pass
                            //      - 半角英数字と記号(!#$%&()~_\/+|=)の6~32文字
                            //      - 数字と半角英字がそれぞれ1文字以上
                            if(preg_match("/^[a-zA-Z0-9_]{4,32}$/", $userName) && preg_match("/^[a-zA-Z0-9_\!\#\$\%\&\(\)\~_\/\\\+\=\|]{6,32}$/", $pass) && preg_match("/\d+/", $pass) && preg_match("/[A-Za-z]+/", $pass) && preg_match("/^\d{4}-\d{2}-\d{2}$/", $birthday) && preg_match("/^\S+@\S+\.[^\.\s]+$/", $email)){
                                $pass = hash("sha512", $pass);

                                $sql = "INSERT INTO `account` (`uuid`, `name`, `pass`, `encrypt_key`, `birthday`, `email`) VALUES (?, ?, ?, ?, ?, ?)";
                                $stmt = $DB->prepare($sql);

                                $retryConter = 0;
                                do{
                                    if($retryConter < 10){
                                        $uuid = getUUID();
                                        $encryptKey = getRandStr(32, null, true);

                                        $retryConter++;
                                    }
                                    else{
                                        $this->lastError = account::ERROR_TOO_MUCH_RETRY;
                                        return false;
                                    }
                                }while($stmt->execute([$uuid, $userName, $pass, $encryptKey, $birthday, $email]));
                            }
                            else{
                                $this->lastError = account::ERROR_INCORRECT_FORMAT;
                                return false;
                            }
                        }

                        break;

                    case(true):
                        $this->lastError = account::ERROR_USED_NAME;
                        return false;

                    default:
                        $this->lastError = account::ERROR_UNKNOWN;
                        return false;
                }

                $DB->disconnect();

                //ログイン処理
                if($login){
                    return $this->login($userName, $pass);
                }
            }
            else{
                throw new Exception();
            }
        }
        catch(PDOException $e){
            exitWithErrorPage(FATAL_ERRORS::DB_OPERATION_FAILED);
        }
        catch(Exception $e){
            $this->lastError = account::ERROR_UNKNOWN;
            return false;
        }

        return true;
    }

    /**
     * アカウント削除 (ログアウト)
     * @param bool $completely 完全に削除(復元不可)にする (仮登録アカウント等に利用：まじで復元不可になるので取扱注意)
     */
    public function deleteAccount(bool $completely = false) :bool{
        try{
            if($this->getLoginStatus()){
                $DB = new DB();

                if($DB->connect()){
                    if($completely){
                        $sql = "DELETE FROM `account` WHERE `uuid`=?";
                        $stmt = $DB->prepare($sql);

                        if(!$stmt->execute([$this->uuid])){
                            throw new Exception();
                        }

                        //ファイル削除
                        if(isset($this->uuid)){
                            $dirn = dirname(__DIR__)."/data/{$this->uuid}";

                            if(!rmdir_all($dirn)){
                                throw new Exception();
                            }
                        }
                    }
                    else{
                        //完全削除でなく削除フラグを立てるだけ
                        $sql = "UPDATE `account` SET `delete_date`=? WHERE `uuid`=?";
                        $stmt = $DB->prepare($sql);

                        if(!$stmt->execute([date("Y-m-d"), $this->uuid])){
                            throw new Exception();
                        }
                    }

                    $DB->disconnect();
                }
            }
        }
        catch(PDOException $e){
            exitWithErrorPage(FATAL_ERRORS::DB_OPERATION_FAILED);
        }
        catch(Exception $e){
            $this->lastError = account::ERROR_UNKNOWN;
            return false;
        }

        return $this->logout(true);
    }
}

/**
 * `$userName`が既に存在するか確認
 * @return bool|null `true` -> 既に存在, `false` -> 存在しない, `null` -> エラー
 */
function checkNameExist(string $userName) :?bool{
    try{
        $DB = new DB();

        if($DB->connect()){
            $sql = "SELECT COUNT(`name`) FROM `account` WHERE `name`=?";
            $stmt = $DB->prepare($sql);

            if($stmt->execute([$userName])){
                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                if((int)$result["COUNT(`name`)"] === 0){
                    return false;
                }
                else{
                    return true;
                }
            }
            else{
                throw new Exception();
            }

            $DB->disconnect();
        }
        else{
            return null;
        }
    }
    catch(PDOException $e){
        exitWithErrorPage(FATAL_ERRORS::DB_OPERATION_FAILED);
    }
    catch(Exception $e){
        return null;
    }
}

?>