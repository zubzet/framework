const { defineConfig } = require("cypress");
const { compareScreenshot } = require("./cypress/support/visualRegression");

module.exports = defineConfig({
  e2e: {
    baseUrl: "http://localhost:8080",
    experimentalRunAllSpecs: true,
    video: false,
    // One automatic retry in headless runs. Every retry that rescues a test
    // is recorded in flaky-tests.log (see support/e2e.js), so flakes are
    // tracked instead of silently absorbed.
    retries: { runMode: 1, openMode: 0 },
    viewportWidth: 1280,
    viewportHeight: 720,
    setupNodeEvents(on) {
      on("task", { compareScreenshot });
    },
  },
});
