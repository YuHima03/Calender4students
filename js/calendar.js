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
const DAYLY = 3;

class calendar{
    async constructor(){

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
function updateMonthlyCalendar(year, month, reload = true){
    let calendar = document.createElement("table");
    calendar.id = "calendar_table";
}

window.onload = async () => {
    let calendarTableWrap = document.getElementById("calendar_table_wrap");
}