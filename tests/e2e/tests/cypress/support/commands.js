// A cy.request() that carries the CSRF token the way Z.js does.
//
// Z.Forms / Z.Request read the `z_csrf` cookie and echo it back as an
// `X-CSRF-Token` header; the Csrf object rejects any marked request
// (isFormData / _zReq) that arrives without a matching header. A spec posting
// through a bare cy.request() bypasses Z.js entirely and therefore looks
// exactly like a forged cross-origin request - 403. Use this instead whenever
// a spec simulates a Z.Forms submit without driving the actual UI.
Cypress.Commands.add('zRequest', (options) => {
    const send = (token) => cy.request({
        ...options,
        headers: { ...(options.headers || {}), 'X-CSRF-Token': token },
    });

    return cy.getCookie('z_csrf').then((cookie) => {
        if (cookie) return send(cookie.value);

        // No token in the jar yet (fresh context, or cy.session cleared it).
        // Every response issues one, so any cheap GET primes the cookie.
        return cy.request('/_zubzet/health')
            .then(() => cy.getCookie('z_csrf'))
            .then((fresh) => send(fresh ? fresh.value : ''));
    });
});

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

Cypress.Commands.add('setConfigSetting', (key, value) => {
    const CONFIG_PATH = '../z_config/z_settings.ini';
    cy.readFile(CONFIG_PATH, 'utf8').then((content) => {
        const updated = content.replace(
            new RegExp(`^${key}\\s*=.*`, 'm'),
            `${key} = ${value}`
        );
        cy.writeFile(CONFIG_PATH, updated);
    });
});

let configBackup = null;

Cypress.Commands.add('saveConfigBackup', () => {
    const CONFIG_PATH = '../z_config/z_settings.ini';
    cy.readFile(CONFIG_PATH, 'utf8').then((content) => {
        configBackup = content;
    });
});

Cypress.Commands.add('restoreConfigBackup', () => {
    const CONFIG_PATH = '../z_config/z_settings.ini';
    if (configBackup !== null) {
        cy.writeFile(CONFIG_PATH, configBackup);
    }
});
// Runs a SQL statement directly on one Galera node (bypassing the database
// endpoint) and yields its trimmed stdout. For cluster assertions that must
// observe a specific node, e.g. replication checks.
Cypress.Commands.add('dbQueryNode', (node, sql) => {
    const command = `docker exec ${node} mariadb -uroot -proot_password --silent -e "${sql.replace(/"/g, '\\"')}"`;
    return cy.exec(command, { timeout: 15000 }).then(({ stdout }) => stdout.trim());
});
