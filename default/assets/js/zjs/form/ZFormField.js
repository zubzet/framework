/**
 * @type {Number} Number that holds the latest created index. Used to give ids to input fields and map labels correctly to them.
 */
let zInputIndex = 0;

/**
 * @typedef {number} FieldWidth
 * A number from 1 to 12 that defines the width of a field. 12 Is the full width. If a row gets over the lenght of 12 it will break automatically.
 * 
 * The number corresponds to the widths from the bootstrap grid system
 */

/**
 * A dataset for an item in a select of autocomplete box.
 * @typedef {object} Food
 * @property {any} value Value of the option
 * @property {string} text Text to show to the user 
 */

/**
 * Types to use in an input field. All html default ones, textarea, select and autocomplete are supported.
 * @typedef {"button"|"checkbox"|"color"|"date"|"datetime-local"|"email"|"file"|"multi-file"|"hidden"|"image"|"month"|"number"|"password"|"radio"|"range"|"reset"|"search"|"submit"|"tel"|"text"|"time"|"url"|"week"|"select"|"textarea"|"autocomplete"|"checkbox-list"} InputType
 */

/**
 * All parameters are optional
 * @typedef FormFieldOptions
 * @property {string} name Name to use in the request
 * @property {boolean} required Sets if this field is required to be filled in
 * @property {InputType} type Type of the field
 * @property {string} text Text to show in the label
 * @property {string} hint Small text to show under the input. For example: "We do not share you email" or something.
 * @property {any} default Default value 
 * @property {boolean} autofill Enable browser level autofill for this field.
 * @property {string} placeholder Placeholder to show in the input when nothing is entered
 * @property {FieldWidth} width Width of the field in units
 * @property {object} attributes List of attributes to apply to the input element. The keys are attribute names and their values will be used as the value
 * @property {Food} food Food for selects or autocomepletes
 * @property {boolean} compact Sets the compact mode. In compact mode, the label is hidden
 * @property {string} prepend Content to put in front of the input. Units are usally put there
 */

/**
 * @class ZFormField
 * Class for a field in a form
 */
export default class ZFormField {

