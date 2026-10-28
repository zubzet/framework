describe('CSRF protection', () => {
    const PASS = null;
    const CROSS = 'cross site request';
    const BLOCKED = 'blocked origin';

    const SAME_SITE = { csrf_allow: 'same-site' };
    const SITES = ['same-origin', 'none', 'same-site', 'cross-site'];
    const METHODS = ['GET', 'HEAD', 'OPTIONS', 'POST', 'PUT', 'PATCH', 'DELETE'];

    // Runs the decision on simulated headers and settings. The probe request itself carries
    // none of the browser headers, so it passes the real check under the e2e settings.
    function decide(expected, headers, settings = {}) {
        cy.request({ method: 'POST', url: '/CsrfProbe/decide', body: { headers, settings }, failOnStatusCode: false }).then((res) => {
            expect(res.body.verified, JSON.stringify({ headers, settings })).to.eq(expected === PASS);
        });
    }

    // Sends a real request through the real check
    function send(method, url, headers = {}) {
        return cy.request({ method, url, headers, failOnStatusCode: false });
    }

    function expectRejected(res, reason) {
        expect(res.status).to.eq(403);
        const body = typeof res.body === 'string' ? JSON.parse(res.body) : res.body;
        expect(body.error).to.deep.eq({ code: 403, message: 'csrf check failed' });
    }

    describe('the decision', () => {
        // Old browsers, plain http, curl, servers and webhooks
        it('passes without Sec-Fetch-Site unless the source is blocked', () => {
            decide(PASS, {});
            decide(PASS, { Origin: 'https://evil.test' });
            decide(PASS, { Origin: 'null' }, { csrf_blocked_origins: 'https://bucket.example.com' });
            decide(BLOCKED, { Origin: 'https://bucket.example.com' }, { csrf_blocked_origins: 'https://bucket.example.com' });
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

            // An invalid csrf_allow is a misconfiguration and throws
            ['same_site', 'cross-site', ''].forEach((allow) => {
                cy.request({ method: 'POST', url: '/CsrfProbe/decide', body: { headers: { 'Sec-Fetch-Site': 'same-origin' }, settings: { csrf_allow: allow } }, failOnStatusCode: false })
                    .its('status').should('eq', 500);
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

        // The blocklist only knows the origins it lists, an unknown source is not matched
        it('passes a same-site request without a known source, the blocklist cannot match it', () => {
            const settings = { ...SAME_SITE, csrf_blocked_origins: 'https://bucket.example.com' };

            decide(PASS, { 'Sec-Fetch-Site': 'same-site' }, settings);
            decide(PASS, { 'Sec-Fetch-Site': 'same-site', Origin: 'null' }, settings);
            decide(PASS, { 'Sec-Fetch-Site': 'same-site', Origin: '' }, settings);
            decide(PASS, { 'Sec-Fetch-Site': 'same-site', Origin: 'https://www.example.com' }, settings);
            // Only same-site needs it
            decide(PASS, { 'Sec-Fetch-Site': 'same-origin', Origin: 'null' }, settings);
            decide(PASS, { 'Sec-Fetch-Site': 'none' }, settings);
            decide(PASS, {}, settings);
            // Nothing blocked, nothing to tell apart
            decide(PASS, { 'Sec-Fetch-Site': 'same-site' }, SAME_SITE);
            decide(PASS, { 'Sec-Fetch-Site': 'same-site', Origin: 'null' }, SAME_SITE);
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
            decide(PASS, { 'Sec-Fetch-Site': 'cross-site' }, settings);
            decide(BLOCKED, cross('https://evil.test'), settings);
            decide(PASS, cross('https://EVIL.test.other'), settings);
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
            // An entry null is taken literally and trusts every Origin: null
            decide(PASS, cross('null'), { csrf_trusted_origins: 'null' });
            // A blocklist of nothing but empty entries counts as empty
            decide(PASS, { 'Sec-Fetch-Site': 'same-site' }, { ...SAME_SITE, csrf_blocked_origins: ' , ' });
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
            // Origin: null or a missing Origin is not on the list
            decide(PASS, site('same-site', 'null'), settings);
            decide(PASS, { 'Sec-Fetch-Site': 'same-site' }, settings);
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
            decide(PASS, { 'Sec-Fetch-Site': 'same-site' }, settings);
            decide(PASS, site('cross-site', 'https://x.com'), settings);
            decide(CROSS, site('cross-site', 'https://evil.test'), settings);
        });

        it('7. every subdomain may, except x', () => {
            const settings = { ...SAME_SITE, csrf_blocked_origins: 'https://x.example.com' };

            decide(PASS, site('same-site', 'https://www.example.com'), settings);
            decide(BLOCKED, site('same-site', 'https://x.example.com'), settings);
            decide(PASS, { 'Sec-Fetch-Site': 'same-site' }, settings);
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

        it('rejects a request from another site, for every method', () => {
            METHODS.forEach((method) => {
                send(method, '/CsrfProbe/plain', crossSite).then((res) => {
                    expect(res.status, method).to.eq(403);
                    if('HEAD' !== method) expectRejected(res, CROSS);
                });
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

        it('rejects a link from another site', () => {
            send('GET', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'cross-site', Origin: 'https://evil.test' }).then((res) => expectRejected(res, CROSS));
            send('GET', '/', { 'Sec-Fetch-Site': 'cross-site' }).its('status').should('eq', 403);
        });

        it('passes a request from the same origin', () => {
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-origin', Origin: 'http://localhost:8080' }).its('status').should('eq', 200);
        });

        it('passes a browser without Sec-Fetch-Site', () => {
            send('POST', '/CsrfProbe/plain', { Origin: 'https://evil.test' }).its('status').should('eq', 200);
            send('POST', '/CsrfProbe/plain', { Origin: 'null' }).its('status').should('eq', 200);
        });

        it('checks an explicit route', () => {
            send('POST', '/csrf-probe-route', crossSite).then((res) => expectRejected(res, CROSS));
            send('GET', '/csrf-probe-route', { 'Sec-Fetch-Site': 'same-origin' }).its('status').should('eq', 200);
        });

        it('checks the framework routes', () => {
            send('GET', '/_zubzet/health', crossSite).then((res) => expectRejected(res, CROSS));
            send('GET', '/_zubzet/health').its('status').should('eq', 200);
            send('POST', '/_zubzet/profile/clear-sessions', crossSite).then((res) => expectRejected(res, CROSS));
        });

        it('rejects before a missing page answers 404', () => {
            send('GET', '/CsrfProbe/missing-action-and-page', crossSite).then((res) => expectRejected(res, CROSS));
            send('GET', '/NoSuchControllerAnywhere', crossSite).then((res) => expectRejected(res, CROSS));
            send('GET', '/NoSuchControllerAnywhere', { 'Sec-Fetch-Site': 'none' }).its('status').should('eq', 404);
        });

        it('rejects before checkPermission() shows the login', () => {
            send('GET', '/CsrfProbe/guarded', crossSite).then((res) => expectRejected(res, CROSS));
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
            send('GET', '/CsrfProbe/reroute', crossSite).then((res) => expectRejected(res, CROSS));
        });

        it('passes pages a browser loads itself', () => {
            cy.visit('/CsrfProbe/plain');
            cy.contains('success');
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
            preflight('https://other.test').then((res) => expectRejected(res, CROSS));
        });

        it('passes a sibling subdomain under same-site until something is blocked', () => {
            cy.setConfigSetting('csrf_allow', 'same-site');
            send('GET', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-site' }).its('status').should('eq', 200);

            cy.setConfigSetting('csrf_blocked_origins', 'http://bucket.localhost:8080');
            send('GET', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-site' }).its('status').should('eq', 200);
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-site', Origin: 'http://bucket.localhost:8080' }).then((res) => expectRejected(res, BLOCKED));
            send('POST', '/CsrfProbe/plain', { Origin: 'http://bucket.localhost:8080' }).then((res) => expectRejected(res, BLOCKED));
            send('POST', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-site', Origin: 'http://www.localhost:8080' }).its('status').should('eq', 200);
        });

        it('throws for an unknown csrf_allow, but only for a request that carries a browser header', () => {
            cy.setConfigSetting('csrf_allow', 'same_site');
            send('GET', '/CsrfProbe/plain', { 'Sec-Fetch-Site': 'same-site' }).its('status').should('eq', 500);
            send('GET', '/CsrfProbe/plain').its('status').should('eq', 200);
        });
    });
});
