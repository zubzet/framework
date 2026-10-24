const { defineConfig } = require("cypress");

module.exports = defineConfig({
  e2e: {
    baseUrl: "http://localhost:8080",
    video: false,
    viewportWidth: 1280,
    viewportHeight: 720,
  },
});
