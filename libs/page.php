<?php

include_once __DIR__."/main.php";
include_once __DIR__."/db.php";
include_once __DIR__."/account.php";

/**
 * JavaScriptの`HTMLElement`とは仕様が違うよ (あくまで効率UPの為)
 */
class HTMLElement{
    private string $tagName = "";
    private array $style = [];
    private array $attr = [];
    private bool $endTag = true;
    /** @var \HTMLElement[] */
    private array $children = [];

    /**
     * @param \HTMLElement[] $children 
     */
    public function __construct(string $tagName, array $style = [], array $attr = [], bool $endTag = true, ?array $children = []){
        $this->tagName = $tagName;
        $this->style = $style;
        $this->attr = $attr;
        $this->endTag = $endTag;

        //終了タグなしの要素は中身はないはず
        if($endTag && (isset($children) || sizeof($children) === 0)){
            $this->children = $children;
        }
    }

    /**
     * HTMLの生成
     * @return string|false
     */
    public function getHTML(){
        //開始タグの中身
        $tag_s_content = "";

        //Style
        if(sizeof($this->style) > 0){
            $tag_s_content .= "style=\"";
            foreach($this->style as $key => $value){
                $tag_s_content .= "{$key}:{$value};";
            }
            $tag_s_content .= "\" ";
        }

        //Attributes
        if(sizeof($this->attr)){
            foreach($this->attr as $key => $value){
                $tag_s_content .= "{$key}=\"{$value}\" ";
            }
        }

        $tag_s = "<{$this->tagName} $tag_s_content>";

        //終了タグ有りの場合
        if($this->endTag){
            $textContent = "";
            $tag_e = "</{$this->tagName}>";

            //要素の中身
            foreach($this->children as $child){
                $textContent .= (strlen($textContent) > 0) ? "\n" : "";

                if(is_string($child)){
                    $textContent .= $child;
                }
                else if($child instanceof HTMLElement){
                    //再帰的に呼び出し
                    $textContent .= $child->getHTML();
                }
                else{
                    throw new ErrorException("Invalid `children` value type -> It must be string or HTMLElement!");
                    return false;
                }
            }
        }

        return ($this->endTag) ? $tag_s.$textContent.$tag_e : $tag_s;
    }
}

/**
 * 言語と言語ファイルの取得等
 */
class lang{
    public const Japanese = "ja";
    public const Japanese_Hiragana = "ja_h";
    public const English = "en";

    private static $langList = [
        self::Japanese,
        self::Japanese_Hiragana,
        self::English
    ];

    private ?\account $account = null;

    private ?string $lang = null;

    private ?array $langFile = null;

    //言語ファイルの読み込み
    private function loadLangFile() :bool{
        $filename = URI::ABSOLUTE_PATH()."/lang/".$this->getLang().".json";
        $result = file_get_contents($filename);

        if($result !== false){
            $json = json_decode($result, true);

            if(is_array($json)){
                $this->langFile = $json;
                return true;
            }
            else{
                //throw new ErrorException("json-decode failed");
                return false;
            }
        }
        else{
            return false;
        }
    }

    public function __construct(?\account &$accountObj = null, ?string $lang = null, bool $sync = true){
        //accountオブジェクト
        if(isset($accountObj)){
            $this->account = &$accountObj;
        }
        else{
            $this->account = new account();
        }

        //言語設定
        if(isset($lang)){
            //言語の設定
            $this->setLang($lang, $sync);
        }
        else{
            $this->getLang();
        }
    }

    public function setLang_GET(bool $sync = true) :bool{
        if(isset($_GET["lang"])){
            $langStr = mb_strtolower($_GET["lang"]);
            $langSync = !(isset($_GET["langsync"]) && (strtolower($_GET["langsync"]) === "false" || $_GET["langsync"] === "0"));
            
            return $this->setLang($langStr, $langSync && $sync);
        }
        else{
            return false;
        }
    }

