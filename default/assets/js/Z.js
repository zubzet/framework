Z = {
  Forms: {
    create(options) { return new ZForm(options); },
  },
  Request: {
    action(action, data, handler) {
      $.ajax({
        method: "POST",
        data: Object.assign(data, {action: action})
      }).done((data) => {
        var dat = null;
        try {
          dat = JSON.parse(data);
        } catch (e) {
          console.error("Please show this to a developer: ", data);
        }
        if (dat != null) {
          handler(dat);
        }
      });
    },
    root(action, subaction, data, handler = null) {
      $.ajax({
        method: "POST",
        data: Object.assign(data, {action: subaction}),
        url: Z.Request.rootPath + action
      }).done((data) => {
        var dat = null;
        try {
          dat = JSON.parse(data);
        } catch (e) {
          console.error("Please show this to a developer: ", data);
        }
        if (dat != null && handler) {
          handler(dat);
        }
      });
    },
    rootPath: ""
  },
  Lang: {
    addElement: "+",
    submit: "Submit",
    saved: "Saved!",
    saveError: "Error while saving",
    unsaved: "Unsaved Changes",
    error_filter: "Your input does not have the correct pattern!",
    error_length: "Your input it too long or too short. It should have between [0] and [1] characters.",
    error_required: "Please fill in this field",
    error_range: "The number is too large to too small. It must be between [0] and [1].",
    error_unique: "This already exists!",
    error_exist: "This does not exist!",
    error_integer: "This is not an integer",
    error_date: "Please give a correct date!",
    error_contact_admin: "This input field does not like you. Contact an admin that convinces it that you are a good person!",
    choose_file: "Choose file"
  },
  Presets: {
    Login(nameElementId, passwordElementId, errorLabel, redirect = "") {
      var eName = document.getElementById(nameElementId);
      var ePassword = document.getElementById(passwordElementId);
      Z.Request.root('login', 'login', {name: eName.value, password: ePassword.value}, (res) => {  
        if (res.result == "success") {
          if (redirect == "") {
            window.location.reload();
          } else {
            window.location.href = redirect;
          }
        } else {
          document.getElementById(errorLabel).innerHTML = res.message;
        }
      });
    },
    Signup(nameElementId, passwordElementId, passwordConfirmElementId, errorLabelId, redirect = "") {
      var eName = document.getElementById(nameElementId);
      var ePassword = document.getElementById(passwordElementId);
      var ePasswordConfirm = document.getElementById(passwordConfirmElementId);
      if (ePassword.value != ePasswordConfirm.value) { alert("The password are not the same!"); return; }
      Z.Request.root('login/signup', 'signup', {email: eName.value, password: ePassword.value}, (res) => {
        if (res.result == "error") {
          document.getElementById(errorLabelId).innerHTML = res.message;
        } else if (res.result == "success") {
          if (redirect == "") {
            window.location.reload();
          } else {
            window.location.href = redirect;
          }
        }
      });
    }
  }
}

class ZCED { //Create, edit, delete

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
    this.buttonAdd.classList.add("btn", "btn-primary", "m-1");
    this.dom.appendChild(this.buttonAdd);

    if (blueprint.value) {
      for (var value of blueprint.value) {
        var item = new ZCEDItem(blueprint);
        item.value = value;
        this.addItem(item);
      }
    }
  }

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

  getFormData(data) {
    var index = 0;
    for (var i = 0; i < this.items.length; i++) {
      var item = this.items[i];
      if (item.getFormData(data, this.name, index)) {
        index++;
      }
    }

  }

  createItem() {
    var item = new ZCEDItem(this.blueprint);
    this.addItem(item);
    this.emit("change");
  }

  addItem(item) {
    this.items.push(item);
    item.ced = this;
    this.itemDom.appendChild(item.dom);
  }

  on(type, handler) {
    if (!(type in this.listeners)) this.listeners[type] = [];
    this.listeners[type].push(handler);
  }

  emit(type) {
    if (type in this.listeners) {
      for (var handler of this.listeners[type]) {
        handler();
      }
    }
  }

  markInvalid(error) {
    this.items[error.index].markInvalid(error);
  }

  markValid() {
    for (var item of this.items) {
      item.markValid();
    }
  }
}