  /**
   * Creates a new form Field. Usally this called from ZForm.createField and not directly
   * @param {FormFieldOptions} options Options for this field
   */
  constructor(options) {
    zInputIndex++;

    this.options = options;
    this.name = options.name;
    this.isRequired = options.required;
    this.type = options.type;
    this.text = options.text || "&nbsp;";
    this.hint = options.hint;
    this.placeholder = options.placeholder;
    this.default = options.default;
    this.autofill = options.autofill || false;
    
    this.optgroup = null;

    this.dom = document.createElement("div");
    this.dom.classList.add("col", "col-12");

    this.label = document.createElement("label");
    this.label.innerHTML = this.text;
    if (this.options.required) {
      this.label.innerHTML += "<span class='text-danger'>*</span>";
      this.label.classList.add("input-required");
    }
    this.label.setAttribute("for", "input-" + zInputIndex);
    this.dom.appendChild(this.label);

    var customDiv = null;

    if (this.type == "select") {           // --- Select ---
      this.input = document.createElement("select");
      var option = document.createElement("option");
      option.setAttribute("disabled", true);
      option.setAttribute("selected", true);
      option.setAttribute("value", "");
      option.innerHTML = "---";
      if (options.required) {
        option.disabled = true;
      }
      this.input.classList.add("form-control");
      this.input.appendChild(option);
    } else if (this.type == "textarea") {         // --- Textarea ---
      this.input = document.createElement("textarea");
      this.input.classList.add("form-control");
    } else if (this.type == "button") {           // --- Button ---
      this.input = document.createElement("button");
      this.input.innerHTML = options.value;
      var style = options.style || "btn-primary";
      this.input.classList.add("btn", style, "w-100");
    } else if (this.type == "hidden") {           // --- Hidden ---
      this.input = document.createElement("input");
      this.input.setAttribute("type", "hidden");
      this.dom.classList.add("d-none");
    } else if (this.type == "autocomplete") {     // --- Autocomplete ---
      this.autocompleteData = options.autocompleteData || [];
      this.autocompleteMinCharacters = options.autocompleteMinCharacters || 2;
      this.autocompleteTextCB = options.autocompleteTextCB;
      this.autocompleteCB = options.autocompleteCB || null;
      this.float = options.float || false; // Should the autocomplete popup float above the page?

      customDiv = document.createElement("div");

      this.input = document.createElement("input");
      this.input.setAttribute("type", "text");
      this.input.classList.add("form-control");
      
      var completeDiv = document.createElement("div");
      completeDiv.classList.add("list-group");
      customDiv.appendChild(this.input);
      customDiv.appendChild(completeDiv);

      if(!Array.isArray(this.autocompleteData)) {
        this.autocompleteBindingUrl = this.autocompleteData;
      }

      this.lockAutocompleteAge = 0;

      this.input.addEventListener("keyup", (e) => {
        if (e.key == "Shift") return;
        if (e.target.value.length < this.autocompleteMinCharacters) return;

        var currentAge = this.lockAutocompleteAge;

        if(this.autocompleteBindingUrl && e.target.value != "") {
          Z.Request.root(this.autocompleteBindingUrl, "autocomplete", {
            "value": e.target.value
          }, (res) => {
            if(currentAge >= this.lockAutocompleteAge) {
              this.lockAutocompleteAge++;
              this.autocompleteData = res.data;
              updateAutocompleteResults();
            }
          });
        }

        let updateAutocompleteResults = () => {
          completeDiv.innerHTML = "";
          if (e.target.value == "") return;
          if (e.key == "Escape") return;
          
          for (let value of this.autocompleteData) {
  
            let text = typeof value == "string" ? value : value.text;
            let realValue = typeof value == "string" ? value : value.value;
  
            if (text.toLowerCase().includes(e.target.value.toLowerCase())) {
              var item = document.createElement("button");
              item.type = "button";
              item.classList.add("list-group-item");
              item.classList.add("list-group-item-action");
              item.classList.add("py-1");
              
              if(text.toLowerCase() == e.target.value.toLowerCase()) {
                item.classList.add("text-primary");
              }
  
              var start = text.toLowerCase().indexOf(e.target.value.toLowerCase());
              var tmp = text.substr(0, start);
              tmp += "<strong>" + text.substr(start, e.target.value.length) + "</strong>";
              tmp += text.substring(start + e.target.value.length, value.length);
              if(this.autocompleteTextCB) {
                tmp = this.autocompleteTextCB(tmp, text, realValue);
              }
              item.innerHTML = tmp;
  
              completeDiv.appendChild(item);
              
              if (this.float) {
                completeDiv.style.position = "absolute";
                completeDiv.style.width = "calc(100% - 10px)";
              }
  
              item.addEventListener("click", e => {
                this.input.value = text;
                completeDiv.innerHTML = "";
                if(this.autocompleteCB) {
                  this.autocompleteCB(realValue, text);
                }
              });
            }
          }
        };
        updateAutocompleteResults();
      });

      document.addEventListener("click", function() {
        completeDiv.innerHTML = "";
      });

    } else if (this.type == "boolean") { // --- Boolean ---
      this.input = document.createElement("select");
      this.input.classList.add("form-control");
      this.input.innerHTML = `<option value="1">${Z.Lang.yes}</option><option value="2">${Z.Lang.no}</option>`;
    } else { // --- Default ---
      this.input = document.createElement("input");
      this.input.setAttribute("type", this.type);
      this.input.classList.add("form-control");
    }
    this.input.setAttribute("name", this.name);
    this.input.setAttribute("id", "input-" + zInputIndex);
    if (!this.autofill) {
      this.input.setAttribute("autocomplete", "new-password");
    }

    if (this.placeholder) {
      this.input.setAttribute("placeholder", this.placeholder);
    }

    if (options.value) {
      this.value = options.value;
    }

    if (options.width) {
      this.setWidth(options.width);
    } else {
      if (this.type == "hidden") {
        this.setWidth(0);
      } else {
        this.setWidth(12);
      }
    }

    if (options.attributes) {
      for (var k in options.attributes) {
        this.input.setAttribute(k, options.attributes[k]);
      }
    }

    if (customDiv) {
      this.dom.appendChild(customDiv);
    } else {
      this.dom.appendChild(this.input);
    }

    if (options.prepend) {
      var groupWrapper = document.createElement("div");
      groupWrapper.classList.add("input-group");
      var prependDiv = document.createElement("div");
      prependDiv.classList.add("input-group-prepend");
      var prependSpan = document.createElement("span");
      prependSpan.classList.add("input-group-text");
      prependSpan.innerHTML = options.prepend;
      prependDiv.appendChild(prependSpan);
      groupWrapper.appendChild(prependDiv);
      groupWrapper.appendChild(this.input);
      this.dom.appendChild(groupWrapper);
    }

    if (this.hint) {
      this.hintText = document.createElement("span");
      this.hintText.innerHTML = this.hint;
      this.hintText.classList.add("form-text", "text-muted");
      this.dom.appendChild(this.hintText);
    }

    this.errorLabel = document.createElement("span");
    this.errorLabel.classList.add("form-text", "text-danger");
    this.dom.appendChild(this.errorLabel);

    if (options.food) {
      this.feedData(options.food);
    }

    if (options.compact) {
      this.label.classList.add("d-none");
    }
  }

