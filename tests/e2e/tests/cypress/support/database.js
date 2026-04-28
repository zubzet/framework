Cypress.Commands.add('dbSeed', () => {
    // Skip the npm-script bootstrap that `npm run seed` adds.
    cy.exec('docker exec application php index.php db:seed');
    Cypress.session.clearAllSavedSessions();
})