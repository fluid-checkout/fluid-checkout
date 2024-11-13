const { defineConfig } = require("cypress");

module.exports = defineConfig({
    experimentalStudio: true,
    video: true,
    e2e: {
        baseUrl: "https://fluidcheckout.local/",
    },
});
