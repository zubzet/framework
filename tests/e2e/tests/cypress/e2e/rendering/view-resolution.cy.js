// View-name resolution through the Katana engine's finder chain, driven by
// RenderProbeController. Covers what the framework normalizes (extensions),
// what Katana resolves natively (dot notation), userspace-overrides-framework
// precedence, and cross-root composition (app view + framework layout +
// framework component) that the old single-root renderer could not span.
//
// These are stateless renders: no cy.dbSeed().

describe('View resolution', () => {

    describe('view-name equivalence (same app view, four references)', () => {
        // core/render prints <h2 data-test="data">$opt["data"]</h2>; each action
        // passes a distinct marker so we know the right file was rendered.
        const cases = [
            { url: '/RenderProbe/extNone',  ref: 'bare "core/render"',       marker: 'ExtNone' },
            { url: '/RenderProbe/extBlade', ref: '"core/render.blade.php"',  marker: 'ExtBlade' },
            { url: '/RenderProbe/extPhp',   ref: 'legacy "core/render.php"', marker: 'ExtPhp' },
            { url: '/RenderProbe/dotted',   ref: 'dotted "core.render"',     marker: 'Dotted' },
        ];

        cases.forEach(({ url, ref, marker }) => {
            it(`resolves ${ref} to app/Views/core/render.blade.php`, () => {
                cy.request(url).then((res) => {
                    expect(res.status).to.eq(200);
                    expect(res.body).to.include('data-test="title">Render<');
                    expect(res.body).to.include(`data-test="data">${marker}<`);
                });
            });
        });
    });

    describe('userspace overrides framework', () => {
        // `login` exists in BOTH roots. The app copy carries an explicit
        // data-test="login-source">app< marker; the framework copy does not (and
        // has the "Don't have a account?" signup link the app copy omits).
        it('renders the app copy by default (escape hatch off)', () => {
            cy.request('/RenderProbe/overrideAppWins').then((res) => {
                expect(res.status).to.eq(200);
                expect(res.body).to.include('data-test="login-source"');  // app copy marker
                expect(res.body).to.not.include("Don't have a account?"); // framework copy
            });
        });

        // The complement: the SAME name rendered through the framework-priority
        // escape hatch resolves to the framework copy, which lacks the app marker.
        // (FrameworkViewProbe flips Engine::$prioritizeFrameworkViews.)
        it('renders the framework copy of the same name when forced', () => {
            cy.request('/FrameworkViewProbe/login').then((res) => {
                expect(res.status).to.eq(200);
                expect(res.body).to.not.include('data-test="login-source"'); // not the app copy
                expect(res.body).to.include("Don't have a account?");        // framework copy
            });
        });
    });

    describe('cross-root composition', () => {
        // App view (app/Views/core/render) extended into a framework layout
        // (layout/min_layout), which itself renders a framework component
        // (<x-zubzet::head/>). Child, parent and component live under different
        // roots yet all resolve through the one ordered finder chain.
        it('an app view extends a framework layout that pulls a framework component', () => {
            cy.request('/RenderProbe/crossRoot').then((res) => {
                expect(res.status).to.eq(200);
                // Framework layout shell.
                expect(res.body).to.match(/<!doctype html>/i);
                expect(res.body).to.include('<title>QA Suite</title>');
                // App view body.
                expect(res.body).to.include('data-test="data">CrossRootBody<');
                // Framework component resolved across roots inside the layout.
                expect(res.body).to.include('_zubzet/asset-proxy/js/jquery.min.js');
            });
        });
    });
});
