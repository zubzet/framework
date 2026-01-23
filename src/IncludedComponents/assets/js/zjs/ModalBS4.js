export default {

  /**
   * Shows a simple message like alert
   * @param {object} options Parameters object
   * @param {string} options.message HTML code for the body section
   * @param {string} options.title HTML code for the header section
   * @returns {Promise}
   */
  showMessage(options) {
    return new Promise(resolve => {
      let modal = new Modal({
        body: options.message,
        header: options.title,
        buttons: [
          {
            text: Z.Lang.modal_ok,
            classes: ["btn", "btn-primary"],
            onclick() { 
              modal.close(); 
            }
          }
        ],
        onclose: resolve
      });
    });
  },

  /**
   * Asks the user to confirms an action
   * @param {object} options Parameters object
   * @param {string} options.message HTML code for the body section
   * @param {string} options.title HTML code for the header section
   * @returns {Promise} Promise resolves true, if confirm. False if cancel
   */
  confirm(options) {
    let body = document.createElement("div");
    let message = document.createElement("p");
    message.innerHTML = options.message;
    body.appendChild(message);

    let input;
    if ("confirmString" in options) {
      let p = document.createElement("p");
      p.innerHTML = Z.Lang.modal_confirm_string.replace("[0]", options.confirmString);
      body.appendChild(p);

      input = document.createElement("input");
      input.type = "text";
      input.placeholder = options.confirmString;
      input.classList.add("form-control");
      body.appendChild(input);
    }

    return new Promise(resolve => {
      let modal = new Modal({
        body,
        header: options.title || Z.Lang.confirm,
        buttons: [
          {
            text: Z.Lang.modal_confirm,
            classes: ["btn", "btn-primary"],
            onclick() { 
              resolve(true); 
              modal.close(); 
            }
          },
          {
            text: Z.Lang.modal_cancel,
            classes: ["btn", "btn-primary"],
            onclick() { 
              resolve(false);
              modal.close(); 
            }
          }
        ],
        onclose() { 
          resolve(false); 
        }
      });

      if ("confirmString" in options) {
        modal.buttons[0].disabled = true;
        input.addEventListener("input", () => {
          modal.buttons[0].disabled = input.value != options.confirmString;
        });
      }
    });
  }
}

/**
 * 
 */

/**
 * Counting modal id variable. Used to assign each modal a unique id
 */
let nextModalId = 0;

class Modal {

  /**
   * Creates a new modal Object
   * @param {object} options Options object
   * @param {?function}           options.onclose Callback called when the modal closes
   * @param {?string|HTMLElement} options.header Content for the header section
   * @param {?string|HTMLElement} options.body Content for the body section
   * @param {?string|HTMLElement} options.footer Content for the footer section
   * @param {?object[]}           options.buttons Button array. Buttons are appended at the bottom of the modal
   * @param {string}              options.buttons[].text Text of the button
   * @param {function}            options.buttons[].onclick Callback for a click on that button
   * @param {string[]}            options.buttons[].classes Css classes for the button. E.g. ["btn", "btn-primary"]
   */
  constructor(options) {
    // Id is given to the dom object. Used for selectors with no references.
    this.id = "BS4-Modal-" + (nextModalId++);
    this.onclose = options.onclose || null;

    this.dom = document.createElement("div");
    this.dom.id = this.id;
    this.dom.classList.add("modal", "fade");

    let dialog = document.createElement("div");
    dialog.classList.add("modal-dialog");
    this.dom.appendChild(dialog);

    let content = document.createElement("div");
    content.classList.add("modal-content");
    dialog.appendChild(content);

    // Modal content
    this.sections = [];
    for (let section of ["header", "body", "footer"]) {
      if (options[section]) {
        let div = document.createElement("div");
        div.classList.add("modal-" + section);

        if (typeof options[section] == "string") {
          div.innerHTML = options[section];
        } else {
          div.appendChild(options[section]);
        }

        content.appendChild(div);
        this.sections[section] = div;
      }
    }

    this.buttons = [];
    // If buttons are provided, put them in a new footer
    if ("buttons" in options && options.buttons.length) {
      let footer = document.createElement("div");
      footer.classList.add("modal-footer");
      content.appendChild(footer);
      
      for (let button of options.buttons) {
        let btn = document.createElement("button");
        btn.classList.add(...button.classes);
        btn.innerHTML = button.text;
        btn.addEventListener("click", button.onclick);
        footer.appendChild(btn);
        this.buttons.push(btn);
      }
    }

    // Add the dom to the document
    document.body.appendChild(this.dom);

    $(this.dom).modal("show");

    // Register modal close listener
    $(this.dom).on('hidden.bs.modal', () => {
      if (this.onclose) this.onclose();
      $(this.dom).modal('dispose');
      this.dom.remove();
    });
  }

  /**
   * Closes the modal. The onclose callback will be called.
   */
  close() {
    $(this.dom).modal('hide');
  }

}