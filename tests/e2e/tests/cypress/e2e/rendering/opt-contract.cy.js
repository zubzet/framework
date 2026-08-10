// The $opt contract that CanRenderView::legacyOptExpansion hands every view,
// driven by RenderProbeController + Views/renderprobe/opt|resource. Pins the
// data shape (caller data visible top-level AND in $opt, framework expansions in
// $opt), the caller-title regression, and generateResourceLink.
//
// Stateless renders: no cy.dbSeed(). The dev-cache-bust block patches config, so
// it brackets itself with save/restoreConfigBackup.

describe('Render $opt contract', () => {

    describe('data shape', () => {
        it('exposes caller data both top-level ($a) and inside $opt', () => {
            cy.request('/RenderProbe/opt').then((res) => {
                expect(res.status).to.eq(200);
                expect(res.body).to.include('data-test="top-a">alpha<'); // top-level $a
                expect(res.body).to.include('data-test="opt-a">alpha<');  // $opt["a"]
            });
        });

        it('injects the framework expansions into $opt', () => {
            cy.request('/RenderProbe/opt').then((res) => {
                expect(res.body).to.include('data-test="root">/<');
                expect(res.body).to.include('data-test="host">http://localhost:8080<');
                expect(res.body).to.include('data-test="abs-root">http://localhost:8080/<');
                // Objects and the closure are present (not their values, just wired).
                expect(res.body).to.include('data-test="has-user">yes<');
                expect(res.body).to.include('data-test="has-request">yes<');
                expect(res.body).to.include('data-test="has-response">yes<');
                expect(res.body).to.include('data-test="has-genlink">yes<');
            });
        });
    });

    describe('title', () => {
        // Regression: legacyOptExpansion used to overwrite a caller-supplied title
        // with the config default. A caller title must now survive.
        it('preserves a caller-supplied title', () => {
            cy.request('/RenderProbe/opt').then((res) => {
                expect(res.body).to.include('data-test="title">Custom Title Marker<');
            });
        });

        it('falls back to config("pageName") when the caller passes none', () => {
            cy.request('/RenderProbe/optDefaultTitle').then((res) => {
                expect(res.body).to.include('data-test="title">QA Suite<');
            });
        });
    });

    describe('generateResourceLink', () => {
        // assetVersion is a fixed "1.0.0" in the test config, and the root folder
        // is "/". root=true prepends the root; root=false does not.
        it('appends the fixed asset version and honors the root toggle', () => {
            cy.request('/RenderProbe/resourceLink').then((res) => {
                expect(res.status).to.eq(200);
                expect(res.body).to.include('data-test="link-root">/demo/asset.js?v=1.0.0<');
                expect(res.body).to.include('data-test="link-noroot">demo/asset.js?v=1.0.0<');
            });
        });

        describe('dev cache-busting', () => {
            before(() => cy.saveConfigBackup());
            after(() => cy.restoreConfigBackup());

            // assetVersion="dev" swaps the version for time(), so links bust the
            // cache on every request instead of pinning a release version.
            it('uses a time()-based version when assetVersion is "dev"', () => {
                cy.setConfigSetting('assetVersion', 'dev');
                cy.request('/RenderProbe/resourceLink').then((res) => {
                    expect(res.body).to.match(/data-test="link-root">\/demo\/asset\.js\?v=\d{10}</);
                    expect(res.body).to.not.include('?v=dev');
                    expect(res.body).to.not.include('?v=1.0.0');
                });
            });
        });
    });
});