class ZCEDItem {

  constructor(blueprint) {

    this.dom = document.createElement("div");
    this.dom.classList.add("card", "m-1", "p-1");
    this.fields = {};
    this.blueprint = blueprint;
    this.ced = null;

    this.dbId = -1;
    this.deleted = false;

    for (var fieldBlueprint of this.blueprint.fields) {
      var field = new ZFormField(fieldBlueprint);
      this.dom.appendChild(field.dom);
      field.on("change", () => {
        this.ced.emit("change");
      });
      this.fields[field.name] = field;
    }

    var buttonRemove = document.createElement("button");
    buttonRemove.addEventListener("click", () => { 
      this.ced.emit("change");
      this.dom.classList.add("d-none");
      this.deleted = true;
    });
    buttonRemove.innerHTML = "✕";
    buttonRemove.classList.add("btn", "btn-danger");
    this.dom.appendChild(buttonRemove);
  }

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

  markInvalid(error) {
    this.fields[error.subname].markInvalid(error);
  }

  markValid() {
    for (var k in this.fields) {
      this.fields[k].markValid();
    }
  }

  set value(value) {
    for (var k in value) {
      if (k == "dbId") {
        this.dbId = value[k];
        continue;
      }
      this.fields[k].value = value[k];
    }
  }

}

class ZForm {

  constructor(options = {doReload: true, dom: null, saveHook: null}) {
    this.fields = {};
    this.options = options;
    this.ceds = [];

    this.doReload = options.doReload || false;
    this.saveHook = options.saveHook;

    this.dom = document.createElement("div");

    this.alert = document.createElement("div");
    this.alert.classList.add("alert", "d-none");
    this.lastAlertClass = "a";
    this.dom.appendChild(this.alert);

    this.inputSpace = document.createElement("div");
    this.inputSpace.classList.add("form-group");
    this.dom.appendChild(this.inputSpace);

    this.buttonSubmit = document.createElement("button");
    this.buttonSubmit.innerHTML = Z.Lang.submit;
    var that = this;
    this.buttonSubmit.addEventListener("click", function(e) {
      that.send();
    });
    this.buttonSubmit.classList.add("btn", "btn-primary");
    this.dom.appendChild(this.buttonSubmit);

    this.currentRowLength = 12;
    this.currentRow = null;
    this.rows = [];

    if (options.dom) document.getElementById(options.dom).appendChild(this.dom);
  }

  getPostString() {
    var postString = "isFormData=true";
    for (var k in this.fields) {
      var f = this.fields[k];
      postString += "&" + f.getPostString();
      f.markValid();
    }
    return postString;
  }

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

  send() {
    var data = this.getFormData();

    for (var pair of data.entries()) {
      console.log(pair[0]+ ', ' + pair[1]); 
    }

    $.ajax({
      method: "POST",
      enctype: 'multipart/form-data',
      cache: false,
      contentType: false,
      data: data,
      processData: false
    }).done((data) => {
      var json;
      console.log(data);
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
          this.fields[error.name].markInvalid(error);
        }
      } else if (json.result == "error") {
        this.hint("alert-danger", Z.Lang.saveError);
      }

    });
  }

  addField(field) {


    if (field.type == "CED") this.doReload = true;

    this.fields[field.name] = field;
    field.on('change', () => {
      this.hint("alert-warning", Z.Lang.unsaved);
    });
    bsCustomFileInput.init();

    if (field.width + this.currentRowLength > 12) {
      var group = document.createElement("div");
      group.classList.add("form-group");
      this.currentRow = document.createElement("div");
      this.currentRow.classList.add("form-row");
      group.appendChild(this.currentRow);
      this.inputSpace.appendChild(group);
      this.currentRowLength = 0;
    }

    this.currentRow.appendChild(field.dom);
    this.currentRowLength += field.width;
  }

  createCED(blueprint) {
    var ced = new ZCED(blueprint);
    this.addField(ced);
    return ced;
  }

  createField(options) {
    var field = new ZFormField(options);
    this.addField(field);
    return field;
  }

  createEmpty(size = 12) {
    var div = document.createElement("div");
    div.classList.add("col-0", "col-md-" + size);
    this.inputSpace.appendChild(div);
  }

  addSeperator() {
    this.inputSpace.appendChild(document.createElement("hr"));
  }

  hint(alertClass, content) {
    this.alert.classList.remove("d-none", this.lastAlertClass);
    this.alert.classList.add(alertClass);
    this.lastAlertClass = alertClass;
    this.alert.innerHTML = content;
  }

  unhint() {
    this.alert.classList.add("d-none");
  }

  createActionButton(text, style, action) {
    var button = document.createElement("button");
    button.classList.add("ml-1", "btn", style);
    button.innerHTML = text;
    this.dom.appendChild(button);
    button.addEventListener("click", action);
  }

}