    public function setLang(string $lang = self::Japanese, bool $sync = true) :bool{
        if(in_array($lang, lang::$langList)){
            if($sync){
                //DBに保存
                $DB = new DB();
                if($DB->connect()){
                    $sql = "UPDATE `account` SET `lang`=? WHERE `uuid`=?";
                    $stmt = $DB->prepare($sql);

                    if(!$stmt->execute([$lang, $this->account->getUUID()])){
                        //エラー発生？
                        return false;
                    }

                    $DB->disconnect();
                }

                //一応セッションは消しとく
                if(isset($_SESSION["_lang"]))   unset($_SESSION["_lang"]);
            }
            else{
                //セッションに保存
                $_SESSION["_lang"] = $lang;
            }

            $this->lang = $lang;

            return true;
        }
        else{
            //throw new ErrorException("Unknown `lang` value -> '{$lang}'");
            return false;
        }
    }

    public function getWord(string $key) :?string{
        if(isset($this->langFile)){
            $result = $this->langFile;

            while(preg_match("/^\w+/", $key, $matchstr)){
                if(is_array($result)){
                    if(($result = @isset_check($result[$matchstr[0]])) !== null){
                        $key = substr($key, strlen($matchstr[0]) + 1);
                    }
                    else{
                        //throw new ErrorException("Unknown key -> '{$matchstr[0]}'")
                        return null;
                    }
                }
                else{
                    //throw new ErrorException("The variable isn't array");
                    return null;
                }
            }
            unset($matchstr);

            return is_string($result) ? $result : null;
        }
        else{
            if($this->loadLangFile()){
                return $this->getWord($key);
            }
            else{
                return null;
            }
        }
    }

    /**
     * @return string|false `false`はなんかエラーが発生
     */
    public function getLang(){
        $result = null;

        if(isset($this->lang)){
            //まずDB
            $DB = new DB();
            if($DB->connect()){
                $sql = "SELECT `lang` FROM `account` WHERE `uuid`=?";
                $stmt = $DB->prepare($sql);

                if($stmt->execute([$this->account->getUUID()])){
                    $result = $stmt->fetch(PDO::FETCH_ASSOC)["lang"];
                }
                else{
                    //エラーが発生？
                    return false;
                }

                $DB->disconnect();
            }

            //次にセッション
            if(isset($_SESSION["_lang"])){
                if(in_array($_SESSION["_lang"], self::$langList)){
                    $result = $_SESSION["_lang"];
                }
                else{
                    //セッション破棄
                    unset($_SESSION["_lang"]);
                }
            }

            //言語が未設定の場合は設定する
            if(is_null($result)){
                if($this->setLang()){
                    $result = self::Japanese;
                }
                else{
                    //エラーが発生？
                    return false;
                }
            }

            //クラスオブジェクトに保存
            $this->lang = $result;
        }
        else{
            $result = $this->lang;
        }

        return $result;
    }

    public function getLangAttr(){
        $result = null;

        switch($this->getLang()){
            case(lang::English):
                $result = "en";
                break;

            case(lang::Japanese):
            case(lang::Japanese_Hiragana):
                $result = "ja";
                break;

            default:
                $this->setLang();
                $result = "ja";
                break;
        }

        return "lang=\"{$result}\"";
    }
}

/**
 * ページ生成関連
 */
class page{
    //タグ
    public const BR_TAG = "<br />";

    //OGP用
    public const OGP_PREFIX = "prefix=\"og: https://ogp.me/ns#\"";

    //ページ生成のモード指定
    /**headタグ(開始、終了タグも含む) */
    public const HEAD = 0;
    /**headタグ(中身のみ) */
    public const HEAD_C = 1;
    /**scriptタグ(BODY内) */
    public const JS_INBODY = 2;

    /**
     * ページ情報(デフォルト)
     */
    private static $defaultPageInfo = [
        "title" =>  "Untitled",
        //titleの後にサービス名を入れるか否か
        "appNameAfterTitle" =>  true,
        "description"   =>  "Calednder4Students(仮)は、日々の生活のさまざまを繋げるカレンダーWebアプリです。",
        //metaタグの内容の設定
        "meta"  =>  [
            //OGP
            "ogp"   =>  [],
            //検索エンジンの設定
            "robots"    =>  [
                "none", "notranslate"
            ]
        ],
        //外部から読み込み(相対パス)
        //JS
        "js"    =>  [

        ],
        //CSS
        "css"   =>  [

        ]
    ];

