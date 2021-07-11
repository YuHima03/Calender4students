/**
 * /// signup.js ///
 * 
 * @author YuHima_03 <Twitter:@YuHima_03>
 * @copyright (C)2020 YuHima
 * @version 1.0.0 (2021-02-07)
 */

/**
 * エラー表示
 * @param {String} className 
 * @param {String} textContent 
 * @param {HTMLElement} parentElement 
 * @param {HTMLInputElement} afterElement
 */
function err_msg(className, innerHTML, parentElement, targetInputElement, updateElement = false) {
    //既にある要素の更新
    let elem_flag = isset(parentElement.querySelector("." + className));
    if (updateElement && elem_flag) {
        parentElement.querySelector("." + className).remove();
    }

    //新規に要素追加
    if (updateElement || !elem_flag) {
        let msg_elem = document.createElement("div");
        msg_elem.classList.add(className);
        msg_elem.innerHTML = innerHTML;

        targetInputElement.classList.add("err");
        parentElement.insertBefore(msg_elem, targetInputElement.nextSibling);

        return msg_elem;
    }
    else {
        //既存の場合は更新無し
        return parentElement.querySelector("." + className);
    }
}

/// DOMツリー読み込み後 ///
window.addEventListener("load", () => {
    /**@type {HTMLFormElement} */
    const FORM = document.create_account;

    //ID
    FORM.username.addEventListener("input", (e) => {
        let username = FORM.username;
        let value = e.target.value;
        let chk_regexp = /^[A-Za-z0-9_]{4,32}$/;

        //正規表現で書式チェック
        if (chk_regexp.test(value)) {
            //適切な書式
            username.classList.remove("err");
            if (isset(FORM.querySelector(".wrong_format_msg"))) FORM.querySelector(".wrong_format_msg").remove();

            //fetchでIDチェッカーを叩く
            const data = new FormData();
            data.append("name", value);

            fetch("./account_name_checker.php", {
                method: "POST",
                cache: "no-cache",
                body: data
            })
                .then(r => {
                    r.json()
                        .then(jsonData => {
                            if (!jsonData[0]) {
                                //既に使われてる
                                username.classList.add("err");
                                err_msg("used_username_msg", "このIDは既に使用されています", FORM, FORM.username, false);
                            }
                            else {
                                //未使用
                                FORM.username.classList.remove("err");
                                if (isset(FORM.querySelector(".used_username_msg"))) FORM.querySelector(".used_username_msg").remove();
                            }
                        });
                });
        }
        else {
            //IDチェッカーとは一度goodbye
            if (isset(FORM.querySelector(".used_username_msg"))) FORM.querySelector(".used_username_msg").remove();

            //書式が不適
            username.classList.add("err");
            err_msg("wrong_format_msg", "使用できない文字が含まれています、使用できるのは英数字(A~Z,a~z,0~9)とアンダーバー\"_\"のみです", FORM, FORM.username);
        }
    });

    //パスワード
    FORM.pass.addEventListener("input", (e) => {
        let warn = {
            too_long: false,  //長すぎ
            too_short: false,  //短すぎ
            wrong_pass_format: false,  //パスワードの形式
            ABC_and_num: false,
            flag: function () { //エラーが存在するかどうか
                let flag = false;
                Object.keys(this).forEach((key) => {
                    if (key == "flag") return;
                    else if (warn[key]) flag = true;
                });
                return flag;
            }
        };
        /*
        [MEMO] 使える文字種
        英数字(A~Z,a~z,0~9)
        記号(!"#$%&'()*+,-./:;<=>?@[]^_`{|}~) -> ハッシュ化するから記号の所為でどうこうにはならない
        */

        let value = e.target.value;

        //文字数(6~32文字)
        switch (Math.min(Math.max(5, value.length), 33)) {
            case (5): //文字数が少なすぎ
                warn.too_short = true;
                break;
            case (33): //文字数が多すぎ
                warn.too_long = true;
                break;
        }

        //書式(1文字ずつ見ていく)
        let num = 0;
        let ABC = 0;
        let symbol = 0;

        [...value].forEach((char) => {
            if (/^\d$/.test(char)) num++; //数字
            else if (/^[A-Za-z]$/.test(char)) ABC++; //英文字
            else if (/^[!"#$%&'()*+,-\./:;<=>?@\[\]^_`{|}~]$/.test(char)) symbol++; //記号
            else warn.wrong_pass_format = true; //不正な書式(記号)
        });

        //英文字と数字の両方を含んでるかチェック
        if (!(/[A-Za-z]+/g.test(value) && /\d+/g.test(value))) {
            warn.ABC_and_num = true;
        }

        //エラー処理
        if (warn.flag()) {
            let msg_elem = err_msg("wrong_pass_format_msg", "", FORM, FORM.pass, true);
            //表示するリスト
            let msg_elem_list = {
                elem: document.createElement("ul"),
                add: function (innerHTML) {
                    //li要素追加
                    let li_elem = document.createElement("li");
                    li_elem.textContent = innerHTML;
                    this.elem.appendChild(li_elem);
                }
            };

            //長すぎ
            if (warn.too_long) {
                msg_elem_list.add("パスワードが長すぎます、32文字以下にしてください");
            }
            //短すぎ
            else if (warn.too_short) {
                msg_elem_list.add("パスワードが短すぎます、6文字以上にしてください");
            }
            //書式違う
            if (warn.wrong_pass_format) {
                msg_elem_list.add("利用できない文字が使用されています、使用できるのは英数字(A~Z,a~z,0~9)と記号です");
            }
            //英文字と数字の両方を含んでない
            if (warn.ABC_and_num) {
                msg_elem_list.add("英文字と数字の両方を1文字以上パスワードに含めてください");
            }

            //リストを親要素にぶち込む
            msg_elem.appendChild(msg_elem_list.elem);
        }
    });

    //パスワードの確認
    FORM.pass_chk.addEventListener("blur", () => {
        let pass_chk = FORM.pass_chk;
        let pass = FORM.pass;

        if (pass_chk.value !== pass.value) {
            //確認パスワードが不一致
            pass_chk.classList.add("err");
            err_msg("wrong_passchk_msg", "パスワードが一致しません", FORM, FORM.pass_chk, false);
        }
        else {
            //一致
            pass_chk.classList.remove("err");
            FORM.querySelector(".wrong_passchk_msg").remove();
        }
    });

    FORM.addEventListener("submit", ev => {
        ev.preventDefault();        

        const data = new FormData();
        data.append("name", FORM.username.value);
        data.append("pass", FORM.pass.value);
        data.append("form_token", FORM.form_token.value);

        //fetchで叩く
        fetch("./signup.php", {
            method: "POST",
            cache: "no-cache",
            body: data
        })
            .then(r => {
                r.json()
                    .then(jsonData => {
                        console.log(jsonData);
                    });
            });
    });
});