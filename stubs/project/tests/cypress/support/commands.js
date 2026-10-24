Cypress.Commands.add('query', (selector, ...args) => {
    return cy.get(`[data-test=${selector}]`, ...args);
});
