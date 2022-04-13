import ZFormField from "./ZFormField.js";

/**
 * Class for an item in the CED
 */
export default class ZCEDItem {

  /**
   * Creates an CED item. Usually these items are created in ZCED.createItem which is recommended to use when creating items.
   * @param {CEDBlueprint} blueprint Blueprint for an item
   */
  constructor(blueprint) {

    this.dom = document.createElement("div");
    this.currentRowLength = 12;

    if (blueprint.compact) {
      this.dom.classList.add("row");
      this.inputSpace = this.dom;
    } else {
      this.dom.classList.add("card", "mx-1", "mb-1", "p-1", "pb-3");
      this.inputSpace = document.createElement("div");
      this.inputSpace.classList.add("form-group");
      this.inputSpace.classList.add("mb-0");
      this.dom.appendChild(this.inputSpace);
    }

    this.fields = {};
    this.blueprint = blueprint;
    this.ced = null;

    this.dbId = -1;
    this.deleted = false;

    for (var fieldBlueprint of this.blueprint.fields) {
      var field = new ZFormField(fieldBlueprint);
      this.addField(field);
    }

    var buttonRemove = document.createElement("button");
    buttonRemove.addEventListener("click", () => { 
      this.ced.emit("change");
      this.ced.updateMargins();
      this.dom.classList.add("d-none");
      this.deleted = true;
      if (blueprint.deleteHook) {
        blueprint.deleteHook(this);
      }
    });
    buttonRemove.innerHTML = Z.Lang.CEDRemove;;
    buttonRemove.classList.add("btn", "btn-danger", "float-right");

    if (this.blueprint.smallButton) {
      this.inputSpace.appendChild(buttonRemove);
    } else {
      this.dom.appendChild(buttonRemove);
    }

    if (blueprint.compact) {
      var removeWrapper = document.createElement("div");
      removeWrapper.classList.add("col-md-1", "col");
      buttonRemove.classList.add("btn-block");
      this.dom.classList.add("form-row");
      removeWrapper.appendChild(buttonRemove);
      this.dom.appendChild(removeWrapper);
    }
  }

  /**
   * Returns a string that can be used to append to a request when using application/x-www-form-urlencoded
   * 
   * For these items additional information needs to be given so it knows where to add itself in the array
   * 
   * @param {string} name The name of the CED
   * @param {number} index Index of this item
   * @returns {string} The data containing string
   */
  getPostString(name, index) {
    var str = "";
    var modifier;

    if (this.deleted) {
      if (this.dbId == -1) return "";
      modifier = "delete";
    } else {
      if (this.dbId == -1) {
        modifier = "create";
      } else {
        modifier = "edit";
      }
    }
    str += name + "[" + index + "][Z]=" + modifier;
    if (this.dbId != -1) str += "&" + name + "[" + index + "][dbId]=" + this.dbId;

    for (var k in this.fields) {
      var field = this.fields[k];
      str += "&" + name + "[" + index + "][" + field.name + "]=<#decURI#>" + encodeURIComponent(field.value);
    }
    return str;
  }

  /**
   * Appends data to a form data object. This form data object can then be used to send the form with multipart/form-data
   * 
   * For these items additional information needs to be given so it knows where to add itself in the array
   * 
   * @param {FormData} data Already existing FormData object
   * @param {string} name The name of the CED
   * @param {number} index Index of this item
   * @returns {string} The data containing string
   */
  getFormData(data, name, index) {
    var key = name + "[" + index + "]";
    var modifier;

    if (this.deleted) {
      if (this.dbId == -1) return false;
      modifier = "delete";
    } else {
      if (this.dbId == -1) {
        modifier = "create";
      } else {
        modifier = "edit";
      }
    }
    data.set(key + "[Z]", modifier);

    if (this.dbId != -1) data.set(key + "[dbId]", this.dbId);

    for (var k in this.fields) {
      var field = this.fields[k];
      data.set(key + "[" + field.name + "]", "<#decURI#>" + encodeURIComponent(field.value));
    }

    return true;
  }

  /**
   * Marks an field in this item as invalid and show the error formatted to the user
   * @param {InvalidError} error Error object that comes from the server
   * @param {string} error.subname Name of the field
   * @returns {void}
   */
  markInvalid(error) {
    this.fields[error.subname].markInvalid(error);
  }

  /**
   * Marks all fields of this item as valid
   * @returns {void}
   */
  markValid() {
    for (var k in this.fields) {
      this.fields[k].markValid();
    }
  }

  /**
   * Value
   * @type {object}
   */
  set value(value) {
    for (var k in value) {
      if (k == "dbId") {
        this.dbId = value[k];
        continue;
      }
      this.fields[k].value = value[k];
    }
  }

  addField(field) {
    field.label.classList.add("mb-0");
    this.dom.appendChild(field.dom);
    this.fields[field.name] = field;
    field.on("change", () => {
      this.ced.emit("change");
    });

    this.fields[field.name] = field;

    if (!this.blueprint.compact) {
      if (field.width + this.currentRowLength > 12) {
        var group = document.createElement("div");
        group.classList.add("form-group");
        this.currentRow = document.createElement("div");
        this.currentRow.classList.add("form-row");
        group.appendChild(this.currentRow);
        this.inputSpace.appendChild(group);
        this.currentRowLength = 0;
      }
    } else {
      this.inputSpace.appendChild(field.dom);
    }

    if (this.currentRow) {
      this.currentRow.appendChild(field.dom);
    }
    this.currentRowLength += field.width;
  }

}