    //////////////////////////////////////////////////
    // ここから動的メソッド

    /**accountオブジェクトへの参照 */
    private ?\account $account = null;

    private array $pageInfo = [];

    private ?\lang $lang = null;

    /**
     * @param \account|null &$accountObj 参照渡し
     */
    public function __construct(?\account &$accountObj = null){
        if(is_null($accountObj)){
            $accountObj = new account();
        }

        //初期設定
        $this->account = $accountObj;
        $this->pageInfo = self::$defaultPageInfo;

        //langオブジェクト
        $this->lang = new lang($accountObj);
        $this->lang->setLang_GET();

        return;
    }

    /**`account`クラスオブジェクトを取得 (ポインタなので操作可能) */
    public function getAccountObj() :\account{
        return $this->account;
    }

    /**`lang`クラスオブジェクトを取得 */
    public function getLangObj() :\lang{
        return $this->lang;
    }
    
    public function setPageInfo(array $info = [], array $deep = []) :bool{
        $arrData = &$this->pageInfo;
        //deepの分だけ$arrDataを深層へもってく
        foreach($deep as $key){
            $arrData = &$arrData[$key];
        }

        foreach($info as $key => $value){
            if(@is_array($value) && @is_array($arrData[$key])){
                //再帰的に呼び出し
                $this->setPageInfo($value, [...$deep, $key]);
            }
            else{
                //普通に変更
                $arrData[$key] = $value;
            }
        }

        unset($arrData);

        return true;
    }

    public function getPageInfo() :array{
        return $this->pageInfo;
    }

    /**
     * headタグの生成
     */
    private function genHead(?bool $inclTag = true) :string{
        $pageInfo = $this->pageInfo;
        $title = htmlspecialchars($pageInfo["title"]).(($pageInfo["appNameAfterTitle"]) ? "&nbsp|&nbspC4S" : "");

        $result = <<<EOD
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{$title}</title>
        EOD;

        /** @var \HTMLElement[] */
        $innerElements = [];

        //css
        if(is_array($css = @isset_check($pageInfo["css"]))){
            foreach($css as $path){
                $innerElements[] = new HTMLElement("link", [], [
                    "rel"   =>  "stylesheet",
                    "type"  =>  "text/css",
                    "href"  =>  $path
                ]);
            }
        }
        //js
        if(is_array($js = @isset_check($pageInfo["js"]))){
            foreach($js as $path){
                if(is_string($path)){
                    $innerElements[] = new HTMLElement("script", [], [
                        "src"  =>  $path
                    ]);
                }
            }
        }

        //一括生成
        foreach($innerElements as $elem){
            $result .= $elem->getHTML();
        }

        //開始、終了タグの追加
        if($inclTag)    $result = "<head>".$result."</head>";

        return $result;
    }

    /**
     * BODYタグ内のscriptタグの生成
     */
    private function getScriptInBody() :string{
        $result = "";

        if(is_array($js = @isset_check($this->pageInfo["js"]))){
            foreach($js as $value){
                if(is_array($value) && @isset_check($value[1]) === self::JS_INBODY){
                    $elem = new HTMLElement("script", [], [
                        "src"   =>  $value[0]
                    ]);
                    $result .= $elem->getHTML();
                }
            }
        }

        return $result;
    }

    /**
     * ページ(タグ)の生成
     * @return string|false
     */
    public function genPage(int $tagType){
        $result = "";

        switch($tagType){
            case(self::HEAD):
            case(self::HEAD_C):
                //headタグ
                $result = $this->genHead($tagType === self::HEAD);
                break;

            case(self::JS_INBODY):
                //BODY内に書くjsタグ
                $result = $this->getScriptInBody();
                break;
            
            default:
                throw new ErrorException("Unknown `tagType` value -> '{$tagType}'");
                return false;
                break;
        }

        return $result;
    }
}

?>