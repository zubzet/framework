// Pure helpers in src/Support/Helpers.php. The PHP action runs each helper
// against its probe cases and returns a single bool - cypress only checks
// the response body is true.

describe('Global helpers (src/Support/Helpers.php)', () => {
    const helpers = [
        'makeSlug',
        'uecho',
        'shortenStr',
        'de_strtolower',
        'var_swap',
        'emptyToNull',
        'getCaller',
    ];

    helpers.forEach((fn) => {
        it(`${fn} produces the expected output for all probe cases`, () => {
            cy.request(`/helper/function_${fn}`).then((res) => {
                expect(res.status).to.eq(200);
                expect(res.body.allPassed, JSON.stringify(res.body.results)).to.eq(true);
            });
        });
    });
});
