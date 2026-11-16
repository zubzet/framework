// The $opt contract that CanRenderView::legacyOptExpansion hands every view,
// driven by RenderProbeController + Views/renderprobe/opt. Pins that caller data
// is visible both top-level ($a) and inside $opt; the framework expansions, the
// title fallback and generateResourceLink are unit tested.
//
// Stateless render: no cy.dbSeed().

describe('Render $opt contract', () => {

    describe('data shape', () => {
        it('exposes caller data both top-level ($a) and inside $opt', () => {
            cy.request('/RenderProbe/opt').then((res) => {
                expect(res.status).to.eq(200);
                expect(res.body).to.include('data-test="top-a">alpha<'); // top-level $a
                expect(res.body).to.include('data-test="opt-a">alpha<');  // $opt["a"]
            });
        });
    });
});
