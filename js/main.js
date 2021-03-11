/**
 * /// main.js ///
 * 
 * Main javascript of the website
 * 
 * @author  YuHima <Twitter:@YuHima_03>
 * @version 1.0.0 (2021-01-30)
 */

/// いろいろな関数 ///
/**PHPでいうところのisset */
function isset(d){
    return (d !== "" && d !== null && d !== undefined);
}

/**setAttributeを複数回一度に実行 
 * @param E Element
 * @param value {name:value, ...}
 */
function setAttrs(E, value){
    let arr = Object.entries(value);
    arr.forEach((v) => {
        E.setAttribute(arr[0], arr[1]);
    });
}

/** 一時的に使うフォーム要素を作成*/
class createTmpForm{
    constructor(action, method){
        this.tmp_form = document.createElement("form");
        this.tmp_form.action = action;
        this.tmp_form.name = "tmp";
        this.tmp_form.method = method;
        this.tmp_form.style.display = "none";
    }

    addInputElement(name, type, value = ""){
        let tmp_input = document.createElement("input");
        tmp_input.name = name;
        tmp_input.type = type;
        tmp_input.value = value;

        this.tmp_form.appendChild(tmp_input);

        return true;
    }

    getElement(){
        return this.tmp_form;
    }

    submit(parentElement){
        parentElement.appendChild(this.tmp_form);
        this.tmp_form.submit();
    }
}

/**ランダムな文字列を返す (lenの長さの文字列) */
function rand_text(len = 64){
    let char = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ123456789";
    let ret = "";
    for(i = 0; i < len; i++){
        ret += char[Math.floor(Math.random() * char.length)];
    }
    return ret;
}

/**
 * Dateオブジェクトを連想配列にする
 * @param {Object} DateObject 
 * @param {Boolean} UTC UTC時間を返す
 * @param {Boolean} noMonthZero //月を1~12にする
 * @returns {Object} 連想配列
 */
function dateToAssociativeArray(DateObject = undefined, UTC = false, noMonthZero = true){
    let now = (isset(DateObject)) ? DateObject : new Date();
    return (UTC) ? {
        year            :   now.getUTCFullYear(),
        month           :   now.getUTCMonth() + Number(noMonthZero),
        date            :   now.getUTCDate(),
        day             :   now.getUTCDay(),
        hours           :   now.getUTCHours(),
        minutes         :   now.getUTCMinutes(),
        seconds         :   now.getUTCSeconds(),
        milliseconds    :   now.getUTCMilliseconds()
    }
    : {
        year            :   now.getFullYear(),
        month           :   now.getMonth() + Number(noMonthZero),
        date            :   now.getDate(),
        day             :   now.getDay(),
        hours           :   now.getHours(),
        minutes         :   now.getMinutes(),
        seconds         :   now.getSeconds(),
        milliseconds    :   now.getMilliseconds()
    };
}

/**
 * その月の初日の曜日を取得
 * @param {Number} year 
 * @param {Number} month 
 */
function getFirstDay(year, month){
    let date = new Date(year, month-1, 1);
    return date.getDay();
}

/**
 * その月の最終日の日付を取得
 * @param {Number} year 
 * @param {Number} month 
 * @param {Boolean} day 日付じゃなくて曜日を取得
 */
function getFinalDate(year, month, day = false){
    let date = new Date(year, month, 0);
    return (day) ? date.getDay() : date.getDate();
}

/**
 * UTC時間からホストシステム側の時間に変換
 * @param {Object} DateObject Dateオブジェクト
 * @param {Number} timezoneOffset UTC時間からの差(UTC+9だったら9)
 * @returns {Object} Dateオブジェクト
 */
function UTCToClientTimezone(DateObject = undefined, timezoneOffset = undefined){
    let now = (isset(DateObject)) ? DateObject : new Date();
    let tzOffset = (isset(timezoneOffset)) ? timezoneOffset : -now.getTimezoneOffset();

    now.setUTCMinutes(now.getUTCMinutes() + tzOffset);

    return now;
}

/**
 * 値がfromとtoの間にあるかを検証
 * @param {Any} value 
 * @param {Any} from 
 * @param {Any} to 
 * @param {Boolean} [includeEqual=true]
 * @return {Boolean}
 */
function valueBetween(value, from, to, includeEqual = true){
    if(from < value && value < to){
        return true;
    }
    else if (includeEqual && (value == from || value == to)){
        return true;
    }
    return false;
}

/**
 * Element の中の全要素削除
 * @param {Element} Element Target Element (Parent)
 */
function removeAllChildElements(Element){
    while(Element.firstChild){
        Element.removeChild(Element.firstChild);
    }

    return !isset(Element.firstChild);
}

/**
 * ```targetElement```内のすべての要素を取得(どれだけ階層が下でも兎に角全部)
 * @param {Element} targetElement 
 */
function getAllChildren(targetElement){
    let result = Array();
    let childElements = targetElement.children;

    for(let i = 0; i < childElements.length; i++){
        let elem = childElements[i];
        result.push(elem);
        
        if(elem.children.length > 0){
            getAllChildren(elem).forEach(v => {
                result.push(v);
            });
        }
    }

    return result;
}

/**
 * ```targetElement```に関わる全ての親要素の取得(htmlまでいく)
 * @param {Element} targetElement 
 * @returns {Array} 添え字0の値は```HTMLElement```になってるはず
 */
function getAllParents(targetElement){
    let parentElement = targetElement.parentElement;

    if(isset(parentElement)){
        return [...getAllParents(parentElement), parentElement];
    }
    else{
        //もうこれ以上親要素がない
        return Array();
    }
}

/**
 * ```a≡k (mod b)```の```k(余り)```を返す(正の数)
 * @param {Number} a 
 * @param {Number} b 
 */
function mod(a, b){
    return (
        (a % b >= 0)
        ? (a % b)
        : (a % b + b)
    );
}

/**
 * ```str```の単位のみを取り除く
 * @param {String} str 
 * @returns {Number}
 */
function removeUnit(str){
    return Number(str.match(/[\d\.]*/)[0]);
}

/// DOMツリー読み込み後実行 ///
$(function(){
    //a要素のセキュリティ対策
    document.querySelectorAll("a[target='_blank']").forEach((E) => {
        E.setAttribute('rel', "noopener noreferrer");
    });

    //ボタン要素にdata-gotoがある場合はそこに移動するようにする
    document.querySelectorAll("input[type='button']").forEach((E) => {
        E.addEventListener("click", (E) => {
            let go_to = E.target.dataset.goto;
            if(isset(go_to)){
                go_to = go_to.split(/,/);

                if(go_to[1] == "_blank"){
                    window.open(go_to[0], "_blank", "noopener noreferrer");
                }
                else{
                    window.location.href = go_to[0];
                }
            }
        });
    });

    //inputタグにtypeとnameに応じたクラス名をそれぞれつける
    [...document.getElementsByTagName("input")].forEach(element => {
        if(isset(element.type)){
            element.classList.add(`input_${element.type}`);
        }
        if(isset(element.name)){
            element.classList.add(`name_${element.name}`);
        }
    });

    //クラス名判定
    getAllChildren(document.body).forEach(element => {
        element.classList.forEach(v => {
            if(v.match(/-weight-\d{3}/)){
                element.style.fontWeight =  String(v.match(/\d{3}$/)[0]);
            }
        });
    });
});