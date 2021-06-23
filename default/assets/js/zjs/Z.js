!function(t){var e={};function i(s){if(e[s])return e[s].exports;var n=e[s]={i:s,l:!1,exports:{}};return t[s].call(n.exports,n,n.exports,i),n.l=!0,n.exports}i.m=t,i.c=e,i.d=function(t,e,s){i.o(t,e)||Object.defineProperty(t,e,{enumerable:!0,get:s})},i.r=function(t){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})},i.t=function(t,e){if(1&e&&(t=i(t)),8&e)return t;if(4&e&&"object"==typeof t&&t&&t.__esModule)return t;var s=Object.create(null);if(i.r(s),Object.defineProperty(s,"default",{enumerable:!0,value:t}),2&e&&"string"!=typeof t)for(var n in t)i.d(s,n,function(e){return t[e]}.bind(null,n));return s},i.n=function(t){var e=t&&t.__esModule?function(){return t.default}:function(){return t};return i.d(e,"a",e),e},i.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},i.p="",i(i.s=0)}([function(t,e,i){"use strict";i.r(e);let s=0;class n{constructor(t){s++,this.options=t,this.name=t.name,this.isRequired=t.required,this.type=t.type,this.text=t.text||"&nbsp;",this.hint=t.hint,this.placeholder=t.placeholder,this.default=t.default,this.autofill=t.autofill||!1,this.optgroup=null,this.dom=document.createElement("div"),this.dom.classList.add("col","col-12"),this.label=document.createElement("label"),this.label.innerHTML=this.text,this.options.required&&(this.label.innerHTML+="<span class='text-danger'>*</span>",this.label.classList.add("input-required")),this.label.setAttribute("for","input-"+s),this.dom.appendChild(this.label);var e=null;if("select"==this.type){this.input=document.createElement("select");var i=document.createElement("option");i.setAttribute("disabled",!0),i.setAttribute("selected",!0),i.setAttribute("value",""),i.innerHTML="---",t.required&&(i.disabled=!0),this.input.classList.add("form-control"),this.input.appendChild(i)}else if("textarea"==this.type)this.input=document.createElement("textarea"),this.input.classList.add("form-control");else if("button"==this.type){this.input=document.createElement("button"),this.input.innerHTML=t.value;var n=t.style||"btn-primary";this.input.classList.add("btn",n,"w-100")}else if("hidden"==this.type)this.input=document.createElement("input"),this.input.setAttribute("type","hidden"),this.dom.classList.add("d-none");else if("autocomplete"==this.type){this.autocompleteData=t.autocompleteData||[],this.autocompleteMinCharacters=t.autocompleteMinCharacters||2,this.autocompleteTextCB=t.autocompleteTextCB,this.autocompleteCB=t.autocompleteCB||null,this.float=t.float||!1,e=document.createElement("div"),this.input=document.createElement("input"),this.input.setAttribute("type","text"),this.input.classList.add("form-control");var a=document.createElement("div");a.classList.add("list-group"),e.appendChild(this.input),e.appendChild(a),Array.isArray(this.autocompleteData)||(this.autocompleteBindingUrl=this.autocompleteData),this.lockAutocompleteAge=0,this.input.addEventListener("keyup",t=>{if("Shift"==t.key)return;if(t.target.value.length<this.autocompleteMinCharacters)return;var e=this.lockAutocompleteAge;this.autocompleteBindingUrl&&""!=t.target.value&&Z.Request.root(this.autocompleteBindingUrl,"autocomplete",{value:t.target.value},t=>{e>=this.lockAutocompleteAge&&(this.lockAutocompleteAge++,this.autocompleteData=t.data,i())});let i=()=>{if(a.innerHTML="",""!=t.target.value&&"Escape"!=t.key)for(let n of this.autocompleteData){let o="string"==typeof n?n:n.text,r="string"==typeof n?n:n.value;if(o.toLowerCase().includes(t.target.value.toLowerCase())){var e=document.createElement("button");e.type="button",e.classList.add("list-group-item"),e.classList.add("list-group-item-action"),e.classList.add("py-1"),o.toLowerCase()==t.target.value.toLowerCase()&&e.classList.add("text-primary");var i=o.toLowerCase().indexOf(t.target.value.toLowerCase()),s=o.substr(0,i);s+="<strong>"+o.substr(i,t.target.value.length)+"</strong>",s+=o.substring(i+t.target.value.length,n.length),this.autocompleteTextCB&&(s=this.autocompleteTextCB(s,o,r)),e.innerHTML=s,a.appendChild(e),this.float&&(a.style.position="absolute",a.style.width="calc(100% - 10px)"),e.addEventListener("click",t=>{this.input.value=o,a.innerHTML="",this.autocompleteCB&&this.autocompleteCB(r,o)})}}};i()}),document.addEventListener("click",(function(){a.innerHTML=""}))}else"boolean"==this.type?(this.input=document.createElement("select"),this.input.classList.add("form-control"),this.input.innerHTML=`<option value="1">${Z.Lang.yes}</option><option value="2">${Z.Lang.no}</option>`):(this.input=document.createElement("input"),this.input.setAttribute("type",this.type),this.input.classList.add("form-control"));if(this.input.setAttribute("name",this.name),this.input.setAttribute("id","input-"+s),this.autofill||this.input.setAttribute("autocomplete","new-password"),this.placeholder&&this.input.setAttribute("placeholder",this.placeholder),t.value&&(this.value=t.value),t.width?this.setWidth(t.width):"hidden"==this.type?this.setWidth(0):this.setWidth(12),t.attributes)for(var o in t.attributes)this.input.setAttribute(o,t.attributes[o]);if(e?this.dom.appendChild(e):this.dom.appendChild(this.input),t.prepend){var r=document.createElement("div");r.classList.add("input-group");var d=document.createElement("div");d.classList.add("input-group-prepend");var l=document.createElement("span");l.classList.add("input-group-text"),l.innerHTML=t.prepend,d.appendChild(l),r.appendChild(d),r.appendChild(this.input),this.dom.appendChild(r)}this.hint&&(this.hintText=document.createElement("span"),this.hintText.innerHTML=this.hint,this.hintText.classList.add("form-text","text-muted"),this.dom.appendChild(this.hintText)),this.errorLabel=document.createElement("span"),this.errorLabel.classList.add("form-text","text-danger"),this.dom.appendChild(this.errorLabel),t.food&&this.feedData(t.food),t.compact&&this.label.classList.add("d-none")}get value(){return this.input.value}set value(t){this.input.value=t}setWidth(t){this.width=t,this.dom.classList.add("col-md-"+t)}on(){this.input.addEventListener(...arguments)}markInvalid(t){var e=Z.Lang["error_"+t.type];if(t.info)for(var i=0;i<t.info.length;i++)e=e.replace("["+i+"]",t.info[i]);this.errorLabel.innerHTML=e,this.input.setCustomValidity(t.type),this.input.classList.add("is-invalid"),this.dom.scrollIntoView({behavior:"smooth",block:"center",inline:"nearest"})}markValid(){this.errorLabel.innerHTML="",this.input.setCustomValidity(""),this.input.classList.remove("is-invalid")}validate(){return!0}feedData(t,e=!0){for(var i of("select"!=this.type&&console.warn("Do not feed select data to non select input!"),e&&(this.options.required?this.input.innerHTML="":this.input.innerHTML='<option value="">---</option>'),t))if(null==i.type||"option"==i.type){var s=document.createElement("option");s.innerHTML=i.text,s.setAttribute("value",i.value),null!=this.optgroup?this.optgroup.appendChild(s):this.input.appendChild(s)}else"optgroup"==i.type&&(null!=this.optgroup&&this.input.appendChild(this.optgroup),this.optgroup=document.createElement("optgroup"),this.optgroup.setAttribute("label",i.text),this.input.appendChild(this.optgroup));null!=this.optgroup&&this.input.appendChild(this.optgroup),void 0!==this.options.value&&(this.value=this.options.value)}getPostString(){return this.name+"=<#decURI#>"+encodeURIComponent(this.value)}getFormData(t){t.set(this.name,"<#decURI#>"+encodeURIComponent(this.value))}static create(t){let e;return"file"==t.type&&(t.type="multi-file",t.limit=1),e="multi-file"==t.type?new a(t):"checkbox-list"==t.type?new o(t):new n(t),e}}class a extends n{constructor(t){super(t),this.uploadPath=Z.Request.rootPath+"upload",this.input.type="file",this.input.setAttribute("multiple",!0),this.input.style.display="none",this.fileLimit=t.limit||10,this.types=t.fileTypes||["pdf","png","jpg","jpeg"],this.list=document.createElement("div"),this.list.classList.add("list-group"),this.dom.appendChild(this.list),this.emptyHint=document.createElement("div"),this.emptyHint.classList.add("list-group-item"),this.emptyHint.innerHTML=1==this.fileLimit?Z.Lang.choose_file:Z.Lang.no_files_selected,this.list.appendChild(this.emptyHint),this.statusHint=document.createElement("div"),this.statusHint.classList.add("list-group-item","list-group-item-secondary","p-1"),this.fileLimit>1&&this.list.appendChild(this.statusHint),this.fileValues=[],this.list.addEventListener("click",()=>{this.input.click()}),this.input.addEventListener("change",()=>{let t=this.input.files;for(let e=0;e<t.length;e++)this.addUpload(t[e]);this.update(),this.input.value=""}),this.update()}addUpload(t){if(this.fileValues.length>=this.fileLimit)return void this.hint(Z.Lang.too_many_files);let e={progress:0,serverId:null,file:t,progressBar:null,dom:null},i=document.createElement("div"),s=document.createElement("span");s.innerText+=t.name;let n=document.createElement("span");n.innerHTML='<i class="far fa-file"></i>',n.style.marginRight="4px",i.appendChild(n),i.appendChild(s),s.style.width="100%",s.style.overflow="hidden",s.style.textOverflow="ellipsis",s.style.whiteSpace="nowrap",i.classList.add("list-group-item","p-2","d-flex","justify-content-between","align-items-center","text-truncate");let a=document.createElement("div");a.innerHTML=Z.Lang.CEDRemove,a.classList.add("btn","btn-danger"),a.style.cursor="pointer",i.appendChild(a),a.addEventListener("click",t=>{t.stopPropagation();let s=this.fileValues.indexOf(e);this.fileValues.splice(s,1),i.remove(),this.update()});let o=document.createElement("div");Object.assign(o.style,{position:"absolute",width:"0px",height:"4px",bottom:"0px",left:"0px",background:"red",transition:"all 200ms"}),i.appendChild(o);let r=new FormData;function d(t){e.progress=t,o.style.width=100*t+"%",o.style.background=1==t?"green":"red"}return r.append("file",t),d(0),fetch(this.uploadPath,{method:"POST",body:r}).then(t=>t.json()).then(t=>{e.serverId=t.fileId,d(1)}),e.dom=i,this.fileValues.push(e),e}update(){0==this.fileValues.length?this.emptyHint.style.display="":this.emptyHint.style.display="none";for(let t of this.fileValues)this.list.insertBefore(t.dom,this.emptyHint);this.statusHint.innerText=this.fileValues.length+" / "+this.fileLimit}get value(){return this.fileValues.map(t=>t.serverId).join(",")}}class o extends n{constructor(t){super(t),this.input.type="hidden"}feedData(t,e=!0){if(this.list||(this.items=[],this.list=document.createElement("div"),this.list.classList.add("list-group"),this.dom.appendChild(this.list)),e)for(let t of this.items)t.remove();for(let e of t){let t=document.createElement("div");this.items.push(t),t.classList.add("list-group-item","list-group-item-action"),t.innerHTML='<i class="far fa-square"></i> '+e.text,t.dataset.value=e.value,t.style.cursor="pointer",t.addEventListener("click",()=>{t.classList.toggle("active"),this.update()}),this.list.appendChild(t)}}update(){let t=this.items.filter(t=>t.classList.contains("active"));this.input.value=t.map(t=>t.dataset.value).join(",");for(let t of this.items)t.classList.contains("active")?t.innerHTML=t.innerHTML.replace("fa-square","fa-check-square"):t.innerHTML=t.innerHTML.replace("fa-check-square","fa-square");this.input.dispatchEvent(new Event("change"))}set value(t){"string"==typeof t&&(t=t.split(","));for(let e of this.items)e.classList.toggle("active",t.includes(e.dataset.value));this.update()}get value(){return this.items.filter(t=>t.classList.contains("active")).map(t=>t.dataset.value)}}class r{constructor(t){for(var e of(this.dom=document.createElement("div"),this.currentRowLength=12,t.compact?(this.dom.classList.add("row"),this.inputSpace=this.dom):(this.dom.classList.add("card","mx-1","mb-1","p-1","pb-3"),this.inputSpace=document.createElement("div"),this.inputSpace.classList.add("form-group"),this.inputSpace.classList.add("mb-0"),this.dom.appendChild(this.inputSpace)),this.fields={},this.blueprint=t,this.ced=null,this.dbId=-1,this.deleted=!1,this.blueprint.fields)){var i=new n(e);this.addField(i)}var s=document.createElement("button");if(s.addEventListener("click",()=>{this.ced.emit("change"),this.ced.updateMargins(),this.dom.classList.add("d-none"),this.deleted=!0,t.deleteHook&&t.deleteHook(this)}),s.innerHTML=Z.Lang.CEDRemove,s.classList.add("btn","btn-danger","float-right"),this.blueprint.smallButton?this.inputSpace.appendChild(s):this.dom.appendChild(s),t.compact){var a=document.createElement("div");a.classList.add("col-md-1","col"),s.classList.add("btn-block"),this.dom.classList.add("form-row"),a.appendChild(s),this.dom.appendChild(a)}}getPostString(t,e){var i,s="";if(this.deleted){if(-1==this.dbId)return"";i="delete"}else i=-1==this.dbId?"create":"edit";for(var n in s+=t+"["+e+"][Z]="+i,-1!=this.dbId&&(s+="&"+t+"["+e+"][dbId]="+this.dbId),this.fields){var a=this.fields[n];s+="&"+t+"["+e+"]["+a.name+"]=<#decURI#>"+encodeURIComponent(a.value)}return s}getFormData(t,e,i){var s,n=e+"["+i+"]";if(this.deleted){if(-1==this.dbId)return!1;s="delete"}else s=-1==this.dbId?"create":"edit";for(var a in t.set(n+"[Z]",s),-1!=this.dbId&&t.set(n+"[dbId]",this.dbId),this.fields){var o=this.fields[a];t.set(n+"["+o.name+"]","<#decURI#>"+encodeURIComponent(o.value))}return!0}markInvalid(t){this.fields[t.subname].markInvalid(t)}markValid(){for(var t in this.fields)this.fields[t].markValid()}set value(t){for(var e in t)"dbId"!=e?this.fields[e].value=t[e]:this.dbId=t[e]}addField(t){if(t.label.classList.add("mb-0"),this.dom.appendChild(t.dom),this.fields[t.name]=t,t.on("change",()=>{this.ced.emit("change")}),this.fields[t.name]=t,this.blueprint.compact)this.inputSpace.appendChild(t.dom);else if(t.width+this.currentRowLength>12){var e=document.createElement("div");e.classList.add("form-group"),this.currentRow=document.createElement("div"),this.currentRow.classList.add("form-row"),e.appendChild(this.currentRow),this.inputSpace.appendChild(e),this.currentRowLength=0}this.currentRow&&this.currentRow.appendChild(t.dom),this.currentRowLength+=t.width}}class d{constructor(t={}){this.blueprint=t,this.type="CED",this.name=t.name,this.items=[],this.deleted=[],this.zform=null,this.blueprint=t,this.width=12,this.dom=document.createElement("div"),this.dom.classList.add("col","col-12");var e=document.createElement("label");if(e.innerHTML=t.text,this.dom.appendChild(e),this.itemDom=document.createElement("div"),this.itemDom.classList.add("bg-secondary","pt-1","pb-1"),this.dom.appendChild(this.itemDom),this.listeners={},this.buttonAdd=document.createElement("button"),this.buttonAdd.innerHTML=Z.Lang.addElement,this.buttonAdd.addEventListener("click",this.createItem.bind(this)),this.buttonAdd.classList.add("btn","btn-primary","my-1","mr-1"),this.dom.appendChild(this.buttonAdd),t.value)for(var i of t.value){var s=new r(t);s.value=i,this.addItem(s)}t.compact&&this.itemDom.classList.add("container")}getPostString(){for(var t="",e=0;e<this.items.length;e++){var i=this.items[e].getPostString(this.name,e);i&&(t+="&"+i)}return t}getFormData(t){for(var e=0,i=0;i<this.items.length;i++){this.items[i].getFormData(t,this.name,e)&&e++}}createItem(){var t=new r(this.blueprint);return this.addItem(t),this.emit("change"),t}addItem(t){this.items.push(t),t.ced=this,this.itemDom.appendChild(t.dom),this.updateMargins()}on(t,e){t in this.listeners||(this.listeners[t]=[]),this.listeners[t].push(e)}emit(t){if(t in this.listeners)for(var e of this.listeners[t])e()}markInvalid(t){this.items[t.index].markInvalid(t)}markValid(){for(var t of this.items)t.markValid()}updateMargins(){for(var t=0;t<this.items.length;t++)this.items[t].dom.classList.add("mb-1");this.items.length>0&&this.items[this.items.length-1].dom.classList.remove("mb-1")}}class l{constructor(t={doReload:!0,dom:null,saveHook:null,formErrorHook:null,hidehints:!1,sendOnSubmitClick:!0,customEndpoint:null}){this.fields={},this.options=t,this.ceds=[],this.doReload=t.doReload||!1,this.saveHook=t.saveHook,this.formErrorHook=t.formErrorHook,this.sendOnSubmitClick=!("sendOnSubmitClick"in t)||t.sendOnSubmitClick,this.customEndpoint=t.customEndpoint||null,this.hidehints=t.hidehints,this.dom=document.createElement("div"),this.alert=document.createElement("div"),this.alert.classList.add("alert","d-none","sticky-top"),this.alert.style.top="60px",this.lastAlertClass="a",this.dom.appendChild(this.alert),this.inputSpace=document.createElement("div"),this.inputSpace.classList.add("form-group"),this.dom.appendChild(this.inputSpace),this.buttonSubmit=this.createActionButton(Z.Lang.submit,"btn-primary",()=>{this.sendOnSubmitClick&&this.send(this.customEndpoint)}),this.currentRowLength=12,this.currentRow=null,this.rows=[],this.title=t.title||"no title",t.dom&&document.getElementById(t.dom).appendChild(this.dom)}getPostString(){var t="isFormData=true";for(var e in this.fields){var i=this.fields[e];t+="&"+i.getPostString(),i.markValid()}return t}getFormData(){var t=new FormData;for(var e in t.set("isFormData",1),this.fields){var i=this.fields[e];i.getFormData(t),i.markValid()}return t}addCustomHTML(t){var e=document.createElement("div");e.innerHTML=t,this.inputSpace.appendChild(e)}send(t=null){var e=this.getFormData();for(var i of e.entries())this.debug&&console.log(i[0]+", "+i[1]);var s={method:"POST",enctype:"multipart/form-data",cache:!1,contentType:!1,data:e,processData:!1};null!=t&&(s.url=t),$.ajax(s).done(t=>{var e;this.debug&&console.log(t);try{e=JSON.parse(t)}catch(t){e={result:"error"}}if("success"==e.result)this.saveHook&&this.saveHook(e),this.doReload&&window.location.reload(),this.hint("alert-success",Z.Lang.saved);else if("formErrors"==e.result){for(var i of e.formErrors)this.fields[i.name]&&this.fields[i.name].markInvalid(i);this.formErrorHook&&this.formErrorHook(e)}else"error"==e.result&&this.hint("alert-danger",Z.Lang.saveError)})}addField(t){if("CED"==t.type&&(this.doReload=!0),this.fields[t.name]=t,t.on("change",()=>{this.hint("alert-warning",Z.Lang.unsaved)}),t.width+this.currentRowLength>12){var e=document.createElement("div");e.classList.add("form-group"),this.currentRow=document.createElement("div"),this.currentRow.classList.add("form-row"),e.appendChild(this.currentRow),this.inputSpace.appendChild(e),this.currentRowLength=0}this.currentRow&&this.currentRow.appendChild(t.dom),this.currentRowLength+=t.width}createCED(t){var e=new d(t);return this.addField(e),e}createField(t){var e=n.create(t);return this.addField(e),e}createEmpty(t=12){var e=document.createElement("div");e.classList.add("col-0","col-md-"+t),this.inputSpace.appendChild(e)}addSeperator(){this.inputSpace.appendChild(document.createElement("hr"))}hint(t,e){this.hidehints||(this.alert.classList.remove("d-none",this.lastAlertClass),this.alert.classList.add(t),this.lastAlertClass=t,this.alert.innerHTML=e)}unhint(){this.alert.classList.add("d-none")}createActionButton(t,e,i){var s=document.createElement("button");return s.classList.add("mr-1","mb-1","btn",e),s.innerHTML=t,this.dom.appendChild(s),s.addEventListener("click",i),s}getFieldValue(t){if(!(t in this.fields))return;return this.fields[t].value}}class h extends l{constructor(t){super(t),this.cardBody=this.dom,this.cardBody.classList.add("card-body"),this.dom=document.createElement("div"),this.dom.classList.add("card"),this.inputSpace.remove(),this.titleBar=document.createElement("div"),this.titleBar.classList.add("card-header"),this.dom.appendChild(this.titleBar),this.dom.appendChild(this.cardBody),this.steps=t.steps;for(let t of this.steps){if(this.cardBody.appendChild(t.form.dom),t.form.dom.style.display="none",t.next)t.next=this.steps.find(e=>e.id==t.next);else{let e=this.steps.indexOf(t);t.next=this.steps[e+1]}t.form.doReload=!1,t.form.sendOnSubmitClick=!1,t.form.alert.remove(),t.form.buttonSubmit.addEventListener("click",()=>{t.onNext?t.onNext():this.next()})}this.footer=document.createElement("div"),this.footer.classList.add("card-footer"),this.dom.appendChild(this.footer),this.buttonBack=document.createElement("button"),this.buttonBack.classList.add("btn","btn-secondary","mr-1","mb-1"),this.buttonBack.addEventListener("click",()=>this.back()),this.buttonBack.innerHTML=Z.Lang.back,this.footer.appendChild(this.buttonBack),this.buttonSubmit.remove(),this.stepHistory=[],this.currentStep=null,this.setStep(this.steps[0]),t.dom&&document.getElementById(t.dom).appendChild(this.dom)}setStep(t){this.stepHistory.push(t),this.currentStep&&(this.currentStep.form.dom.style.display="none",this.currentStep.form.buttonSubmit.remove()),this.currentStep=t,this.titleBar.innerHTML=this.currentStep.form.title,this.currentStep.form.dom.style.display="",this.footer.appendChild(this.currentStep.form.buttonSubmit);let e=this.steps.find(t=>t.next==this.currentStep);this.buttonBack.disabled=!e}setStepById(t){return this.setStep(this.steps.find(e=>e.id==t))}next(){this.currentStep.next&&this.setStep(this.currentStep.next)}back(){this.stepHistory.pop();let t=this.stepHistory.pop();this.setStep(t)}getFieldValue(t){for(let e of this.steps){let i=e.form.getFieldValue(t);if(null!=i)return i}}getPostString(){let t="isFormData=true";for(let e of this.steps)t+=e.form.getPostString().replace("isFormData=true","");return t}}var c={create:t=>new l(t),createMultiStepForm:t=>new h(t)},u={action(t,e,i){$.ajax({method:"POST",data:Object.assign(e,{action:t})}).done(t=>{var e=null;try{e=JSON.parse(t)}catch(e){console.error("Please show this to a developer: ",t)}null!=e&&i(e)})},root(t,e,i,s=null,n=!0,a=!0,o={}){$.ajax({method:"POST",data:Object.assign(i,{action:e}),url:Z.Request.rootPath+t,async:n,...o}).done(t=>{if(a){var e=null;try{e=JSON.parse(t)}catch(e){console.error("Please show this to a developer: ",t)}null!=e&&s&&(t=e)}s(t)})},rootPath:""};var p={Login:function(t,e,i,s=""){var n=document.getElementById(t),a=document.getElementById(e),o=document.getElementById(i),r=document.getElementById("loading");r&&(r.style.display=""),o.style.display="none",Z.Request.root("login","login",{name:n.value,password:a.value},t=>{if(r&&(r.style.display="none"),"success"==t.result)""==s?window.location.reload():window.location.href=s;else{o.style.display="";var e=t.message;"Username or password is wrong"==e&&(e=Z.Lang.error_login),"Too many login tries. Try again later."==e&&(e=Z.Lang.error_too_many_login_tries),o.innerHTML==e?$(o).fadeOut(20).fadeIn(100).fadeOut(20).fadeIn(100).show():(o.innerHTML=e,$(o).slideDown(300))}})},Signup:function(t,e,i,s,n="",a=!1){var o=document.getElementById(t),r=document.getElementById(e),d=document.getElementById(i),l=document.getElementById(s);if(r.value!=d.value)return a?void alert(Z.Lang.error_password_mismatch):void(l.innerHTML==Z.Lang.error_password_mismatch?$("#"+s).fadeOut(20).fadeIn(100).fadeOut(20).fadeIn(100).show():(l.innerHTML=Z.Lang.error_password_mismatch,$("#"+s).slideDown(300)));var h=document.getElementById("loading");h&&(h.style.display=""),Z.Request.root("login/signup","signup",{email:o.value,password:r.value},t=>{if(h&&(h.style.display="none"),"error"==t.result){let e=t.message;"This email is not allowed!"==t.message&&(e=Z.Lang.error_invalid_email),l.innerHTML=e,a&&alert(e)}else"success"==t.result&&(""==n?window.location.reload():window.location.href=n)})},ForgotPassword:function(t,e,i=""){var s=document.getElementById(t),n=document.getElementById("loading");n&&(n.style.display=""),Z.Request.root("login/forgot_password","forgot_password",{unameemail:s.value},t=>{n&&(n.style.display="none"),"success"==t.result?""==i?window.location.reload():window.location.href=i:document.getElementById(e).innerHTML==Z.Lang.error_password_reset?$("#"+e).fadeOut(20).fadeIn(100).fadeOut(20).fadeIn(100).show():(document.getElementById(e).innerHTML=Z.Lang.error_password_reset,$("#"+e).slideDown(300))})}},m={showMessage:t=>new Promise(e=>{let i=new g({body:t.message,header:t.title,buttons:[{text:Z.Lang.modal_ok,classes:["btn","btn-primary"],onclick(){i.close()}}],onclose:e})}),confirm(t){let e,i=document.createElement("div"),s=document.createElement("p");if(s.innerHTML=t.message,i.appendChild(s),"confirmString"in t){let s=document.createElement("p");s.innerHTML=Z.Lang.modal_confirm_string.replace("[0]",t.confirmString),i.appendChild(s),e=document.createElement("input"),e.type="text",e.placeholder=t.confirmString,e.classList.add("form-control"),i.appendChild(e)}return new Promise(s=>{let n=new g({body:i,header:t.title||Z.Lang.confirm,buttons:[{text:Z.Lang.modal_confirm,classes:["btn","btn-primary"],onclick(){s(!0),n.close()}},{text:Z.Lang.modal_cancel,classes:["btn","btn-primary"],onclick(){s(!1),n.close()}}],onclose(){s(!1)}});"confirmString"in t&&(n.buttons[0].disabled=!0,e.addEventListener("input",()=>{n.buttons[0].disabled=e.value!=t.confirmString}))})}};let f=0;class g{constructor(t){this.id="BS4-Modal-"+f++,this.onclose=t.onclose||null,this.dom=document.createElement("div"),this.dom.id=this.id,this.dom.classList.add("modal","fade");let e=document.createElement("div");e.classList.add("modal-dialog"),this.dom.appendChild(e);let i=document.createElement("div");i.classList.add("modal-content"),e.appendChild(i),this.sections=[];for(let e of["header","body","footer"])if(t[e]){let s=document.createElement("div");s.classList.add("modal-"+e),"string"==typeof t[e]?s.innerHTML=t[e]:s.appendChild(t[e]),i.appendChild(s),this.sections[e]=s}if(this.buttons=[],"buttons"in t&&t.buttons.length){let e=document.createElement("div");e.classList.add("modal-footer"),i.appendChild(e);for(let i of t.buttons){let t=document.createElement("button");t.classList.add(...i.classes),t.innerHTML=i.text,t.addEventListener("click",i.onclick),e.appendChild(t),this.buttons.push(t)}}document.body.appendChild(this.dom),$(this.dom).modal("show"),$(this.dom).on("hidden.bs.modal",()=>{this.onclose&&this.onclose(),$(this.dom).modal("dispose"),this.dom.remove()})}close(){$(this.dom).modal("hide")}}window.Z={debug:!1,Request:u,Lang:{addElement:'<i class="fas fa-plus"></i>',submit:"Submit",saved:"Saved!",saveError:"Error while saving",back:"Back",unsaved:"There are unsaved changes",error_filter:"Your input does not have the correct pattern!",error_length:"Your input is too long or too short. It should have between [0] and [1] characters.",error_required:"Please fill in this field",error_range:"The number is too large to too small. It must be between [0] and [1].",error_unique:"This already exists!",error_exist:"This does not exist!",error_integer:"This is not an integer!",error_date:"Please give a correct date!",error_regex:"The input does not meet the required pattern!",error_contact_admin:"This input field does not like you. Contact an admin that convinces it that you are a good person!",error_password_reset:"An error occurred. Did you use the correct email address?",error_password_mismatch:"The password are not the same!",error_invalid_email:"This email is not allowed!",error_too_many_login_tries:"Too many login tries. Try again later.",error_login:"Username or password is wrong",choose_file:"Choose file",CEDRemove:"✕",modal_ok:"OK",modal_confirm:"Confirm",modal_cancel:"Cancel",modal_confirm_string:"Type <code>[0]</code> into the input to confirm",no_files_selected:"There are no files selected yet. Click here to add files",yes:"Yes",no:"No",too_many_files:"The file upload limit was reached"},Presets:p,Forms:c,ModalBS4:m},window.ZCED=d,window.ZCEDItem=r,window.ZForm=l,window.ZFormField=n}]);