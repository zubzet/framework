const { defineConfig } = require("cypress");

module.exports = defineConfig({
  e2e: {
    baseUrl: "http://localhost:8080",
    experimentalRunAllSpecs: true,
    viewportWidth: 1280,
    viewportHeight: 720,
  },
});