  /**
   * @type {any}
   */
  get value() {
    return this.input.value;
  }

  set value(value) {    
    this.input.value = value;
  }

  /**
   * Sets the width of a field. Is mostly done in the constructor. Changing the width after constructing may result in weird results.
   * @param {FieldWidth} units The width of the field
   * @returns {void}
   */
  setWidth(units) {
    this.width = units;
    this.dom.classList.add("col-md-" + units);
  }

  /**
   * Adds an event listener to the dom input element. It has the same parameters as addEventListener form DOM objects.
   * @returns {void}
   */
  on() {
    this.input.addEventListener(...arguments);
  }

  /**
   * Marks this field as errorous.
   * @param {InvalidError} error Error containg information about whats wrong
   * @returns {void}
   */
  markInvalid(error) {
    var text = Z.Lang["error_" + error.type];

    if (error.info) {
      for (var i = 0; i < error.info.length; i++) {
        text = text.replace("[" + i + "]", error.info[i]);
      }
    }

    this.errorLabel.innerHTML = text;
    this.input.setCustomValidity(error.type);
    this.input.classList.add("is-invalid");

    this.dom.scrollIntoView({behavior: "smooth", block: "center", inline: "nearest"});
  }

  /**
   * Marks this field as valid and removes all error messages from it.
   * @returns {void}
   */
  markValid() {
    this.errorLabel.innerHTML = "";
    this.input.setCustomValidity("");
    this.input.classList.remove("is-invalid");
  }

  /**
   * Validates the input and shows an appropriate in case of an error.
   * @returns {boolean} False in case of invalid
   */
  validate() {
    /**
     * @todo Implement
     */
    return true;
  }

  /**
   * Feeds the input with data. Used for selects of autocomplete inputs.
   * @param {Food[]} food Data generated with makeFood in PHP
   * @param {=boolean} clear If set, all old data will be cleared before adding the new one
   * @returns {void}
   */
  feedData(food, clear = true) {
    if (this.type != "select") {
      console.warn("Do not feed select data to non select input!");
    }

    if (clear) {
      if (!this.options.required) {
        this.input.innerHTML = '<option value="">---</option>';
      } else {
        this.input.innerHTML = "";
      }
    }

    for (var data of food) {
      if(data.type == undefined || data.type == "option") {
        var option = document.createElement("option");
        option.innerHTML = data.text;
        option.setAttribute("value", data.value);
        if(this.optgroup != null) {
          this.optgroup.appendChild(option);
        } else {
          this.input.appendChild(option);
        }
      } else if(data.type == "optgroup") {
        if(this.optgroup != null) this.input.appendChild(this.optgroup);
        this.optgroup = document.createElement("optgroup");
        this.optgroup.setAttribute("label", data.text);
        this.input.appendChild(this.optgroup);
      }
    }

    if(this.optgroup != null) this.input.appendChild(this.optgroup);
    
    if (this.options.value !== undefined) {
      this.value = this.options.value;
    }
  }

  /**
   * Returns a string that can be used to append to a request when using application/x-www-form-urlencoded
   * @returns {string} The data containing string
   */
  getPostString() {
    return this.name + "=<#decURI#>" + encodeURIComponent(this.value);
  }

  /**
   * Appends data to a form data object. This form data object can then be used to send the form with multipart/form-data
   * @param {FormData} data An existing FormData object to append to
   * @returns {void}
   */
  getFormData(data) {
    data.set(this.name, "<#decURI#>" + encodeURIComponent(this.value));
  }

  static create(options) {
    let field;

    if (options.type == "file") {
      options.type = "multi-file";
      options.limit = 1;
    }

    if (options.type == "multi-file") {
      field = new MultiFileUpload(options);
    } else if (options.type == "checkbox-list") {
      field = new CheckboxList(options);
    } else {
      field = new ZFormField(options);
    }
    return field;
  }
}

class MultiFileUpload extends ZFormField {

  constructor(options) {
    super(options);
    this.uploadPath = Z.Request.rootPath + "upload";

    this.input.type = "file";
    this.input.setAttribute("multiple", true);
    this.input.style.display = "none";

    this.fileLimit = options.limit || 10;
    this.types = options.fileTypes || ["pdf", "png", "jpg", "jpeg"];

    this.list = document.createElement("div");
    this.list.classList.add("list-group");
    this.dom.appendChild(this.list);

    this.emptyHint = document.createElement("div");
    this.emptyHint.classList.add("list-group-item");
    this.emptyHint.innerHTML = this.fileLimit == 1 ? Z.Lang.choose_file : Z.Lang.no_files_selected;
    this.list.appendChild(this.emptyHint);

    this.statusHint = document.createElement("div");
    this.statusHint.classList.add("list-group-item", "list-group-item-secondary", "p-1");
    if (this.fileLimit > 1) {
      this.list.appendChild(this.statusHint);
    }

    this.fileValues = [];

    this.list.addEventListener("click", () => {
      this.input.click();
    });

    this.input.addEventListener("change", () => {
      let files = this.input.files;
      for (let i = 0; i < files.length; i++) {
        this.addUpload(files[i]);
      }
      this.update();
      this.input.value = "";
    });

    this.update();
  }

