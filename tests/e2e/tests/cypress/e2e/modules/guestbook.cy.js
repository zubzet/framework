// The guestbook module as a real feature: module routes, controller, model,
// migration + seed data, a POSTable form and a module webroot asset, all
// provided by tests/e2e/modules/guestbook and served through the app.

describe('Guestbook module', () => {
    before(() => {
        cy.dbSeed();
    });

    it('serves /guestbook with the config default title, default skin and all seeded entries', () => {
        cy.request('/guestbook').then((res) => {
            expect(res.status).to.eq(200);
            // The module ini is not merged into the app config at this point,
            // so the controller falls back to its code default "Guestbook".
            expect(res.body).to.include('data-test="guestbook-title">Guestbook<');
            expect(res.body).to.include('data-test="guestbook-skin">default<');
            expect(res.body).to.include('data-test="guestbook-entry"');
            expect(res.body).to.include('Guestbook is ready');
            expect(res.body).to.include('Welcome to the guestbook');
            expect(res.body).to.include('Second seeded entry');
        });
    });

    it('serves the same page through the convention URL /Guestbook/index', () => {
        cy.request('/Guestbook/index').then((res) => {
            expect(res.status).to.eq(200);
            expect(res.body).to.include('data-test="guestbook-title"');
        });
    });

    it('accepts a POSTed entry and lists it immediately and on the next GET', () => {
        cy.request({
            method: 'POST',
            url: '/guestbook/add',
            form: true,
            body: {
                author: 'Cypress',
                message: 'Entry from the e2e run',
            },
        }).then((res) => {
            expect(res.status).to.eq(200);
            // action_add re-renders the index with fresh entries, so the POST
            // response itself already lists the new entry.
            expect(res.body).to.include('Entry from the e2e run');
        });

        cy.request('/guestbook').then((res) => {
            expect(res.body).to.include('Entry from the e2e run');
        });
    });

    it('links the module css and serves it through the asset proxy', () => {
        cy.request('/guestbook').then((res) => {
            expect(res.body).to.include('guestbook.css');
        });

        cy.request('/_zubzet/asset-proxy/guestbook.css').then((res) => {
            expect(res.status).to.eq(200);
            expect(res.headers['content-type']).to.match(/^text\/css(;|$)/);
            expect(res.body).to.include('guestbook-css-marker');
        });
    });
});
