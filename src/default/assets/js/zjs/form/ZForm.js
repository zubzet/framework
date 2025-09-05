import ZCED from "./ZCED.js";
import ZFormField from "./ZFormField.js";

/**
 * @callback saveHook
 * @param {object} data Data that comes back from the server after submiting.
 */

 /**
 * @callback formErrorHook
 * @param {object} data Data that comes back from the server after submiting.
 */

/**
 * Class that handles all automatic form logic
 */
export default class ZForm {

  /**
   * Creates a ZForm instance
   * @param {object} options Options
   * @param {boolean} options.doReload Should the form reload after submit? This is automatically set to true when using a CED in the form
   * @param {string} options.dom Id of a dom element to append this form automatically to
   * @param {saveHook} options.saveHook Function that is called after saving. It is only called after a success and not when validation errors occour
   * @param {formErrorHook} options.formErrorHook Function that is only called on form errors
   */
  constructor(options = {
    doReload: true, 
    dom: null, 
    saveHook: null, 
    formErrorHook:null, 
    hidehints: false,
    sendOnSubmitClick: true,
    customEndpoint: null
  }) {
    this.fields = {};
    this.options = options;
    this.ceds = [];

    this.doReload = options.doReload || false;
    this.saveHook = options.saveHook;
    this.formErrorHook = options.formErrorHook;
    this.sendOnSubmitClick = "sendOnSubmitClick" in options ? options.sendOnSubmitClick : true;
    this.customEndpoint = options.customEndpoint || null;

    this.hidehints = options.hidehints;

    this.dom = document.createElement("div");

    this.alert = document.createElement("div");
    this.alert.classList.add("alert", "d-none", "sticky-top");
    this.alert.style.top = "60px";
    this.lastAlertClass = "a";
    this.dom.appendChild(this.alert);

    this.inputSpace = document.createElement("div");
    this.inputSpace.classList.add("form-group");
    this.dom.appendChild(this.inputSpace);

    this.buttonSubmit = this.createActionButton(Z.Lang.submit, "btn-primary", () => {
      if(this.sendOnSubmitClick) this.send(this.customEndpoint);
    });

    this.currentRowLength = 12;
    this.currentRow = null;
    this.rows = [];

    if (options.dom) document.getElementById(options.dom).appendChild(this.dom);
  }

  /**
   * Returns a application/x-www-form-urlencoded string to use in requests.
   * @returns {string} The string with the data
   */
  getPostString() {
    var postString = "isFormData=true";
    for (var k in this.fields) {
      var f = this.fields[k];
      postString += "&" + f.getPostString();
      f.markValid();
    }
    return postString;
  }

  /**
   * Returns a FormData object containg data for Post requests.
   * @returns {FormData} object holding the data
   */
  getFormData() {
    var data = new FormData();
    data.set("isFormData", 1);

    for (var k in this.fields) {
      var f = this.fields[k];
      f.getFormData(data);
      f.markValid();
    }
    return data;
  }

  /**
   * Adds custom html to the current part of the Form
   * @returns {void}
   */
  addCustomHTML(html) {
    var node = document.createElement("div");
    node.innerHTML = html;
    this.inputSpace.appendChild(node);
  }

  /**
   * Gathers the information automatically from the form and submits them. This function will reload the page if doReload is true and the submit was a success.
   * @returns {void}
   */
  send(customUrl = null) {
    var data = this.getFormData();

    for (var pair of data.entries()) {
      if(this.debug) console.log(pair[0]+ ', ' + pair[1]); 
    }

    var ajax_options = {
      method: "POST",
      enctype: 'multipart/form-data',
      cache: false,
      contentType: false,
      data: data,
      processData: false
    };

    if(customUrl != null) ajax_options.url = customUrl;

    $.ajax(ajax_options).done((data) => {
      var json;

      if(this.debug) console.log(data);
      
      try {
        json = JSON.parse(data);
      } catch (e) {
        json = {result: "error"};
      }

      if (json.result == "success") {
        if (this.saveHook) {
          this.saveHook(json);
        }

        if (this.doReload) window.location.reload();
        this.hint("alert-success", Z.Lang.saved);
      } else if (json.result == "formErrors") {
        for (var error of json.formErrors) {
          if(this.fields[error.name]) {
            this.fields[error.name].markInvalid(error);
          }
        }
        if (this.formErrorHook) {
          this.formErrorHook(json);
        }
      } else if (json.result == "error") {
        this.hint("alert-danger", Z.Lang.saveError);
      }

    });
  }

  /**
   * Adds an already existing field to the form
   * @param {(ZFormField|ZCED)} field Field to add
   * @returns {void}
   */
  addField(field) {
    if (field.type == "CED") this.doReload = true;

    this.fields[field.name] = field;
    field.on('change', () => {
      this.hint("alert-warning", Z.Lang.unsaved);
    });

    if (field.width + this.currentRowLength > 12) {
      var group = document.createElement("div");
      group.classList.add("form-group");
      this.currentRow = document.createElement("div");
      this.currentRow.classList.add("form-row");
      group.appendChild(this.currentRow);
      this.inputSpace.appendChild(group);
      this.currentRowLength = 0;
    }

    if (this.currentRow) {
      this.currentRow.appendChild(field.dom);
    }
    this.currentRowLength += field.width;
  }

  /**
   * Creates an CED and adds it directly to the form.
   * @param {CEDBlueprint} blueprint The blueprint that defines the attributes of the CED
   * @return {ZCED} The newly generated CED
   */
  createCED(blueprint) {
    var ced = new ZCED(blueprint);
    this.addField(ced);
    return ced;
  }

  /**
   * Creates and adds a ZFormField to the form
   * @param {FormFieldOptions} options for the new Field
   * @returns {ZFormField} The newly created field
   */
  createField(options) {
    var field = new ZFormField(options);
    this.addField(field);
    return field;
  }

  /**
   * Adds an empty space to the form just as spacer.
   * @param {FieldWidth} size Size of the field
   * @returns {void}
   */
  createEmpty(size = 12) {
    var div = document.createElement("div");
    div.classList.add("col-0", "col-md-" + size);
    this.inputSpace.appendChild(div);
  }

  /**
   * Adds an <hr> tag at the end of the generated form
   * @returns {void}
   */
  addSeperator() {
    this.inputSpace.appendChild(document.createElement("hr"));
  }

  /**
   * Sets the alert that is shown on top of the form. Used for errors, warnings or hints.
   * @param {string} alertClass Class name of the bootstrap class. For exmaple alert-success or alert-danger.
   * @param {string} content Text to show in the alert
   * @returns {void}
   */
  hint(alertClass, content) {
    if(this.hidehints) return;
    this.alert.classList.remove("d-none", this.lastAlertClass);
    this.alert.classList.add(alertClass);
    this.lastAlertClass = alertClass;
    this.alert.innerHTML = content;
  }

  /**
   * Removes the alert that holds errors, warnings or hints
   * @returns {void}
   */
  unhint() {
    this.alert.classList.add("d-none");
  }

  /**
   * Adds an button to the bottom of the form next to the submit button
   * @param {string} text Content of the button
   * @param {string} style Class for the button. Bootstrap classes like btn-primary or btn-secondary can be used here
   * @param {Function} action Function that is called when the button is clicked
   */
  createActionButton(text, style, action) {
    var button = document.createElement("button");
    button.classList.add("mr-1", "mb-1", "btn", style);
    button.innerHTML = text;
    this.dom.appendChild(button);
    button.addEventListener("click", action);
    return button;
  }

}