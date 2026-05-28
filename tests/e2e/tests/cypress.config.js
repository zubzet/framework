const { defineConfig } = require("cypress");
const { compareScreenshot } = require("./cypress/support/visualRegression");

module.exports = defineConfig({
  e2e: {
    baseUrl: "http://localhost:8080",
    experimentalRunAllSpecs: true,
    video: false,
    viewportWidth: 1280,
    viewportHeight: 720,
    setupNodeEvents(on) {
      on("task", { compareScreenshot });
    },
  },
});