  addUpload(file) {
    if (this.fileValues.length >= this.fileLimit) {
      this.hint(Z.Lang.too_many_files);
      return;
    }

    let uploadData = {
      progress: 0,
      serverId: null,
      file: file,
      progressBar: null,
      dom: null
    }

    let item = document.createElement("div");
    let text = document.createElement("span");
    text.innerText += file.name;

    let icon = document.createElement("span");
    icon.innerHTML = `<i class="far fa-file"></i>`;
    icon.style.marginRight = "4px";
    
    item.appendChild(icon);
    item.appendChild(text);

    text.style.width = "100%";
    text.style.overflow = "hidden";
    text.style.textOverflow = "ellipsis";
    text.style.whiteSpace = "nowrap";

    item.classList.add("list-group-item", "p-2", "d-flex", "justify-content-between", "align-items-center", "text-truncate");

    let removeButton = document.createElement("div");
    removeButton.innerHTML = Z.Lang.CEDRemove;
    removeButton.classList.add("btn", "btn-danger");
    removeButton.style.cursor = "pointer";
    item.appendChild(removeButton);

    removeButton.addEventListener("click", (e) => {
      e.stopPropagation();
      let index = this.fileValues.indexOf(uploadData);
      this.fileValues.splice(index, 1);
      item.remove();
      this.update();
    });

    let progressBar = document.createElement("div");
    Object.assign(progressBar.style, {
      position: "absolute",
      width: "0px",
      height: "4px",
      bottom: "0px",
      left: "0px",
      background: "red",
      transition: "all 200ms"
    });
    item.appendChild(progressBar);

    let formData = new FormData();
    formData.append("file", file);

    setProgress(0);
    fetch(this.uploadPath, {
      method: "POST",
      body: formData
    }).then(res => {
      return res.json();
    }).then(res => {
      uploadData.serverId = res.fileId;
      setProgress(1);
    });

    uploadData.dom = item;
    this.fileValues.push(uploadData);

    function setProgress(progress) {
      uploadData.progress = progress;
      progressBar.style.width = progress * 100 + "%";
      progressBar.style.background = progress == 1 ? "green" : "red";
    }

    return uploadData;
  }

  update() {
    if (this.fileValues.length == 0) {
      this.emptyHint.style.display = "";
    } else {
      this.emptyHint.style.display = "none";
    }
    
    for (let file of this.fileValues) {
      this.list.insertBefore(file.dom, this.emptyHint);
    }

    this.statusHint.innerText = this.fileValues.length + " / " + this.fileLimit;
  }

  /**
   * @type {any}
   */
     get value() {
      return this.fileValues.map(val => val.serverId).join(",");
    }
}

class CheckboxList extends ZFormField {

  constructor(options) {
    super(options);

    this.input.type = "hidden";
  }

  feedData(food, clear = true) {
    if (!this.list) {
      this.items = [];
      this.list = document.createElement("div");
      this.list.classList.add("list-group");
      this.dom.appendChild(this.list);
    }

    if (clear) {
      for (let item of this.items) {
        item.remove();
      }
    }

    for (let value of food) {
      let item = document.createElement("div");
      this.items.push(item);
      item.classList.add("list-group-item", "list-group-item-action");
      item.innerHTML = `<i class="far fa-square"></i> ` + value.text;
      item.dataset.value = value.value;
      item.style.cursor = "pointer";
      item.addEventListener("click", () => {
        item.classList.toggle("active");
        this.update();
      });
      this.list.appendChild(item);
    }
  }

  update() {
    let selectedItems = this.items.filter(item => item.classList.contains("active"));
    this.input.value = selectedItems.map(item => item.dataset.value).join(",");
    for (let item of this.items) {
      if (item.classList.contains("active")) {
        item.innerHTML = item.innerHTML.replace("fa-square", "fa-check-square");
      } else {
        item.innerHTML = item.innerHTML.replace("fa-check-square", "fa-square");
      }
    }
    this.input.dispatchEvent(new Event("change"));
  }

  set value(value) {
    if (typeof value == 'string') value = value.split(",");
    for (let item of this.items) {
      item.classList.toggle("active", value.includes(item.dataset.value));
    }
    this.update();
  }

  get value() {
    return this.items.filter(item => item.classList.contains("active")).map(item => item.dataset.value);
  }

}