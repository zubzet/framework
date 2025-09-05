Cypress.Commands.add('dbSeed', () => {
    cy.exec('npm run seed');
    Cypress.session.clearAllSavedSessions();
})