/**
 * /// calendar.js ///
 * 
 * @author YuHima
 * @copyright (C)2021 YuHima
 * @version 1.0.0
 */

const YEARLY = 0;
const MONTHLY = 1;
const WEEKLY = 2;
const DAILY = 3;

class Calendar{
    constructor(){
        this.#setAuthResult();
    }

    async #setAuthResult(){
        this.authresult = await auth();
        return;
    }

    async getData(flag){

    }
}

/**
 * 月間カレンダーの更新
 * @param {number} year 
 * @param {number} month 
 * @param {number} reload 情報を再読み込みするかどうか
 */
async function updateMonthlyCalendar(year, month, reload = true){
    let calendar = document.createElement("table");
    calendar.id = "calendar_table";
}

window.onload = async () => {
    let calendar = new Calendar();
    let calendarTableWrap = document.getElementById("calendar_table_wrap");

    
}