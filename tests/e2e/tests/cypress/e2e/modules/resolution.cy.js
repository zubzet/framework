// Lookup semantics across the three roots: recursive controller and model
// discovery inside a module, userspace shadowing a module view, a module
// shadowing a framework view, and the app consuming a module PSR-4 namespace.

describe('Module resolution', () => {
    before(() => {
        cy.dbSeed();
    });

    it('finds a nested module controller and model by bare name', () => {
        // GuestbookAdminController lives in app/Controllers/admin/, the
        // GuestbookStats model in app/Models/reports/; both resolve recursively.
        cy.request('/GuestbookAdmin/stats').then((res) => {
            expect(res.status).to.eq(200);
            const match = res.body.match(/data-test="guestbook-count">(\d+)</);
            expect(match, 'guestbook-count marker').to.not.be.null;
            expect(Number(match[1])).to.be.at.least(3);
        });
    });

    it('resolves an explicit dotted model path to the same model as the bare name', () => {
        cy.request('/GuestbookAdmin/stats').then((bareRes) => {
            cy.request('/GuestbookAdmin/statsExplicit').then((dottedRes) => {
                expect(dottedRes.status).to.eq(200);
                const bare = bareRes.body.match(/data-test="guestbook-count">(\d+)</)[1];
                const dotted = dottedRes.body.match(/data-test="guestbook-count">(\d+)</)[1];
                expect(dotted).to.eq(bare);
            });
        });
    });

    it('never rescues a wrong explicit path through the recursive index', () => {
        // "nowhere.GuestbookStats" names a directory that does not hold the
        // model; explicit paths must fail instead of falling back to the index.
        cy.request({ url: '/GuestbookAdmin/statsMissing', failOnStatusCode: false }).then((res) => {
            expect(res.status).to.eq(500);
        });
    });

    it('renders the userspace footer over the module copy', () => {
        cy.request('/guestbook').then((res) => {
            expect(res.body).to.include('app-footer');
            expect(res.body).to.not.include('module-footer');
        });
    });

    it('renders the module email view over the framework copy', () => {
        cy.request('/ModuleHost/email').then((res) => {
            expect(res.status).to.eq(200);
            expect(res.body).to.include('module-security-mail');
            expect(res.body).to.not.include('Someone has tried multiple times to login');
        });
    });

    it('lets app code consume the module PSR-4 namespace', () => {
        cy.request('/ModuleHost/service').then((res) => {
            expect(res.status).to.eq(200);
            expect(res.body).to.include('QA: module namespace works');
        });
    });

    it('still serves the framework Z.js asset with the exact application/javascript content-type', () => {
        cy.request('/_zubzet/asset-proxy/Z.js').then((res) => {
            expect(res.status).to.eq(200);
            expect(res.headers['content-type']).to.eq('application/javascript');
            expect(res.body.length).to.be.greaterThan(0);
        });
    });

    it('lets a module shadow a framework asset through the proxy', () => {
        // Module mounts come before the framework mounts, matching the global
        // precedence. css/loadCircle.css exists in the framework assets and in
        // the guestbook module; the module copy must win.
        cy.request('/_zubzet/asset-proxy/css/loadCircle.css').then((res) => {
            expect(res.status).to.eq(200);
            expect(res.body).to.include('module-loadcircle-shadow');
        });
    });

    it('refuses to serve source and config extensions from any mount', () => {
        // webroot/deny-probe.php exists in the module mount, so a 404 can only
        // come from the asset proxy extension denylist.
        cy.request({ url: '/_zubzet/asset-proxy/deny-probe.php', failOnStatusCode: false }).then((res) => {
            expect(res.status).to.eq(404);
            // The PHP source must not leak into the response.
            expect(res.body).to.not.include('<?php');
        });
    });
});
