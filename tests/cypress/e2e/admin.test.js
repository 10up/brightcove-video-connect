describe("WP Admin general Tests", () => {
  before(() => {
    cy.login();
  });

  it("Brightcove Video Connect can be activated and deactivated", () => {
    cy.activatePlugin("brightcove-video-connect");
    cy.deactivatePlugin("brightcove-video-connect");
  });
});
