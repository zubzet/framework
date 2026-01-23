import ZCEDItem from "./ZCEDItem.js";

/**
* InvalidError is an error that holds data about field failed to validate and mark them. This object usally comes as literal from the backend.
* @typedef {object} InvalidError
* @property {string} type The type of the error. This specifies what message to show
* @property {string[]} info Array of strings that will be inserted in the placeholders ([0], [1]...)
*/

/**
* Blueprint used in the constructor of CED fields and CED items.
* @typedef {object} CEDBlueprint
* @property {string} name Name of the field for post data
* @property {string} text Text for the label
* @property {array} value Default value. Can be generated in php with createCEDFood
* @property {Array} fields Array of options for creating fields. These fields will be inserted in all CED items
* @property {boolean} compact Uses the compact mode if set to true
*/

/**
* Class that handles CED items
*/
export default class ZCED { //Create, edit, delete

  /**
  * Creates a ZCED instance
  * @param {CEDBlueprint} blueprint Options object for the CED
  * @param {string} blueprint.name Name of the field for post data
  * @param {string} blueprint.text Text for the label
  * @param {array} blueprint.value Default value. Can be generated in php with createCEDFood
  * @param {Array} blueprint.fields Array of options for creating fields. These fields will be inserted in all CED items
  * @param {boolean} blueprint.compact Uses the compact mode if set to true
  * @param {boolean} blueprint.smallButton Makes the remove button small
  */
  constructor(blueprint = {}) {
    this.blueprint = blueprint;
    this.type = "CED";

    this.name = blueprint.name;
    this.items = [];
    this.deleted = [];
    this.zform = null;
    this.blueprint = blueprint;

    this.width = 12;

    this.dom = document.createElement("div");
    this.dom.classList.add("col", "col-12");

    var label = document.createElement("label");
    label.innerHTML = blueprint.text;
    this.dom.appendChild(label);

    this.itemDom = document.createElement("div");
    this.itemDom.classList.add("bg-secondary", "pt-1", "pb-1");
    this.dom.appendChild(this.itemDom);

    this.listeners = {};

    this.buttonAdd = document.createElement("button");
    this.buttonAdd.innerHTML = Z.Lang.addElement;
    this.buttonAdd.addEventListener("click", this.createItem.bind(this));
    this.buttonAdd.classList.add("btn", "btn-primary", "my-1", "mr-1");
    this.dom.appendChild(this.buttonAdd);

    if (blueprint.value) {
      for (var value of blueprint.value) {
        var item = new ZCEDItem(blueprint);
        item.value = value;
        this.addItem(item);
      }
    }

    if (blueprint.compact) {
      this.itemDom.classList.add("container");
    }
  }

  /**
  * Returns a string that can be used to append to a request when using application/x-www-form-urlencoded
  * @returns {string} The data containing string
  */
  getPostString() {
    var str = "";
    for (var i = 0; i < this.items.length; i++) {
      var item = this.items[i];
      var out = item.getPostString(this.name, i);
      if (!out) continue;
      str += "&" + out;
    }
    return str;
  }

  /**
  * Appends data to a form data object. This form data object can then be used to send the form with multipart/form-data
  * @param {FormData} data An existing FormData object to append to
  * @returns {void}
  */
  getFormData(data) {
    var index = 0;
    for (var i = 0; i < this.items.length; i++) {
      var item = this.items[i];
      if (item.getFormData(data, this.name, index)) {
        index++;
      }
    }

  }

  /**
  * Creates and adds an item with default values to this CED.
  * @returns {void}
  */
  createItem() {
    var item = new ZCEDItem(this.blueprint);
    this.addItem(item);
    this.emit("change");
    return item;
  }

  /**
  * Adds an already existing item to this CED
  * @param {CEDItem} item 
  * @returns {void}
  */
  addItem(item) {
    this.items.push(item);
    item.ced = this;
    this.itemDom.appendChild(item.dom);
    this.updateMargins();
  }

  /**
  * Adds an event listener to this object.
  * @param {String} type Type of the event
  * @param {Function} handler Function that is called when the event occours
  * @returns {void}
  */
  on(type, handler) {
    if (!(type in this.listeners)) this.listeners[type] = [];
    this.listeners[type].push(handler);
  }

  /**
  * Emits an event. The implementation is very simple and does not support any event args.
  * 
  * This should only be called privatly.
  * @param {String} type Type of the event
  * @returns {void}
  */
  emit(type) {
    if (type in this.listeners) {
      for (var handler of this.listeners[type]) {
        handler();
      }
    }
  }

  /**
  * Marks a field in the ced item as invalid
  * @param {InvalidError} error The error object. Comes usally from back from the server.
  * @param {number} error.index Index of the item to show the error at
  * @param {string} error.subname Name of the field in the item
  * @returns {void}
  */
  markInvalid(error) {
    this.items[error.index].markInvalid(error);
  }

  /**
  * Marks all fields in the items of the ced as valid
  * @returns {void}
  */
  markValid() {
    for (var item of this.items) {
      item.markValid();
    }
  }

  updateMargins() {
    for (var i = 0; i < this.items.length; i++) {
      this.items[i].dom.classList.add("mb-1");
    }

    if (this.items.length > 0) {
      this.items[this.items.length - 1].dom.classList.remove("mb-1");
    }
  }
}
