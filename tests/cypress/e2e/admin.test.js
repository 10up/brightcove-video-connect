describe("WP Admin general Tests", () => {
  before(() => {
    cy.login();
  });

  it("Brightcove Video Connect can be activated and deactivated", () => {
    cy.activatePlugin("brightcove-video-connect");
    cy.deactivatePlugin("brightcove-video-connect");
  });

  it("Admin notice asking to configure the plugin displays when activating the plugin for the first time", () => {
    cy.activatePlugin("brightcove-video-connect");
    cy.get('.configure-brightcove').should('exist');
  });


});
