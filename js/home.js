/**
 * home.js
 * 
 * @author YuHima <Twitter:@YuHima_03>
 * @copyright (C)2021 YuHima
 * @version 1.0.0 (2021-02-14)
 */

let calendarMainElemSelecter = "#calendar > div";

/**
 * 月間カレンダーの要素を新規で作る
 * @param {Number} year 
 * @param {Number} month 
 * @returns {Boolean|HTMLTableElement} 要素の作成に成功した場合は月間カレンダーのtable要素を返す(失敗するとfalse)
 * 
 * ```div#calendar > div > table.month > tr.week > td.date```
 */
function createMonthlycalendar(year, month){
    //monthが1~12の範囲外にある時に補正する
    if(!valueBetween(month, 1, 12, true)){
        year += (Math.ceil(month / 12) - 1);
        month = mod(month - 1, 12) + 1;
    }

    let calendarElem = document.querySelector(calendarMainElemSelecter);

    //月
    let monthElem = calendarElem.querySelector(".month");
    if(isset(monthElem) && Number(monthElem.dataset["year"]) === year && Number(monthElem.dataset["month"]) === month){
        //既に月が存在する場合は処理やらない
        return monthElem;
    }
    else{
        //変数いろいろ
        let now = new Date();
        let fDay = getFirstDay(year, month); //初日の曜日
        let dNum = getFinalDate(year, month); //最終日(その月の日数)

        //月の要素の新規作成
        if(!isset(monthElem)){
            monthElem = document.createElement("table");
            monthElem.classList.add("month");
        }
        monthElem.dataset["year"] = year;
        monthElem.dataset["month"] = month;

        //月の要素の中身ぶっ飛ばす(月要素は引き継ぐが中身は完全にリセットしてからの再生成)
        removeAllChildElements(monthElem);

        //週(0~5)(week+1週目)
        for(let week = 0; week < 6; week++){
            let weekElem = document.createElement("tr");
            weekElem.classList.add("week");
            weekElem.dataset["week"] = week;

            //日(0~6)(day+1曜日)(week*7+day+fDay)
            for(let day = 0; day < 7; day++){
                let dateElem = document.createElement("td");
                let dateTopElem = document.createElement("div");
                let dateShowElem = dateTopElem.cloneNode(); //日付表示
                dateElem.classList.add("date", "out_of_month");

                let date = week * 7 + day - (fDay - 1);
                //日付がその月の範囲を超えたとき
                if(date <= 0)           date += getFinalDate(year, month-1);  //先月
                else if(date > dNum)    date -= dNum;                               //来月
                else                    dateElem.classList.remove("out_of_month");  //今月(クラス名out_of_monthを消す)

                //日付セット
                dateElem.dataset["date"] = date;
                dateShowElem.textContent = date;

                dateTopElem.appendChild(dateShowElem);
                dateElem.appendChild(dateTopElem);
                weekElem.appendChild(dateElem);
            }

            monthElem.appendChild(weekElem);
        }

        //月を追加
        calendarElem.appendChild(monthElem);

        return monthElem;
    }
}

/**
 * 裏で指定された年月の予定等を取得
 * @param {*} year 
 * @param {*} month 
 * @returns {JSON} Return schedule list in JSON format
 */
function backgroundLoading(year, month){
    //IndexedDBを開く
    let IDB = indexedDB.open("_calendar", 1);
}

/**
 * XHRでカレンダーの操作のリクエストを送る
 * @param {String} type 操作の種類 ```("CREATE", "DELETE", "UPDATE")```
 * @param {Object} data 操作に必要なデータ
 * @param {String} relPATH DocRootへの相対パス
 */
function sendProcRequest(type, data, relPATH = "./"){
    let sendData = [
        
    ];

    let xhr = new XMLHttpRequest();
    xhr.open("POST", relPATH + "app/proc_req.php");
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded;charset=utf8");

    let POST_data = new FormData();
    xhr.send(POST_data);

    return xhr.status;
}

/**
 * 再読み込み含め月間カレンダーを読み込む
 * @param {Number} year 
 * @param {Number} month 
 * @param {String} focus ```auto```で読み込んだ場所に移動、```year/month```で完全指定 (初期値=```undefined```は移動なし)
 */
function loadMonthlycalendar(year, month, focus = undefined){
    let calendarElem = document.querySelector(calendarMainElemSelecter);
    //月の要素取得
    let monthElem = createMonthlycalendar(year, month);

    return monthElem;
}


/// 読み込み後or画面サイズの変化検知 ///
function settingWindow(event) {
    let windowHeight = window.innerHeight;
        
    //カレンダー本体の表示部分の高さ設定
    let calendarElem = document.getElementById("calendar");
    calendarElem.style.height = String(Number(windowHeight) - Number(calendarElem.offsetTop) - 20) + "px";
    console.log(calendarElem.offsetTop);

    return true;
}


/// DOMツリー読み込み後 ///
$(function() {
    /// カレンダー追加 ///
    let calendar_elem = document.getElementById("calendar");
    let now = new Date();
    let now_month = now.getMonth();

    //今月を読み込み
    new Promise((resolve, reject) => {
        loadMonthlycalendar(now.getFullYear(), now_month+1);
        resolve();
    }).then(() => {
        //
    });

    //画面サイズ変更に合わせて幅や高さ等を変更
    settingWindow(undefined);
    window.addEventListener("resize", settingWindow);

    //文字選択できないように
    document.addEventListener("selectionchange", () => {
        getSelection().removeAllRanges();
    });


    backgroundLoading(0, 0);
});