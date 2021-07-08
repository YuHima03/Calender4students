'use strict';

/**
 * @typedef {Object.<string, ?string|?number>} assocArray
 * @typedef {{tagName: string, id?: string, class?: string|string[], style?: assocArray, attributes?: assocArray, dataset?: assocArray, children?: Array.<HTMLElement|EStructure|EStructureObj|Node>}} EStructureObj
 */

class EStructure {
    /**
     * @param {EStructureObj} structureObj 
     */
    constructor(structureObj){
        const tagName = structureObj.tagName;
        const id = structureObj.id;
        let classes = structureObj.class;
        const style = structureObj.style;
        const attributes = structureObj.attributes;
        const dataset = structureObj.dataset;
        const children = structureObj.children;

        //tagName
        switch(typeof(tagName)){
            case"string":
                this.tagName = tagName
                break;
            case"undefined":
                throw new Error("`tagName` property not found");
            default:
                throw new TypeError("`tagName` must be string");
        }

        //id
        switch(typeof(id)){
            case"string":
                this.id = id;
            case"undefined":
                break;
            default:
                throw new TypeError("`id` must be string");
        }

        //class
        switch(typeof(classes)){
            case"string":
                classes = [classes];
                break;
            case"undefined":
                break;
            default:
                if(Array.isArray(classes)) break;
                else throw new TypeError("`class` must be string / array<string>");
        }
        for(const className of classes){
            if(typeof(className) !== "string"){
                throw new TypeError("Values in `class` must be string");
            }
        }
        this.class = classes;

        //style
        switch(typeof(style)){
            case"object":
                if(style !== null) this.style = style;
                break;
            case"undefined":
                break;
            default:
                throw new TypeError("`style` must be object");
        }
    }

    createElement(){

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