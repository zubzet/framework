Cypress.Commands.add('dbSeed', ({ exclude = [], include = [] } = {}) => {
    const selectors = [
        ...exclude.map((path) => `-e ${path}`),
        ...include.map((path) => `-i ${path}`),
    ];

    // Skip the npm-script bootstrap that `npm run seed` adds.
    cy.exec(['docker exec application php index.php db:seed', ...selectors].join(' '));
    Cypress.session.clearAllSavedSessions();
})