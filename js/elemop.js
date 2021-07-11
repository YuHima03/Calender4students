'use strict';

/**
 * 
 * @param {*} value 
 * @param {*} defaultValue 
 * @returns 
 */

/**
 * @param {boolean} allowNULL NULLを許容する
 */
function setCorrectValue(value, defaultValue, allowNULL = false) {
    return (value === undefined || (allowNULL && value === null)) ? defaultValue : value;
}

/**
 * @typedef {(ev: Event)} eventListenerCallback
 * @typedef {HTMLElement|EStructure|EStructureObj|Node|string|number} childrenElements
 * @typedef {Object.<string, ?string|?number>} assocArray
 * @typedef {{tagName: string, id?: string, class?: string|string[], style?: assocArray, attributes?: assocArray, dataset?: assocArray, children?: childrenElements|Array.<childrenElements>, eventListener: Object.<string, eventListenerCallback|eventListenerCallback[]>}} EStructureObj
 */

class EStructure {
    /**
     * @param {string} value 
     * @param {boolean} objFlag
     * @returns {string|(HTMLElement|Node)[]}
     */
    #replace(value, objFlag = false) {
        const replacements = this.replacements;

        if (replacements !== undefined && replacements !== null) {
            const matchRes = value.match(/^{{{\w+}}}$/);
            if (objFlag === true && matchRes !== null) {
                let rplRes = replacements[matchRes[0].slice(3, -3)];

                if(rplRes !== undefined && rplRes !== null){
                    if (!Array.isArray(rplRes)) rplRes = [rplRes];

                    for (let i = 0; i < rplRes.length; i++) {
                        const rplChild = rplRes[i];

                        switch (typeof (rplChild)) {
                            case"string":
                            case"number":
                                rplRes[i] = document.createTextNode(String(rplChild));
                                break;
                            case"object":
                                //HTMLElementかNodeの場合はそのままでOK
                                if (rplChild instanceof EStructure) {
                                    rplRes[i] = rplChild.createElement(replacements);
                                }
                                else if (rplChild !== null) {
                                    rplRes[i] = new EStructure(rplChild).createElement(replacements);
                                }
                                break;
                        }
                    }

                    value = rplRes;
                }
            }
            else {
                for (const varName in replacements) {
                    const regexp = new RegExp(`{{${varName}}}`, "g");
                    value = value.replace(regexp, replacements[varName]);
                }
            }
        }

        return value;
    }

    /**
     * @param {EStructureObj} structureObj 
     */
    constructor(structureObj) {
        this.tagName = structureObj.tagName;
        this.id = structureObj.id;
        this.class = structureObj.class;
        this.style = structureObj.style;
        this.attributes = structureObj.attributes;
        this.dataset = structureObj.dataset;
        this.children = structureObj.children;
        this.eventListener = structureObj.eventListener;

        if(this.id === undefined) this.id = null;

        if(this.class === undefined) this.class = [];

        if(this.style === undefined) this.style = {};

        if(this.attributes === undefined) this.attributes = {};

        if(this.dataset === undefined) this.dataset = {};

        if(this.children === undefined) this.children = [];
        else if(!Array.isArray(this.children)) this.children = [this.children];

        if(this.eventListener === undefined) this.eventListener = {};
    }

    /**
     * @param {Object.<string, string|number|childrenElements[]>} replacements 置き換え
     * @returns {HTMLElement}
     */
    createElement(replacements = {}) {
        this.replacements = replacements;

        const result = document.createElement(this.tagName);

        if (typeof (this.id) === "string") result.id = this.id;

        switch (typeof (this.class)) {
            case "string":
                result.classList.add(this.class);
                break;
            case "object":
                for (const className of this.class) {
                    result.classList.add(className);
                }
                break;
        }

        for (const styleName in this.style) {
            result.style[styleName] = this.style[styleName];
        }

        for (const attrName in this.attributes) {
            result.setAttribute(attrName, this.attributes[attrName]);
        }

        for (const key in this.dataset) {
            result.dataset[key] = this.dataset[key];
        }
        
        for (const child of this.children) {
            if (child instanceof HTMLElement) {
                child.innerHTML = this.#replace(child.innerHTML);
                result.appendChild(child);
            }
            else if (child instanceof Node) {
                child.textContent = this.#replace(child.textContent);
                result.appendChild(child);
            }
            else if (child instanceof EStructure) {
                result.appendChild(child.createElement(this.replacements));
            }
            else {
                switch (typeof (child)) {
                    case "string":
                    case "number":
                        // {{{変数名}}}の文字列 はオブジェクトに変換する
                        const replaced = this.#replace(String(child), true);

                        if (Array.isArray(replaced)) {
                            for (const replacedObj of replaced) {
                                result.appendChild(replacedObj);
                            }
                        }
                        else {
                            result.innerHTML += replaced;
                        }

                        break;
                    case "object":
                        result.appendChild(new EStructure(child).createElement(this.replacements));
                        break;
                }
            }
        }

        for(const eventName in this.eventListener){
            let callbacks = this.eventListener[eventName];
            if(!Array.isArray(callbacks)) callbacks = [callbacks];

            for(const callback of callbacks){
                result.addEventListener(eventName, callback);
            }
        }

        delete this.replacements;

        return result;
    }

    /**
     * @param  {...(HTMLElement|EStructure|EStructureObj|Node|string|number)} child 
     */
    appendChild(...child) {
        this.children.push(...child);
    }
}

class ElementOp {
    /**
     * @param {NodeListOf<Element>} nodeList 
     */
    constructor(nodeList) {
        this.nodeList = [...nodeList];

        return this;
    }
}

/**
 * @param {string} selector CSS selector string
 */
function E(selector) {
    const elements = document.querySelectorAll(selector);

    return new ElementOp(elements);
}

//After loading
window.addEventListener("load", () => {

});