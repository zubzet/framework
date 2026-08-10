// Not-found handling in CanRenderView::render, driven by RenderProbeController.
// When a render throws (view missing, or its layout missing/broken), the render
// is caught and re-rendered as the framework 500 page in a GUARANTEED framework
// layout (layout/min_layout). Because the fallback never touches the caller's
// layout, it survives even when that layout is the thing that is missing.
//
// Stateless renders: no cy.dbSeed().

describe('View not-found fallback', () => {

    // Framework 500 page markers (IncludedComponents/views/500.blade.php),
    // wrapped in the guaranteed layout/min_layout (its <title> is the default
    // pageName "QA Suite").
    const expectFiveHundred = (res) => {
        expect(res.status).to.eq(200);
        expect(res.body).to.include('Oops! This page seems to be broken');
        expect(res.body).to.include('Sorry, we messed up!');
        expect(res.body).to.include('Take me back to my Website');
        // Rendered in the guaranteed framework layout, not the caller's.
        expect(res.body).to.match(/<!doctype html>/i);
        expect(res.body).to.include('<title>QA Suite</title>');
    };

    it('renders the 500 page when the view is missing', () => {
        cy.request({ url: '/RenderProbe/missingView', failOnStatusCode: false })
            .then(expectFiveHundred);
    });

    it('renders the 500 page when the view exists but its layout is missing', () => {
        cy.request({ url: '/RenderProbe/validViewMissingLayout', failOnStatusCode: false })
            .then(expectFiveHundred);
    });

    // Regression for the double fault: a missing view whose fallback layout is
    // also missing used to throw again uncaught (hard 500). The guaranteed-layout
    // fallback now recovers it.
    it('renders the 500 page when BOTH the view and its layout are missing', () => {
        cy.request({ url: '/RenderProbe/missingViewAndLayout', failOnStatusCode: false })
            .then(expectFiveHundred);
    });
});
