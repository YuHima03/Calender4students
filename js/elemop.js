'use strict';

/**
 * @typedef {(ev: Event)} eventListenerCallback
 * @typedef {HTMLElement|EStructure|EStructureObj|Node|string|number} childrenElements
 * @typedef {Object.<string, ?string|?number>} assocArray
 * @typedef {{tagName: string, id?: string, class?: string|string[], style?: assocArray, attributes?: assocArray, dataset?: assocArray, children?: childrenElements|Array.<childrenElements>, eventListener: Object.<string, eventListenerCallback|eventListenerCallback[]>}} EStructureObj
 */

/**
 * @class 
 */
class EStructure{
    static #structure_value_parser = {
        tagName(value) {
            if (value === null || value === undefined) throw new Error("'tagName' value was not found")
            else if (typeof (value) !== "string") throw new TypeError("'tagName' value must be string");

            return value;
        },

        id(value) {
            if (value === null || value === undefined) value = "";
            else if (typeof (value) !== "string") throw new TypeError("'id' value must be string");

            return value;
        },

        class(value) {
            if (value === null || value === undefined) {
                value = [];
            }
            else {
                if (!Array.isArray(value)) value = [value];

                for (const v of value) {
                    if (typeof (v) !== "string") throw new TypeError("'class' value must be array of string");
                }
            }

            return value;
        },

        style(value) {
            return this._rpl_obj_parser(value, "style");
        },

        attributes(value) {
            return this._rpl_obj_parser(value, "attributes");
        },

        dataset(value) {
            return this._rpl_obj_parser(value, "dataset");
        },

        children(value) {
            if (value === null || value === undefined) {
                value = [];
            }
            else {
                if (!Array.isArray(value)) value = [value];

                for (const v of value) {
                    switch (typeof (v)) {
                        case "number":
                        case "string":
                            break;

                        case "object":
                            if (!Array.isArray(v)) break;

                        default:
                            throw new TypeError("'children' value must be array of HTMLElement/EStructure...etc (you also can set replacement string)");
                    }
                }
            }

            return value;
        },

        eventListener(value) {
            if (value === null || value === undefined){
                value = {}
            }
            else {
                const typeerr_msg = "'eventListener' value must be object of function/replacement_string (not array)";

                if(typeof(value) === "object" && !Array.isArray(value)){
                    for(const key in value){
                        const v = value[key];

                        if(typeof(v) !== "function"){
                            if(typeof(v) === "string"){
                                if(!this._replacement_checker(v, true))
                                    throw new Error(`Invalid replacement form -> '${v}'`);
                            }
                            else throw new TypeError(typeerr_msg);
                        }
                    }
                }
                else throw new TypeError(typeerr_msg);
            }

            return value;
        },

        /**
         * @param {string} value 
         * @param {boolean} obj_rpl
         * @return {boolean}
         */
        _replacement_checker(value, obj_rpl = false) {
            const regexp = (obj_rpl) ? /^{{{\w+}}}$/ : /^{{\w+}}$/;
            return regexp.test(value);
        },

