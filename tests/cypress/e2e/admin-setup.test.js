describe("Plugin Setup Tests", () => {
  before(() => {
    // We start fresh before running our tests.
    cy.deleteBrightcoveOptions();
  });

  beforeEach(() => {
    cy.login();
  });

  it("Brightcove Video Connect can be activated and deactivated", () => {
    cy.activatePlugin("brightcove-video-connect");
    cy.deactivatePlugin("brightcove-video-connect");
  });

  it("Display admin notice asking to configure the plugin displays when activating the plugin for the first time", () => {
    cy.activatePlugin("brightcove-video-connect");
    cy.get('.configure-brightcove').should('exist');
  });

  it( "Can successfully connect to Brightcove", () => {
    cy.visitAdminPage('?page=page-brightcove-edit-source');
    cy.get('#source-name').type('Cypress');
    cy.get('#source-account-id').type(Cypress.env('brightcoveAccountId'));
    cy.get('#source-client-id').type(Cypress.env('brightcoveClientId'));
    cy.get('#source-client-secret').type(Cypress.env('brightcoveClientSecret'));
    cy.get('#brightcove-edit-account-submit').click();
    cy.get('.successfully-configured-brightcove').should('exist');
  });
});
