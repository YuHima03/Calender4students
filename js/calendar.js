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

window.addEventListener("load", async () => {
    let calendar = new Calendar();
    let calendarTableWrap = document.getElementById("calendar_table_wrap");

    const ws = new WebSocket("ws://localhost:8080");

    ws.addEventListener("open", () => {
        console.log("WebSocket was opened");
    });

    ws.addEventListener("message", msg => {
        console.log(`Message from ws => ${msg.data}`)
    });

    ws.addEventListener("close", () => {
        console.log("WebSocket was closed");
    });
});