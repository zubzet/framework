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
    this.cardBody.classList.add("card-body");
    this.dom = document.createElement("div");
    this.dom.classList.add("card");
    this.inputSpace.remove();

    // Title bar setup
    this.titleBar = document.createElement("div");
    this.titleBar.classList.add("card-header");
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
    }

    // Footer setup
    this.footer = document.createElement("div");
    this.footer.classList.add("card-footer");
    this.dom.appendChild(this.footer);

    // Back button
    this.buttonBack = document.createElement("button");
    this.buttonBack.classList.add("btn", "btn-secondary", "mr-1", "mb-1");
    this.buttonBack.addEventListener("click", () => this.back());
    this.buttonBack.innerHTML = Z.Lang.back;
    this.footer.appendChild(this.buttonBack);
    //this.footer.appendChild(this.buttonSubmit);
    this.buttonSubmit.remove();

    // Step control
    this.stepHistory = [];
    this.currentStep = null;
    this.setStep(this.steps[0]);

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
  }

  setStepById(stepId) {
    return this.setStep(
      this.steps.find(step => step.id == stepId)
    );
  }

  /**
   * Goes to the next page
   */
  next() {
    if (this.currentStep.next) this.setStep(this.currentStep.next);
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

}