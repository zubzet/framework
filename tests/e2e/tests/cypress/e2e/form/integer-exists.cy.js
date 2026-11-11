// Coverage for src/Form/Validation/Field.php - exists().
// Same JSON-probe pattern as the role/group/session specs.

describe('Form Field - integer() and exists() rules', () => {
    before(() => {
        cy.dbSeed();
    });

    function postValidate(body) {
        return cy.request({
            method: 'POST',
            url: '/Form/validateIntegerExists',
            form: true,
            body,
        }).then((res) => JSON.parse(res.body));
    }

    it('exists() passes when the value is present in the table', () => {
        postValidate({ ints: '1', role_name: 'fwapi_KnownRole' }).then((out) => {
            expect(out.hasErrors).to.eq(false);
        });
    });

    it('exists() fails when the value is not present in the table', () => {
        postValidate({ ints: '1', role_name: 'NoSuchRole_NotSeeded' }).then((out) => {
            expect(out.hasErrors).to.eq(true);
        });
    });
});
