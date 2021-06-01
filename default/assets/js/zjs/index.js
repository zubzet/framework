import Forms from "./form/Forms.js";
import Request from "./Request.js";
import Lang from "./Lang.js";
import Presets from "./presets/Presets.js";

import ZCED from "./form/ZCED.js";
import ZCEDItem from "./form/ZCEDItem.js";
import ZForm from "./form/ZForm.js";
import ZFormField from "./form/ZFormField.js";

import ModalBS4 from "./ModalBS4.js";

console.warn("Experimental! Do not use the webpack bundled Z.js in production");

window.Z = {
  debug: false,
  Request,
  Lang,
  Presets,
  Forms,
  ModalBS4
}

// Legacy support. Following classes should not be used manually.
window.ZCED = ZCED;
window.ZCEDItem = ZCEDItem;
window.ZForm = ZForm;
window.ZFormField = ZFormField;