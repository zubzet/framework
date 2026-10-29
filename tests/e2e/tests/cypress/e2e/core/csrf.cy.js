describe('CSRF protection', () => {
    const PASS = null;
    const CROSS = 'cross site request';
    const BLOCKED = 'blocked origin';

    const SAME_SITE = { csrf_allow: 'same-site' };
    const SITES = ['same-origin', 'none', 'same-site', 'cross-site'];
    const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];
    const CHECKED_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];
    const METHODS = [...SAFE_METHODS, ...CHECKED_METHODS];

    // Runs the decision on simulated headers and settings, as a POST unless method says otherwise.
    // The probe request itself carries none of the browser headers, so it passes the real check.
    function decide(expected, headers, settings = {}, method = undefined, additionalMethods = undefined) {
        const body = { headers, settings, method, additionalMethods };
        cy.request({ method: 'POST', url: '/CsrfProbe/decide', body, failOnStatusCode: false }).then((res) => {
            expect(res.body.verified, JSON.stringify(body)).to.eq(expected === PASS);
        });
    }

    // Sends a real request through the real check
    function send(method, url, headers = {}) {
        return cy.request({ method, url, headers, failOnStatusCode: false });
    }

    // A rejected request gets the 403 page of the error controller, a HEAD without its body
    function expectRejected(res, reason) {
        expect(res.status, reason).to.eq(403);
        if(res.body) expect(res.body).to.include('Page not for you');
    }

    describe('the decision', () => {
        // Old browsers, plain http, curl, servers and webhooks
        it('passes without Sec-Fetch-Site unless the source is blocked or unknown', () => {
            decide(PASS, {});
            decide(PASS, { Origin: 'https://evil.test' });
            decide(BLOCKED, { Origin: 'https://bucket.example.com' }, { csrf_blocked_origins: 'https://bucket.example.com' });
            // A hidden source could be the blocked one
            decide(CROSS, { Origin: 'null' }, { csrf_blocked_origins: 'https://bucket.example.com' });
            decide(CROSS, { Origin: 'null' });
        });

        it('takes the source from Origin', () => {
            const trusted = { csrf_trusted_origins: 'https://partner.test' };

            decide(PASS, { 'Sec-Fetch-Site': 'cross-site', Origin: 'https://partner.test' }, trusted);
            decide(PASS, { 'Sec-Fetch-Site': 'cross-site', Origin: 'https://Partner.TEST' }, trusted);
            // Origin: null and a missing Origin leave it unknown
            decide(CROSS, { 'Sec-Fetch-Site': 'cross-site', Origin: 'null' }, trusted);
            decide(CROSS, { 'Sec-Fetch-Site': 'cross-site' }, trusted);
            decide(CROSS, { 'Sec-Fetch-Site': 'cross-site', Origin: 'partner.test' }, trusted);
        });

        it('lets the Sec-Fetch-Site values of csrf_allow through, anything else only from a trusted source', () => {
            const expectations = {
                'same-origin': [PASS, PASS, CROSS, CROSS],
                'same-site': [PASS, PASS, PASS, CROSS],
                any: [PASS, PASS, PASS, PASS],
                // The value is case insensitive
                'Same-Site': [PASS, PASS, PASS, CROSS],
            };
            Object.entries(expectations).forEach(([allow, expected]) => {
                SITES.forEach((site, i) => decide(expected[i], { 'Sec-Fetch-Site': site, Origin: 'https://www.example.com' }, { csrf_allow: allow }));
            });

            // An invalid csrf_allow is a misconfiguration and throws, whatever source the request has
            ['same_site', 'cross-site', ''].forEach((allow) => {
                [{ 'Sec-Fetch-Site': 'same-origin' }, { 'Sec-Fetch-Site': 'same-site', Origin: 'null' }, { Origin: 'https://www.example.com' }].forEach((headers) => {
                    cy.request({ method: 'POST', url: '/CsrfProbe/decide', body: { headers, settings: { csrf_allow: allow } }, failOnStatusCode: false })
                        .its('status').should('eq', 500);
                });
            });

            // The value is case insensitive, any other one is not let through
            ['Same-Origin', 'NONE'].forEach((site) => {
                decide(PASS, { 'Sec-Fetch-Site': site, Origin: 'https://app.example.com' }, SAME_SITE);
            });
            ['same_origin', 'same-origin, cross-site', 'bogus'].forEach((site) => {
                decide(CROSS, { 'Sec-Fetch-Site': site, Origin: 'https://app.example.com' }, SAME_SITE);
            });

            // A trusted source passes whatever Sec-Fetch-Site says
            const trusted = { csrf_trusted_origins: 'https://www.example.com' };
            decide(PASS, { 'Sec-Fetch-Site': 'same-site', Origin: 'https://www.example.com' }, trusted);
            decide(PASS, { 'Sec-Fetch-Site': 'cross-site', Origin: 'https://www.example.com' }, trusted);
            decide(PASS, { 'Sec-Fetch-Site': 'bogus', Origin: 'https://www.example.com' }, trusted);
            decide(CROSS, { 'Sec-Fetch-Site': 'same-site', Origin: 'https://www2.example.com' }, trusted);
        });

        // The lists only know the origins they list, a page that hides its Origin (no-referrer,
        // a sandboxed iframe) would slip past the blocklist, so it gets the strictest rules
        it('passes a request without a known source only from the same origin or no page', () => {
            const settings = { ...SAME_SITE, csrf_blocked_origins: 'https://bucket.example.com' };

            decide(CROSS, { 'Sec-Fetch-Site': 'same-site' }, settings);
            decide(CROSS, { 'Sec-Fetch-Site': 'same-site', Origin: 'null' }, settings);
            decide(CROSS, { 'Sec-Fetch-Site': 'same-site', Origin: '' }, settings);
            decide(CROSS, { 'Sec-Fetch-Site': 'same-site', Origin: ' NULL ' }, settings);
            decide(CROSS, { 'Sec-Fetch-Site': 'cross-site', Origin: 'null' }, settings);
            decide(PASS, { 'Sec-Fetch-Site': 'same-site', Origin: 'https://www.example.com' }, settings);
            decide(PASS, { 'Sec-Fetch-Site': 'same-origin', Origin: 'null' }, settings);
            decide(PASS, { 'Sec-Fetch-Site': 'none' }, settings);
            decide(PASS, {}, settings);
            // Even with nothing blocked
            decide(CROSS, { 'Sec-Fetch-Site': 'same-site' }, SAME_SITE);
            decide(CROSS, { 'Sec-Fetch-Site': 'same-site', Origin: 'null' }, SAME_SITE);
        });

        it('rejects a blocked source on every Sec-Fetch-Site that passes', () => {
            const settings = { ...SAME_SITE, csrf_blocked_origins: 'https://bucket.example.com' };

            ['same-origin', 'none', 'same-site'].forEach((site) => {
                decide(BLOCKED, { 'Sec-Fetch-Site': site, Origin: 'https://bucket.example.com' }, settings);
            });
            decide(BLOCKED, { 'Sec-Fetch-Site': 'same-site', Origin: 'https://bucket.example.com' }, settings);
            // Not let through by csrf_allow, only the trusted list decides
            decide(CROSS, { 'Sec-Fetch-Site': 'cross-site', Origin: 'https://bucket.example.com' }, settings);
            // Only the exact origin
            decide(PASS, { 'Sec-Fetch-Site': 'same-site', Origin: 'http://bucket.example.com' }, settings);
            decide(PASS, { 'Sec-Fetch-Site': 'same-site', Origin: 'https://bucket.example.com:8443' }, settings);
            decide(PASS, { 'Sec-Fetch-Site': 'same-site', Origin: 'https://x.bucket.example.com' }, settings);
        });
    });

    describe('the methods', () => {
        const crossSite = { 'Sec-Fetch-Site': 'cross-site', Origin: 'https://evil.test' };

        it('skips GET, HEAD and OPTIONS and checks every other method', () => {
            SAFE_METHODS.forEach((method) => decide(PASS, crossSite, {}, method));
            CHECKED_METHODS.forEach((method) => decide(CROSS, crossSite, {}, method));
            // The request method is case insensitive
            decide(PASS, crossSite, {}, 'get');
            decide(CROSS, crossSite, {}, 'post');
        });

        it('checks the safe methods passed to it, case insensitive', () => {
            decide(CROSS, crossSite, {}, 'GET', ['GET']);
            decide(PASS, crossSite, {}, 'HEAD', ['GET']);
            decide(CROSS, crossSite, {}, 'GET', ['get', 'head']);
            decide(CROSS, crossSite, {}, 'HEAD', ['get', 'head']);
            decide(PASS, crossSite, {}, 'OPTIONS', ['get', 'head']);
            decide(CROSS, crossSite, {}, 'POST', ['GET']);
        });

        // A browser sends no Origin on a link, so an opted-in GET only passes from the same origin or no page
        it('judges an opted-in GET like any checked request', () => {
            decide(PASS, { 'Sec-Fetch-Site': 'same-origin' }, SAME_SITE, 'GET', ['GET']);
            decide(PASS, { 'Sec-Fetch-Site': 'none' }, SAME_SITE, 'GET', ['GET']);
            decide(PASS, {}, SAME_SITE, 'GET', ['GET']);
            decide(CROSS, { 'Sec-Fetch-Site': 'same-site' }, SAME_SITE, 'GET', ['GET']);
            decide(CROSS, { 'Sec-Fetch-Site': 'cross-site' }, { csrf_trusted_origins: 'https://partner.test' }, 'GET', ['GET']);
        });

        it('only validates csrf_allow for a checked request', () => {
            const body = (method, additionalMethods) => ({ headers: { 'Sec-Fetch-Site': 'same-site' }, settings: { csrf_allow: 'same_site' }, method, additionalMethods });

            cy.request({ method: 'POST', url: '/CsrfProbe/decide', body: body('GET') }).its('body.verified').should('eq', true);
            cy.request({ method: 'POST', url: '/CsrfProbe/decide', body: body('GET', ['GET']), failOnStatusCode: false }).its('status').should('eq', 500);
        });
    });

    describe('the origin lists', () => {
        const cross = (Origin) => ({ 'Sec-Fetch-Site': 'cross-site', Origin });

        it('match the entry exactly, as the browser sends the Origin', () => {
            decide(CROSS, cross('https://partner.test:443'), { csrf_trusted_origins: 'https://partner.test' });
            decide(CROSS, cross('https://partner.test'), { csrf_trusted_origins: 'https://partner.test:443' });
            decide(CROSS, cross('https://partner.test'), { csrf_trusted_origins: 'https://partner.test/' });
            decide(CROSS, cross('https://partner.test'), { csrf_trusted_origins: 'https://partner.test/path' });
            decide(PASS, cross(' https://partner.test '), { csrf_trusted_origins: 'https://partner.test' });
            decide(CROSS, cross(''), { csrf_trusted_origins: 'https://partner.test' });
        });

        it('lets a trusted origin win over the blocklist across sites, but not within allowed ones', () => {
            const both = { csrf_trusted_origins: 'https://partner.test', csrf_blocked_origins: 'https://partner.test' };

            decide(PASS, cross('https://partner.test'), both);
            decide(BLOCKED, { 'Sec-Fetch-Site': 'same-origin', Origin: 'https://partner.test' }, both);
            decide(BLOCKED, { Origin: 'https://partner.test' }, both);
        });

        it('keeps the blocklist under csrf_allow = any', () => {
            const settings = { csrf_allow: 'any', csrf_blocked_origins: 'https://evil.test' };

            decide(PASS, cross('https://partner.test'), settings);
            decide(BLOCKED, cross('https://evil.test'), settings);
            decide(PASS, cross('https://EVIL.test.other'), settings);
            // Every site may, so an unknown source has nothing to be told apart from
            decide(PASS, { 'Sec-Fetch-Site': 'cross-site' }, settings);
            decide(PASS, cross('null'), settings);
            decide(PASS, { Origin: 'null' }, settings);
        });

        it('ignore case and spaces around an entry', () => {
            decide(PASS, cross('https://partner.test'), { csrf_trusted_origins: ' HTTPS://Partner.Test ' });
            decide(BLOCKED, { Origin: 'https://bucket.example.com' }, { csrf_blocked_origins: 'https://Bucket.Example.com' });
        });

        it('compare scheme, host and port exactly', () => {
            const trusted = { csrf_trusted_origins: 'https://partner.test' };

            decide(CROSS, cross('http://partner.test'), trusted);
            decide(CROSS, cross('https://partner.test:8443'), trusted);
            decide(CROSS, cross('https://www.partner.test'), trusted);
            decide(CROSS, cross('https://partner.test.evil.test'), trusted);
        });

        it('skip empty entries and match no invalid one', () => {
            decide(PASS, cross('https://b.test'), { csrf_trusted_origins: ' https://a.test ,, https://b.test, ' });
            ['partner.test', '//partner.test', '*', 'null', ''].forEach((entry) => {
                decide(CROSS, cross('https://partner.test'), { csrf_trusted_origins: `${entry}, https://ok.test` });
            });
            // A wildcard matches nothing
            decide(CROSS, cross('https://a.partner.test'), { csrf_trusted_origins: 'https://*.partner.test' });
            // An entry null never trusts an Origin: null, an unknown source is decided before the lists
            decide(CROSS, cross('null'), { csrf_trusted_origins: 'null' });
            // A blocklist of nothing but empty entries counts as empty
            decide(PASS, { 'Sec-Fetch-Site': 'same-site', Origin: 'https://www.example.com' }, { ...SAME_SITE, csrf_blocked_origins: ' , ' });
        });
    });

    // The edge cases of docs/core-features/csrf-protection.md, the application runs on https://app.example.com
    describe('the documented scenarios', () => {
        const site = (siteValue, Origin) => ({ 'Sec-Fetch-Site': siteValue, Origin });

        it('1. the same application on several subdomains', () => {
            decide(PASS, site('same-origin', 'https://a.example.com'));
            decide(PASS, site('same-origin', 'https://b.example.com'));
            decide(CROSS, site('same-site', 'https://a.example.com'));
        });

        it('2. a bucket on a subdomain hosts foreign content, every other subdomain may', () => {
            const settings = { ...SAME_SITE, csrf_blocked_origins: 'https://bucket.example.com' };

            decide(BLOCKED, site('same-site', 'https://bucket.example.com'), settings);
            decide(BLOCKED, { 'Sec-Fetch-Site': 'same-site', Origin: 'https://bucket.example.com' }, settings);
            decide(BLOCKED, { Origin: 'https://bucket.example.com' }, settings);
            // The bucket hiding its Origin cannot be told apart, so a hidden Origin is rejected
            decide(CROSS, site('same-site', 'null'), settings);
            decide(CROSS, { 'Sec-Fetch-Site': 'same-site' }, settings);
            decide(PASS, site('same-site', 'https://www.example.com'), settings);
        });

        it('3. subdomain x may, subdomain y may not', () => {
            const trusted = { csrf_trusted_origins: 'https://x.example.com' };
            decide(PASS, site('same-site', 'https://x.example.com'), trusted);
            decide(CROSS, site('same-site', 'https://y.example.com'), trusted);
            // Without Sec-Fetch-Site only the blocklist applies
            decide(PASS, { Origin: 'https://y.example.com' }, trusted);

            const blocked = { ...SAME_SITE, csrf_blocked_origins: 'https://y.example.com' };
            decide(PASS, site('same-site', 'https://x.example.com'), blocked);
            decide(BLOCKED, site('same-site', 'https://y.example.com'), blocked);
            decide(BLOCKED, { Origin: 'https://y.example.com' }, blocked);
        });

        it('4. only site x.com may', () => {
            const settings = { csrf_trusted_origins: 'https://x.com' };

            decide(PASS, site('cross-site', 'https://x.com'), settings);
            decide(PASS, { 'Sec-Fetch-Site': 'cross-site', Origin: 'https://x.com' }, settings);
            decide(CROSS, site('cross-site', 'https://www.x.com'), settings);
            decide(CROSS, site('same-site', 'https://www.example.com'), settings);
        });

        it('5. only site x.com and subdomain y may', () => {
            const settings = { csrf_trusted_origins: 'https://x.com, https://y.example.com' };

            decide(PASS, site('cross-site', 'https://x.com'), settings);
            decide(PASS, site('same-site', 'https://y.example.com'), settings);
            decide(CROSS, site('same-site', 'https://z.example.com'), settings);
            decide(CROSS, site('cross-site', 'https://evil.test'), settings);
        });

        it('6. every subdomain may, plus site x.com', () => {
            const settings = { ...SAME_SITE, csrf_trusted_origins: 'https://x.com' };

            decide(PASS, site('same-site', 'https://www.example.com'), settings);
            decide(CROSS, { 'Sec-Fetch-Site': 'same-site' }, settings);
            decide(PASS, site('cross-site', 'https://x.com'), settings);
            decide(CROSS, site('cross-site', 'null'), settings);
            decide(CROSS, site('cross-site', 'https://evil.test'), settings);
        });

        it('7. every subdomain may, except x', () => {
            const settings = { ...SAME_SITE, csrf_blocked_origins: 'https://x.example.com' };

            decide(PASS, site('same-site', 'https://www.example.com'), settings);
            decide(BLOCKED, site('same-site', 'https://x.example.com'), settings);
            // x hiding its Origin with no-referrer does not get past the blocklist
            decide(CROSS, site('same-site', 'null'), settings);
            decide(CROSS, { 'Sec-Fetch-Site': 'same-site' }, settings);
        });

        it('8. the application on a subdomain, the parent host may', () => {
            const settings = { csrf_trusted_origins: 'https://example.com' };

            decide(PASS, site('same-site', 'https://example.com'), settings);
            decide(PASS, { 'Sec-Fetch-Site': 'same-site', Origin: 'https://example.com' }, settings);
            decide(CROSS, site('same-site', 'https://www.example.com'), settings);
        });

        it('9. the application on a subdomain, another subdomain may', () => {
            const settings = { csrf_trusted_origins: 'https://b.example.com' };

            decide(PASS, site('same-site', 'https://b.example.com'), settings);
            decide(CROSS, site('same-site', 'https://c.example.com'), settings);
            decide(CROSS, site('same-site', 'https://example.com'), settings);
        });

        it('10. the nested subdomain x.y may, all others not', () => {
            const settings = { csrf_trusted_origins: 'https://x.y.example.com' };

            decide(PASS, site('same-site', 'https://x.y.example.com'), settings);
            decide(PASS, { 'Sec-Fetch-Site': 'same-site', Origin: 'https://x.y.example.com' }, settings);
            decide(CROSS, site('same-site', 'https://y.example.com'), settings);
            decide(CROSS, site('same-site', 'https://a.x.y.example.com'), settings);
        });

        // Ports are not part of a site, a browser sends same-site across them
        it('11. the same host may on port 3000, not on port 4000', () => {
            const trusted = { csrf_trusted_origins: 'https://app.example.com:3000' };
            decide(PASS, site('same-site', 'https://app.example.com:3000'), trusted);
            decide(CROSS, site('same-site', 'https://app.example.com:4000'), trusted);

            const blocked = { ...SAME_SITE, csrf_blocked_origins: 'https://app.example.com:4000' };
            decide(PASS, site('same-site', 'https://app.example.com:3000'), blocked);
            decide(BLOCKED, site('same-site', 'https://app.example.com:4000'), blocked);
        });
    });

    // Real requests through the real check, the e2e app runs at localhost:8080 under the default settings
    describe('the check on real requests', () => {
        const crossSite = { 'Sec-Fetch-Site': 'cross-site', Origin: 'https://evil.test' };

        it('rejects a request from another site, for every checked method', () => {
            CHECKED_METHODS.forEach((method) => {
                send(method, '/CsrfProbe/plain', crossSite).then((res) => expectRejected(res, method));
            });
        });

        it('passes GET, HEAD and OPTIONS from another site', () => {
            SAFE_METHODS.forEach((method) => {
                send(method, '/CsrfProbe/plain', crossSite).its('status').should('eq', 200);
            });
        });

        it('passes a request from no page, for every method', () => {
            METHODS.forEach((method) => {
                send(method, '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'none' }).its('status').should('eq', 200);
            });
        });

        it('passes a request without the browser headers, for every method', () => {
            METHODS.forEach((method) => {
                send(method, '/CsrfProbe/plain').its('status').should('eq', 200);
            });
        });

        it('passes a link from another site', () => {
            send('GET', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'cross-site' }).its('status').should('eq', 200);
            send('GET', '/', { 'Sec-Fetch-Site': 'cross-site' }).its('status').should('eq', 200);
        });

        it('passes a request from the same origin', () => {
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-origin', Origin: 'http://localhost:8080' }).its('status').should('eq', 200);
        });

        it('passes a browser without Sec-Fetch-Site unless it hides its Origin', () => {
            send('POST', '/CsrfProbe/plain', { Origin: 'https://evil.test' }).its('status').should('eq', 200);
            send('POST', '/CsrfProbe/plain', { Origin: 'null' }).then((res) => expectRejected(res, CROSS));
        });

        it('rejects a form that hides its Origin, unless it comes from the same origin', () => {
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'cross-site', Origin: 'null' }).then((res) => expectRejected(res, CROSS));
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-site', Origin: 'null' }).then((res) => expectRejected(res, CROSS));
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-origin', Origin: 'null' }).its('status').should('eq', 200);
        });

        it('checks an explicit route', () => {
            send('POST', '/csrf-probe-route', crossSite).then((res) => expectRejected(res, CROSS));
            send('GET', '/csrf-probe-route', { 'Sec-Fetch-Site': 'same-origin' }).its('status').should('eq', 200);
        });

        it('checks the framework routes', () => {
            send('POST', '/_zubzet/health', crossSite).then((res) => expectRejected(res, CROSS));
            send('GET', '/_zubzet/health', crossSite).its('status').should('eq', 200);
            send('POST', '/_zubzet/profile/clear-sessions', crossSite).then((res) => expectRejected(res, CROSS));
        });

        it('rejects before a missing page answers 404', () => {
            send('POST', '/CsrfProbe/missing-action-and-page', crossSite).then((res) => expectRejected(res, CROSS));
            send('POST', '/NoSuchControllerAnywhere', crossSite).then((res) => expectRejected(res, CROSS));
            send('POST', '/NoSuchControllerAnywhere', { 'Sec-Fetch-Site': 'none' }).its('status').should('eq', 404);
        });

        it('rejects before checkPermission() shows the login', () => {
            send('POST', '/CsrfProbe/guarded', crossSite).then((res) => {
                expectRejected(res, CROSS);
                expect(res.body).not.to.include('data-test="btn-login"');
            });
            send('GET', '/CsrfProbe/guarded', { 'Sec-Fetch-Site': 'same-origin' }).then((res) => {
                expect(res.status).to.eq(200);
                expect(res.body).to.include('data-test="btn-login"');
            });
        });

        it('passes a reroute of a request that passed', () => {
            send('GET', '/CsrfProbe/reroute', { 'Sec-Fetch-Site': 'same-origin' }).then((res) => {
                expect(res.status).to.eq(200);
                expect(res.body).to.include('"success"');
            });
            send('POST', '/CsrfProbe/reroute', crossSite).then((res) => expectRejected(res, CROSS));
        });

        it('checks an action that opts GET back in, by default only GET', () => {
            send('GET', '/CsrfProbe/optIn', crossSite).then((res) => expectRejected(res, CROSS));
            send('GET', '/CsrfProbe/optIn', { 'Sec-Fetch-Site': 'same-site' }).then((res) => expectRejected(res, CROSS));
            send('HEAD', '/CsrfProbe/optIn', crossSite).its('status').should('eq', 200);
            send('OPTIONS', '/CsrfProbe/optIn', crossSite).its('status').should('eq', 200);
            send('POST', '/CsrfProbe/optIn', crossSite).then((res) => expectRejected(res, CROSS));
            send('GET', '/CsrfProbe/optIn', { 'Sec-Fetch-Site': 'same-origin' }).its('status').should('eq', 200);
            send('GET', '/CsrfProbe/optIn', { 'Sec-Fetch-Site': 'none' }).its('status').should('eq', 200);
            send('GET', '/CsrfProbe/optIn').its('status').should('eq', 200);
        });

        it('checks the methods an action opts back in, written in any case', () => {
            send('GET', '/CsrfProbe/optInHead', crossSite).then((res) => expectRejected(res, CROSS));
            send('HEAD', '/CsrfProbe/optInHead', crossSite).then((res) => expectRejected(res, CROSS));
            send('OPTIONS', '/CsrfProbe/optInHead', crossSite).its('status').should('eq', 200);
            send('HEAD', '/CsrfProbe/optInHead', { 'Sec-Fetch-Site': 'same-origin' }).its('status').should('eq', 200);
        });

        it('passes pages a browser loads itself', () => {
            cy.visit('/CsrfProbe/plain');
            cy.contains('success');
        });
    });

    // The framework's logout is a GET that changes state, so it opts GET and HEAD back in
    describe('the logout', () => {
        // A fresh session per case, so the fixture tokens of the other specs stay valid
        function login() {
            cy.fixture('logins.json').then(({ customer }) => {
                cy.request({ method: 'POST', url: '/login', form: true, body: { name: customer.name, password: customer.password } });
            });
        }

        // The customer lacks admin.panel: logged in sees the 403 page, a guest the login
        function expectLoggedIn(loggedIn) {
            send('GET', '/CsrfProbe/guarded').its('body').should('include', loggedIn ? 'Page not for you' : 'data-test="btn-login"');
        }

        it('logs out from the own pages, a typed URL and without a browser', () => {
            [{ 'Sec-Fetch-Site': 'same-origin' }, { 'Sec-Fetch-Site': 'none' }, {}].forEach((headers) => {
                login();
                expectLoggedIn(true);
                send('GET', '/login/logout', headers);
                expectLoggedIn(false);
            });
        });

        it('rejects a logout from another site or subdomain, by GET and by HEAD', () => {
            login();
            [
                { 'Sec-Fetch-Site': 'cross-site' },
                { 'Sec-Fetch-Site': 'same-site' },
                // A subdomain with no-referrer
                { 'Sec-Fetch-Site': 'same-site', Origin: 'null' },
            ].forEach((headers) => {
                ['GET', 'HEAD'].forEach((method) => {
                    send(method, '/login/logout', headers).then((res) => expectRejected(res, `${method} ${JSON.stringify(headers)}`));
                });
            });
            expectLoggedIn(true);
        });
    });

    describe('the check under changed settings', () => {
        before(() => cy.saveConfigBackup());

        after(() => cy.restoreConfigBackup());

        afterEach(() => {
            cy.setConfigSetting('csrf_allow', 'same-origin');
            cy.setConfigSetting('csrf_trusted_origins', '');
            cy.setConfigSetting('csrf_blocked_origins', '');
        });

        it('passes a preflight, the request and a link from a trusted origin', () => {
            cy.setConfigSetting('csrf_trusted_origins', 'https://partner.test');

            const preflight = (Origin) => send('OPTIONS', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'cross-site', Origin, 'Access-Control-Request-Method': 'PUT' });
            preflight('https://partner.test').its('status').should('eq', 200);
            send('PUT', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'cross-site', Origin: 'https://partner.test' }).its('status').should('eq', 200);
            send('GET', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'cross-site', Origin: 'https://partner.test' }).its('status').should('eq', 200);
            // The preflight is an OPTIONS and passes, the request it announces is checked
            preflight('https://other.test').its('status').should('eq', 200);
            send('PUT', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'cross-site', Origin: 'https://other.test' }).then((res) => expectRejected(res, CROSS));
        });

        it('passes a sibling subdomain under same-site until it is blocked', () => {
            cy.setConfigSetting('csrf_allow', 'same-site');
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-site', Origin: 'http://bucket.localhost:8080' }).its('status').should('eq', 200);
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-site', Origin: 'null' }).then((res) => expectRejected(res, CROSS));

            cy.setConfigSetting('csrf_blocked_origins', 'http://bucket.localhost:8080');
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-site', Origin: 'http://bucket.localhost:8080' }).then((res) => expectRejected(res, BLOCKED));
            // The bucket hiding its Origin does not get past the blocklist
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-site', Origin: 'null' }).then((res) => expectRejected(res, BLOCKED));
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-site' }).then((res) => expectRejected(res, BLOCKED));
            // Its links still pass
            send('GET', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-site' }).its('status').should('eq', 200);
            send('POST', '/CsrfProbe/plain', { Origin: 'http://bucket.localhost:8080' }).then((res) => expectRejected(res, BLOCKED));
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-site', Origin: 'http://www.localhost:8080' }).its('status').should('eq', 200);
        });

        it('throws for an unknown csrf_allow, but only for a checked request that carries a browser header', () => {
            cy.setConfigSetting('csrf_allow', 'same_site');
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-site' }).its('status').should('eq', 500);
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-site', Origin: 'null' }).its('status').should('eq', 500);
            send('POST', '/CsrfProbe/plain').its('status').should('eq', 200);
            send('GET', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-site' }).its('status').should('eq', 200);
        });

        it('passes every site under any, a hidden Origin included', () => {
            cy.setConfigSetting('csrf_allow', 'any');
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'cross-site', Origin: 'https://evil.test' }).its('status').should('eq', 200);
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'cross-site', Origin: 'null' }).its('status').should('eq', 200);
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'cross-site' }).its('status').should('eq', 200);
        });
    });
});
