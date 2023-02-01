// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
//
//
// -- This is a parent command --
// Cypress.Commands.add('login', (email, password) => { ... })
//
//
// -- This is a child command --
// Cypress.Commands.add('drag', { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add('dismiss', { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This will overwrite an existing command --
// Cypress.Commands.overwrite('visit', (originalFn, url, options) => { ... })

Cypress.Commands.add('visitAdminPage', (page = 'index.php') => {
    cy.login();
    if (page.includes('http')) {
        cy.visit(page);
    } else {
        cy.visit(`/wp-admin/${page.replace(/^\/|\/$/g, '')}`);
    }
});

Cypress.Commands.add('deleteBrightcoveOptions', () => {
	cy.wpCliEval(`delete_option( '_brightcove_plugin_activated' );`);
	cy.wpCliEval(`delete_option( '_brightcove_default_account' );`);
	cy.wpCliEval(`delete_option( '_brightcove_pending_videos' );`);
	cy.wpCliEval(`delete_option( '_brightcove_tags' );`);
	cy.wpCliEval(`delete_option( '_brightcove_accounts' );`);
});
