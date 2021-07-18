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

    async getData(){
        const f_data = {
            type: "a"
        };

        /**@typse {{}} */
        const opResData = await fetch("../req/data_op.php", {
            method: "POST",
            cache: "no-cache",
            body: JSON.stringify(f_data)
        })
            .then(async opResult => {
                return await opResult.json()
            });

        console.log(opResData);
    }
}

window.addEventListener("load", async () => {
    let calendar = new Calendar();
    let calendarTableWrap = document.getElementById("calendar_table_wrap");

    console.log(await calendar.getData());
});