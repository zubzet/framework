import ZForm from "./ZForm.js";

/**
 * Form creation utility
 */
export default {
  /**
   * Creates a form object and returns it.
   * @param {*} options Options object
   * @param {boolean} options.doReload Should the form reload after submit? This is automatically set to true when using a CED in the form
   * @param {string} options.dom Id of a dom element to append this form automatically to
   * @param {saveHook} options.saveHook Function that is called after saving. It is only called after a success and not when validation errors occour
   * @param {formErrorHook} options.formErrorHook Function that gets called only on formErrors
   */
  create(options) { return new ZForm(options); },
}