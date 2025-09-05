Cypress.Commands.add('query', (selector, ...args) => {
    return cy.get(`[data-test=${selector}]`, ...args);
});

Cypress.Commands.add('queryLike', (selector, ...args) => {
    return cy.get(`[data-test*=${selector}]`, ...args);
});

Cypress.Commands.add('form', (selector, ...args) => {
    return cy.get(`input[name=${selector}],select[name=${selector}],textarea[name=${selector}]`, ...args);
});

Cypress.Commands.add('fillForm', (inputs) => {
    for(const [name, value] of Object.entries(inputs)) {
        cy.form(name).then(($input) => {
            if($input.is('select')) {
                cy.form(name).select(value);
                return;
            }

            cy.form(name).type(value);
        });
    }
});

Cypress.Commands.add('validateForm', (inputs) => {
    for(const [name, value] of Object.entries(inputs)) {
        cy.form(name).should('have.value', value);
    }
});

Cypress.Commands.add('loginAs', (profile) => {
    profile = profile.toLowerCase();
    cy.fixture('logins.json').then((logins) => {
        cy.session([profile], () => {
            cy.setCookie('z_login_token', logins[profile].token);
        });
    });
});

Cypress.Commands.add('areVisible', (list, callback) => {
    list.forEach((element) => {
        cy.query(element).should("be.visible");
    });
});

Cypress.Commands.add('hasLinks', (list, callback) => {
    list.forEach((element) => {
        cy.get(`a[href*='${element}']`).should("be.visible");
    });
});

Cypress.Commands.add('http', (method, endpoint, body, callback = null) => {
    const httpRequest = (requestBody) => {
        // Allow the request to overwrite the fixture data
        if(callback) callback(requestBody);

        return cy.request({
            method: method,
            url: `/api/v1/${endpoint}`,
            headers: {
                "X-API-KEY": "1234",
            },
            body: requestBody,
            failOnStatusCode: false,
        });
    };

    if(typeof body === 'string') {
        return cy.fixture(body).then(httpRequest);
    }

    return httpRequest(body);
});

Cypress.Commands.add('sendRequest', (url, code = 403) => {
    cy.request({
        url: url,
        failOnStatusCode: false,
    }).then(response => {
        expect(response.status).to.eq(code);
    });
});