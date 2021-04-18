<?php

use FFI\Exception;

require_once __DIR__."/main.php";
require_once __DIR__."/db.php";

/**
 * 同じページ内で複数存在するとバグるかも？
 */
class account{
    //エラーども
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

    //////////////////////////////////////////////////
    // 動的メソッド

    /**uuid */
    private ?string $uuid = null;

    private ?string $userName = null;

    /**権限 */
    private int $permission = 0;

    /** @var array<int> */
    private ?int $lastError = null;


    public function __construct(?string $userName = null, ?string $pass = null, bool $autoLogin = false){
        $this->login($userName, $pass, $autoLogin);
    }

    public function getUUID() :?string{
        return $this->uuid;
    }

    public function getUserName() :?string{
        return $this->userName;
    }

    public function getStatus() :bool {
        return is_string($this->uuid);
    }

    /**管理者かどうか */
    public function is_admin() :bool {
        return ($this->permission === 5);
    }

    /**デベロッパかどうか */
    public function is_developer() :bool {
        return ($this->permission === 4);
    }

    /**
     * エラー情報(番号)の取得
     */
    public function getLastError() :int{
        return $this->lastError;
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
                                        //ログイン成功
                                        $token = $result["token"];
                                    }
                                    else{
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
                    }
                    else{
                        //ログイン失敗(もとから非ログイン状態)
                        return false;
                    }
                }
                else if(is_string($userName)){
                    if(strlen($pass) === 128){
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

                //resultを明示的に破棄
                unset($result);

                //DBのセッション情報更新
                $sql = "UPDATE `login_session` SET `last_login`=? WHERE `token`=?";
                $stmt = $DB->prepare($sql);

                if(!$stmt->execute([date("Y-m-d"), $token])){
                    $this->logout(true, account::ERROR_UNKNOWN);
                    return false;
                }

                //セッションID再生成
                session_regenerate_id(true);

                $DB->disconnect();

                return true;
            }
            else{
                return false;
            }
        }
        catch(PDOException $e){
            //重大なエラー
            URI::moveto(URI::FATAL_ERROR_PAGE(FATAL_ERRORS::DB_CONNECTION_FAILED));
        }
        catch(Exception $e){
            //不明なエラー
            $this->lastError = account::ERROR_UNKNOWN;
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
                    unset($_SESSION["_token"]);
                }

                //自動ログインのフラグを消す
                if(isset($_COOKIE["_session_flag"])){
                    setcookie("_session_flag", "", time()-180, "/");
                }
            }
            catch(PDOException $e){
                //重大なエラー
                URI::moveto(URI::FATAL_ERROR_PAGE(FATAL_ERRORS::DB_CONNECTION_FAILED));
            }
            catch(Exception $e){
                //不明なエラー
                $this->lastError = account::ERROR_UNKNOWN;
                return false;
            }
        }

        //セッションID再生成
        session_regenerate_id(true);

        return true;
    }

    /**
     * @param bool $login アカウント作成後に自動ログイン
     */
    public function create(bool $login = true) :bool{
        return true;
    }

    /**アカウント削除 (ログアウト) */
    public function deleteAccount() :bool{
        try{
            $DB = new DB();

            if($DB->connect()){
                $sql = "UPDATE `account` SET `delete_date`=? WHERE `uuid`=?";
                $stmt = $DB->prepare($sql);

                if($stmt->execute([date("Y-m-d"), $this->uuid])){
                    
                }

                $DB->disconnect();
            }
        }
        catch(PDOException $e){
            URI::moveto(URI::FATAL_ERROR_PAGE(FATAL_ERRORS::DB_CONNECTION_FAILED));
        }
        catch(Exception $e){
            $this->lastError = account::ERROR_UNKNOWN;
            return false;
        }

        return $this->logout(true);
    }
}

?>