var zInputIndex = 0;
class ZFormField {
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

    this.dom = document.createElement("div");
    this.dom.classList.add("col");

    this.label = document.createElement("label");
    this.label.innerHTML = this.text;
    if (this.options.required) {
      this.label.innerHTML += "<span class='text-danger'>*</span>";
      this.label.classList.add("input-required");
    }
    this.label.setAttribute("for", "input-" + zInputIndex);
    this.dom.appendChild(this.label);

    var customDiv = null;
    if (this.type == "file") {
      customDiv = document.createElement("div");
      customDiv.classList.add("custom-file");
      this.input = document.createElement("input");
      this.input.setAttribute("type", this.type);
      this.input.classList.add("custom-file-input");
      var l = document.createElement("label");
      l.innerHTML = options.customFileInputText || Z.Lang.choose_file;
      l.classList.add("custom-file-label", "text-truncate");
      customDiv.appendChild(l);
      customDiv.appendChild(this.input);
      this.input.classList.add("form-control");
    } else if (this.type == "select") {
      this.input = document.createElement("select");
      var option = document.createElement("option");
      option.setAttribute("disabled", true);
      option.setAttribute("selected", true);
      option.setAttribute("value", "");
      option.innerHTML = "---";
      this.input.classList.add("form-control");
      this.input.appendChild(option);     
    } else if (this.type == "textarea") {
      this.input = document.createElement("textarea");
      this.input.classList.add("form-control");
    } else if (this.type == "button") { 
      this.input = document.createElement("button");
      this.input.innerHTML = options.value;
      var style = options.style || "btn-primary";
      this.input.classList.add("btn", style, "w-100");
    } else {
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
      this.setWidth(12);
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

  }

  getData() {
    return {
      name: this.name
    }
  }

  get value() {
    return this.input.value;
  }

  set value(value) {
    this.input.value = value;
  }

  //Width in 1/12 units
  setWidth(units) {
    this.width = units;
    this.dom.classList.add("col-md-" + units);
  }

  on() {
    this.input.addEventListener(...arguments);
  }

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
  }

  markValid() {
    this.errorLabel.innerHTML = "";
    this.input.setCustomValidity("");
    this.input.classList.remove("is-invalid");
  }

  feedData(optData) {
    if (this.type != "select") {
      console.warn("Do not feed select data to non select input!");
    }
    for (var data of optData) {
      var option = document.createElement("option");
      option.innerHTML = data.text;
      if (this.options.value) {
        option.setAttribute("value", data.value);
        this.value = this.options.value;
      }
      this.input.appendChild(option);
    }
  }

  getPostString() {
    return this.name + "=<#decURI#>" + encodeURIComponent(this.value);
  }

  getFormData(data) {
    if (this.type == "file") {
      if (this.input.files[0]) {
        data.append(this.name, this.input.files[0], this.value);
      }
    } else {
      data.set(this.name, "<#decURI#>" + encodeURIComponent(this.value));
    }
  }
}