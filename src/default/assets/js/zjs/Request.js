export default {
  /**
   * Triggers a subaction on the current action from which this view was launched
   * @param {string} action Name of the subaction. Can be checked in the backend with $req->isAction("blub")
   * @param {object} data Data to send to the client. It will be passed as post data
   * @param {function} handler Handler that gets called when the request was successfull
   */
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
  /**
   * Triggers a subaction on any action of any controller. Simply put the path in as action. For example: "login/logout". URL parameters can be attached here too.
   * @param {string} action Name of the subaction. Can be checked in the backend with $req->isAction("blub")
   * @param {object} data Data to send to the client. It will be passed as post data
   * @param {function} handler Handler that gets called when the request was successful
   */
  root(action, subaction, data, handler = null, async = true, parse = true, additionalParameters = {}) {
    $.ajax({
      method: "POST",
      data: Object.assign(data, {action: subaction}),
      url: Z.Request.rootPath + action,
      async: async,
      ...additionalParameters
    }).done((data) => {
      if(parse) {
        var dat = null;
        try {
          dat = JSON.parse(data);
        } catch (e) {
          console.error("Please show this to a developer: ", data);
        }
        if (dat != null && handler) {
          data = dat;
        }
      }
      handler(data);
    });
  },
  /**
   * Path to the root of the page. This is set in the layout essentials. Do not change it!
   */
  rootPath: ""
};