        /**@param {string} property_name */
        _rpl_obj_parser(value, property_name) {
            if (value === null || value === undefined) {
                value = {};
            }
            else {
                switch (typeof (value)) {
                    case "object":
                        if (Array.isArray(value)) {
                            for (let i = 0; i < value.length; i++) {
                                const v = value[i];

                                if (typeof(v) === "string" && !this._replacement_checker(v, true))
                                    throw new Error(`Invalid replacement form -> '${v}'`);
                            }
                        }
                        break;

                    case "string":
                        if (!this._replacement_checker(value, true))
                            throw new Error(`Invalid replacement form -> '${value}'`);

                        value = [value];
                        break;

                    default:
                        throw new TypeError(`'${property_name}' value must be object (you also can set replacement string)`);
                }
            }

            return value;
        }
    }

    static #creation_callback = {
        id(element, value) {
            if (typeof value === "string") {
                if (value.length > 0) element.id = value;
            }
            else throw new TypeError("Value of `id` must be string");
        },

        /**@param {HTMLElement} element */
        class(element, value) {
            switch (typeof value) {
                case "string":
                    element.classList.add(value);
                    break;
                case "object":
                    if (Array.isArray(value)) {
                        for (const classname of value) {
                            EStructure.#creation_callback.class(element, classname);
                        }
                        break;
                    }
                    //配列じゃない場合はdefaultに回される
                default:
                    throw new TypeError("Value of `class` must be [array of] string");
            }
        },

        style(element, value) {
            value = EStructure.#creation_callback._obj(value, "style");

            for (const key in value) element.style[key] = value[key];
        },

        /**@param {HTMLElement} element */
        attributes(element, value) {
            value = EStructure.#creation_callback._obj(value, "dataset");

            for (const key in value) element.setAttribute(key, value[key]);
        },

        dataset(element, value) {
            value = EStructure.#creation_callback._obj(value, "dataset");

            for (const key in value) element.dataset[key] = value[key];
        },

        /**@param {HTMLElement} element */
        children(element, value, replacement) {
            switch (typeof value) {
                case "string":
                case "number":
                    //参照切れ防止のためにだいぶ面倒なことをする
                    const element_copy = element.cloneNode(true);
                    element_copy.innerHTML = value;

                    for (const child of [...element_copy.childNodes]) {
                        element.appendChild(child);
                    }
                    break;
                case "object":
                    if (Array.isArray(value)) {
                        for (const child of value) {
                            EStructure.#creation_callback.children(element, child, replacement);
                        }
                    }
                    else if (value instanceof HTMLElement || value instanceof Node) {
                        element.appendChild(value);
                    }
                    else if (value instanceof EStructure) {
                        element.appendChild(value.createElement(replacement));
                    }
                    else {
                        element.appendChild(new EStructure(value).createElement(replacement));
                    }

                    break;
                default:
                    throw new TypeError("Value of `children` must be [array of] HTMLElement/Node/EStructure/string/number");
            }
        },

        eventListener(element, value) {
            const typeerr_msg = "Valud of `eventListener` must be [array of] associative array of function";

            if (typeof value === "object") {
                for (const key in value) {
                    const v = value[key];

                    switch (typeof v) {
                        case "object":
                            EStructure.#creation_callback.eventListener(element, v);
                            break;
                        case "function":
                            if (!Array.isArray(value)) {
                                element.addEventListener(key, v);
                                break;
                            }
                        default:
                            throw new TypeError(typeerr_msg);
                    }
                }
            }
            else throw new TypeError(typeerr_msg);
        },

        _obj(value, property_name) {
            const result = {};

            switch (typeof value) {
                case "object":
                    for (const key in value) {
                        const v = value[key];

                        switch (typeof v) {
                            case "string":
                            case "number":
                                result[key] = v;
                                break;
                            case "object":
                                Object.assign(result, EStructure.#creation_callback._obj(v));
                                break;
                            default:
                                throw new TypeError(`Invalid value of \`${property_name}\``);
                        }
                    }

                    break;
            }

            return result;
        }
    }

    /**
     * オブジェクトの中に置き換えられるものがあればすべて置き換え
     * @param {EStructureObj} structure 
     * @param {Object.<string, any>} replacement 
     * @return {EStructureObj}
     */
    static #replace(structure, replacement) {
        const result = (Array.isArray(structure)) ? [] : {};

        for (const key in structure) {
            if (structure.hasOwnProperty(key)) {
                const value = structure[key];

                switch (typeof value) {
                    case "string":
                        result[key] = value;

                        //置き換え可能かチェック
                        if (/^{{{\w+}}}$/.test(value)) {
                            const replace_key = value.slice(3, -3);

                            if (replacement.hasOwnProperty(replace_key)) {
                                result[key] = replacement[replace_key];
                            }
                        }
                        else if (/{{\w+}}/.test(value)) {
                            let tmp = value;

                            for (const replace_key of value.matchAll(/{{(\w+)}}/g)) {
                                if (replacement.hasOwnProperty(replace_key[1])) {
                                    tmp = tmp.replace(replace_key[0], replacement[replace_key[1]]);
                                }
                            }

                            result[key] = tmp;
                        }

                        break;
                    case "object":
                        //DFS
                        if (value instanceof HTMLElement || value instanceof Node || value instanceof EStructure) {
                            //エラー回避(無限ループ等)
                            result[key] = value;
                        }
                        else {
                            result[key] = EStructure.#replace(value, replacement);
                        }

                        break;
                    default:
                        result[key] = value;
                        break;
                }
            }
        }

        return result;
    }

    /**@type {EStructureObj} */
    #structure;
    
    /**
     * @param {EStructureObj} structure 
     */
    constructor(structure){
        const parser = EStructure.#structure_value_parser;

        this.#structure = {};

        for(const key in parser){
            if(!key.startsWith("_")){
                const value = structure[key];
                this.#structure[key] = parser[key](value);
            }
        }
    }

    /**
     * 要素を生成
     * @param {Object.<string, any>} replacement 
     */
    createElement(replacement = {}) {
        //置き換え後のやつ
        const structure = EStructure.#replace(this.#structure, replacement);

        const element = document.createElement(structure.tagName);

        //生成するときに使う関数群
        const callbacks = EStructure.#creation_callback;
        //生成
        for (const key in structure) {
            if (!key.startsWith("_")) {
                const value = structure[key];
                if (callbacks.hasOwnProperty(key)) {
                    callbacks[key](element, value, replacement);
                }
            }
        }

        return element;
    }
}

class element_op {
    /**
     * `parentElement`の子要素を列挙
     * @param {HTMLElement|ChildNode} parentElement 
     * @param {boolean} deep 全要素を列挙するかどうか
     * @return {(HTMLElement|ChildNode)[]} 
     */
    static get_all_childNodes(parentElement, deep = false) {
        const result = [];

        for (const child of parentElement.childNodes) {
            if (deep && child.childNodes.length > 0) {
                result.push(...element_op.get_all_childNodes(child, true));
            }
            else {
                result.push(child);
            }
        }

        return result;
    }

    /**
     * `parentElement`の子要素をすべて削除
     * @param  {...HTMLElement} parentElement 
     */
    static remove_AllChildNodes(...parentElement) {
        for(const parent of parentElement){
            while (parent.childNodes.length > 0) {
                parent.childNodes[0].remove();
            }
        }
    }
}

/**
 * @param {...HTMLElement} parentElement 
 */
function remove_AllChildNodes(...parentElement){
    for(const parent of parentElement){
        while (parent.childNodes.length > 0) {
            parent.childNodes[0].remove();
        }
    }
}