import { defineConfig } from "cypress";

export default defineConfig({
  e2e: {
    baseUrl: "http://localhost:5173",
    viewportWidth: 1280,
    viewportHeight: 720,
    video: true,
    screenshotOnRunFailure: true,
    defaultCommandTimeout: 12000,
    requestTimeout: 15000,
    responseTimeout: 15000,
    chromeWebSecurity: false,
    experimentalRunAllSpecs: true,
    supportFile: "cypress/support/e2e.ts",
    specPattern: "cypress/e2e/**/*.cy.{js,ts}",
    screenshotsFolder: "cypress/screenshots",
    videosFolder: "cypress/videos",
  },

  component: {
    devServer: {
      framework: "vue",
      bundler: "vite",
    },
  },
});
