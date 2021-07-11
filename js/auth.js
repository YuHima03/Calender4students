/**
 * /// auth.js ///
 * 
 * @author YuHima
 * @copyright (C)2020 YuHima
 * @version 1.0.0
 */

const authPageURL = "http://localhost/calendar4Students/auth/";

async function auth() {
    let result = null;

    //送信データ
    let data = new FormData();
    data.set("timestamp", Math.floor(Date.now() / 1000));

    //認証
    await fetch(authPageURL, {
        method: "POST",
        cache: "no-cache",
        body: data
    })
        .then(async (response) => {
            result = await response.json();
        })
        .catch(async (reject) => {
            result = reject;
        });

    return result;
}