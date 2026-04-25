describe('Asset Proxy', () => {
    before(() => {
        cy.dbSeed();
    });

    const SENTINEL = 'This file is used for security tests';

    it('serves a framework JS asset with the exact application/javascript content-type', () => {
        cy.request('/_zubzet/asset-proxy/Z.js').then((res) => {
            expect(res.status).to.eq(200);
            expect(res.headers['content-type']).to.eq('application/javascript');
            expect(res.body.length).to.be.greaterThan(0);
        });
    });

    it('serves a framework CSS asset with the exact text/css content-type', () => {
        cy.request('/_zubzet/asset-proxy/css/bootstrap.min.css').then((res) => {
            expect(res.status).to.eq(200);
            expect(res.headers['content-type']).to.match(/^text\/css(;|$)/);
        });
    });

    it('returns 404 for an unknown asset', () => {
        cy.request({
            url: '/_zubzet/asset-proxy/does-not-exist.js',
            failOnStatusCode: false,
        }).then((res) => {
            expect(res.status).to.eq(404);
        });
    });

    it('blocks literal ../ traversal to webroot_security.txt', () => {
        cy.request({
            url: '/_zubzet/asset-proxy/../../../../webroot_security.txt',
            failOnStatusCode: false,
        }).then((res) => {
            expect(res.body).to.not.include(SENTINEL);
        });
    });

    it('blocks URL-encoded ../ traversal to webroot_security.txt', () => {
        // Percent-encode `.` and `/` so the HTTP client can't normalise `../`
        // before it reaches the server.
        const path = '../../../../webroot_security.txt';
        const encoded = path.replaceAll('../', '%2e%2e%2f');

        cy.request({
            url: `/_zubzet/asset-proxy/${encoded}`,
            failOnStatusCode: false,
        }).then((res) => {
            expect(res.body).to.not.include(SENTINEL);
        });
    });

    it('does not leak host files via absolute-path style request', () => {
        cy.request({
            url: '/_zubzet/asset-proxy//etc/passwd',
            failOnStatusCode: false,
        }).then((res) => {
            expect(res.body).to.not.match(/root:.*:0:0:/);
        });
    });

    // Controller-driven tests below — AssetProxyController triggers isolated
    // serve() calls; Cypress inspects the HTTP response directly.

    it('rejects sibling-prefix traversal out of the registered source', () => {
        cy.request({
            url: '/AssetProxy/sibling_prefix_traversal',
            failOnStatusCode: false,
        }).then((res) => {
            expect(res.body).to.not.include(SENTINEL);
        });
    });

    it('returns 404 when the resolved path is a directory', () => {
        cy.request({
            url: '/AssetProxy/directory_request',
            failOnStatusCode: false,
        }).then((res) => {
            expect(res.status).to.eq(404);
        });
    });

    it('falls back to application/octet-stream for unknown mime types', () => {
        cy.request('/AssetProxy/null_mime').then((res) => {
            expect(res.status).to.eq(200);
            expect(res.headers['content-type']).to.eq('application/octet-stream');
        });
    });

    it('rejects symlinks inside source that point outside', () => {
        cy.request({
            url: '/AssetProxy/symlink_escape',
            failOnStatusCode: false,
        }).then((res) => {
            expect(res.body).to.not.include(SENTINEL);
        });
    });

    it('rejects paths with a null-byte suffix', () => {
        cy.request({
            url: '/AssetProxy/null_byte',
            failOnStatusCode: false,
        }).then((res) => {
            expect(res.body).to.not.include('A file inside the assets directory');
        });
    });

    ['empty', 'dot', 'dot_slash'].forEach((variant) => {
        it(`does not dump the source directory (source_root_${variant})`, () => {
            cy.request({
                url: `/AssetProxy/source_root_${variant}`,
                failOnStatusCode: false,
            }).then((res) => {
                // A directory dump would respond 200 with the source files;
                // any rejection (404/500) is acceptable.
                expect(res.status).to.not.eq(200);
                expect(res.body.slice(0, 1000)).to.not.include(SENTINEL);
                expect(res.body.slice(0, 1000)).to.not.include('assets.txt');
            });
        });
    });
});
