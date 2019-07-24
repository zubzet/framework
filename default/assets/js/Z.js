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
        handler(JSON.parse(data));
      });
    }
  },
  Lang: {
    addElement: "+",
    submit: "Submit",
    saved: "Saved!",
    saveError: "Error while saving",
    unsaved: "Unsaved Changes"
  },
  Presets: {
    Login(nameElementId, passwordElementId, errorLabel, redirect = "") {
      var eName = document.getElementById(nameElementId);
      var ePassword = document.getElementById(passwordElementId);
      Z.Request.action('login', {name: eName.value, password: ePassword.value}, (res) => {  
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
    Register(nameElementId, passwordElementId, passwordConfirmElementId, errorLabelId, redirect = "") {
      var eName = document.getElementById(nameElementId);
      var ePassword = document.getElementById(passwordElementId);
      var ePasswordConfirm = document.getElementById(passwordConfirmElementId);
      if (ePassword.value != ePasswordConfirm.value) { alert("The password are not the same!"); return; }
      Z.Request.action('register', {email: eName.value, password: ePassword.value}, (res) => {
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

    this.dom = document.createElement("div");

    var label = document.createElement("label");
    label.innerHTML = blueprint.text;
    this.dom.appendChild(label);

    this.itemDom = document.createElement("div");
    this.itemDom.classList.add("card", "bg-secondary");
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
    var index = 0;
    for (var i = 0; i < this.items.length; i++) {
      var item = this.items[i];
      var out = item.getPostString(this.name, i);
      if (!out) continue;
      str += "&" + out;
      index++;
    }
    return str;
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
      if (this.dbId != -1) {
        this.deleted = true;
      }
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

  constructor(options = {doReload: true, dom: null}) {
    this.fields = {};
    this.options = options;
    this.ceds = [];

    this.doReload = options.doReload || false;

    this.dom = document.createElement("div");

    this.alert = document.createElement("div");
    this.alert.classList.add("alert", "d-none");
    this.lastAlertClass = "a";
    this.dom.appendChild(this.alert);

    this.inputSpace = document.createElement("div");
    this.dom.appendChild(this.inputSpace);

    this.buttonSubmit = document.createElement("button");
    this.buttonSubmit.innerHTML = Z.Lang.submit;
    this.buttonSubmit.addEventListener("click", this.send.bind(this));
    this.buttonSubmit.classList.add("btn", "btn-primary");
    this.dom.appendChild(this.buttonSubmit);

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

  send() {
    var postString = this.getPostString();

    console.log("Sending post: ", postString);

    $.ajax({
      method: "POST",
      data: postString
    }).done((data) => {
      var json;
      console.log(data);
      try {
        json = JSON.parse(data);
      } catch {
        json = {result: "error"};
      }

      if (json.result == "success") {
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
    this.inputSpace.appendChild(field.dom);
    field.on('change', () => {
      this.hint("alert-warning", Z.Lang.unsaved);
    });
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
    this.text = options.text;
    this.hint = options.hint;
    this.placeholder = options.placeholder;
    this.default = options.default;
    this.autofill = options.autofill || false;

    this.dom = document.createElement("div");

    this.label = document.createElement("label");
    this.label.innerHTML = this.text;
    this.label.setAttribute("for", "input-" + zInputIndex);
    this.dom.appendChild(this.label);

    if (this.type != "select") {
      this.input = document.createElement("input");
      this.input.setAttribute("type", this.type);
    } else {
      this.input = document.createElement("select");
      var option = document.createElement("option");
      option.setAttribute("disabled", true);
      option.setAttribute("selected", true);
      option.setAttribute("value", "");
      option.innerHTML = "---";
      this.input.appendChild(option);
    }
    this.input.classList.add("form-control");
    this.input.setAttribute("name", this.name);
    this.input.setAttribute("id", "input-" + zInputIndex);
    if (!this.autofill) {
      this.input.setAttribute("autocomplete", "new-password")
    }

    if (this.placeholder) {
      this.input.setAttribute("placeholder", this.placeholder);
    }

    if (options.value) {
      this.value = options.value;
    }

    this.dom.appendChild(this.input);

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

  on() {
    this.input.addEventListener(...arguments);
  }

  markInvalid(error) {
    this.errorLabel.innerHTML = error.type; //ToDo: make translateable
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
      option.setAttribute("value", data.value);
      this.input.appendChild(option);
    }
    if (this.options.value) {
      this.value = this.options.value;
    }
  }

  getPostString() {
    return this.name + "=<#decURI#>" + encodeURIComponent(this.value);
  }
}