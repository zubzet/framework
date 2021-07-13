import ZForm from "./ZForm.js";

/**
 * MultiStepForm class
 */
export default class MutiStepForm extends ZForm {

  /**
   * Creates a multi step form
   * @param {*} options Options object
   * @param {ZForm} options.steps[].form Form Object to use for that step
   */
  constructor(options) {
    super(options);

    // Save old dom as body
    this.cardBody = this.dom;
    this.cardBody.classList.add("card-body", "pb-0");
    this.dom = document.createElement("div");
    this.dom.classList.add("card", "shadow", "mb-2");
    this.inputSpace.remove();

    // Title bar setup
    this.titleBar = document.createElement("div");
    this.titleBar.classList.add("card-header", "text-accent", "font-weight-bold");
    this.dom.appendChild(this.titleBar);
    this.dom.appendChild(this.cardBody);

    // Steps setup
    this.steps = options.steps;    
    for (let step of this.steps) {
      this.cardBody.appendChild(step.form.dom);
      step.form.dom.style.display = "none";

      if (step.next) {
        step.next = this.steps.find(s => s.id == step.next);
      } else {
        let index = this.steps.indexOf(step);
        step.next = this.steps[index + 1];
      }

      step.form.doReload = false;
      step.form.sendOnSubmitClick = false;
      step.form.alert.remove();
      step.form.buttonSubmit.addEventListener("click", () => {
        if (step.onNext) {
          step.onNext();
        } else {
          this.next();
        }
      });
      step.form.buttonSubmit.classList.remove("mr-1", "mb-1");
      step.form.buttonSubmit.innerHTML = Z.Lang.next;
    }

    // Footer setup
    this.footer = document.createElement("div");
    this.footer.classList.add("card-footer", "d-flex");
    this.footer.style.gap = "2px";
    this.dom.appendChild(this.footer);

    // Back button
    this.buttonBack = document.createElement("button");
    this.buttonBack.classList.add("btn", "btn-secondary");
    this.buttonBack.addEventListener("click", () => this.back());
    this.buttonBack.innerHTML = Z.Lang.back;
    this.footer.appendChild(this.buttonBack);
    //this.footer.appendChild(this.buttonSubmit);
    this.buttonSubmit.remove();

    // Progress bar
    this.showProgress = "showProgress" in options ? Boolean(options.showProgress) : true;
    if (this.showProgress) {
      let progressDom = document.createElement("div");
      progressDom.classList.add("progress", "flex-grow-1");
      progressDom.style.borderTopRightRadius = "0px";
      progressDom.style.borderTopLeftRadius = "0px";
      this.dom.appendChild(progressDom);
      this.progressBar = document.createElement("div");
      this.progressBar.classList.add("progress-bar");
      this.progressBar.style.width = "0%";
      progressDom.appendChild(this.progressBar);
    }

    // Step control
    this.stepHistory = [];
    this.currentStep = null;
    this.setStep(this.steps[0]);

    // Final page
    // Imitate a form, so the same method that shows all other steps can switch to this page
    this.finalPage = {
      form: {
        dom: document.createElement("div"),
        title: Z.Lang.multi_form_final_title,
        buttonSubmit: document.createElement("div")
      },
    };
    this.finalPage.form.dom.innerHTML = Z.Lang.multi_form_final;
    this.finalPage.form.dom.style.display = "none";
    this.cardBody.appendChild(this.finalPage.form.dom);

    // Append self to document
    if (options.dom) document.getElementById(options.dom).appendChild(this.dom);
  }

  /**
   * Hides the current step and shows a new one
   * @param {object} step Step object
   */
  setStep(step) {
    this.stepHistory.push(step);

    if (this.currentStep) {
      this.currentStep.form.dom.style.display = "none";
      this.currentStep.form.buttonSubmit.remove();
    }
    
    this.currentStep = step;

    this.titleBar.innerHTML = this.currentStep.form.title;
    this.currentStep.form.dom.style.display = "";
    this.footer.appendChild(this.currentStep.form.buttonSubmit);

    let prev = this.steps.find(step => step.next == this.currentStep);
    this.buttonBack.disabled = !prev;

    if (this.showProgress) {
      let index = this.steps.indexOf(this.currentStep);
      this.progressBar.style.width = (index / this.steps.length) * 100 + "%";
    }
  }

  setStepById(stepId) {
    return this.setStep(
      this.steps.find(step => step.id == stepId)
    );
  }

  /**
   * Goes to the next page
   */
  async next() {
    if (this.currentStep.next) return this.setStep(this.currentStep.next);

    this.currentStep.form.buttonSubmit.disabled = true;
    Z.Loader.show();

    if (await this.send()) {
      this.cardBody.classList.remove("pb-0");
      this.currentStep.form.dom.style.display = "none";
      this.currentStep.form.buttonSubmit.remove();
      this.buttonBack.remove();

      this.setStep(this.finalPage);
      this.progressBar.style.width = "100%";
      Z.Loader.hide();
      this.currentStep.form.buttonSubmit.disabled = false;
    }
  }

  back() {
    this.stepHistory.pop();
    let prev = this.stepHistory.pop();
    /*let prev = this.steps.find(step => step.next == this.currentStep);*/
    this.setStep(prev);
  }

  /**
   * Gets the value from a subfield of any subform.
   * @param {string} name Name of the field to get the value from.
   */
  getFieldValue(name) {
    for (let step of this.steps) {
      let val = step.form.getFieldValue(name);
      if (val != undefined) return val;
    }
    return undefined;
  }

  /**
   * Returns a application/x-www-form-urlencoded string to use in requests.
   * @returns {string} The string with the data
   */
  getPostString() {
    let out = "isFormData=true";
    for (let step of this.steps) {
      out += step.form.getPostString().replace("isFormData=true", "");
    }
    return out;
  }

  /**
   * Returns a FormData object containg data for Post requests.
   * @returns {FormData} object holding the data
   */
  getFormData() {
    var data = new FormData();
    data.set("isFormData", 1);

    for (let step of this.steps) {
      step.form.getFormData(data);
    }

    return data;
  }

}