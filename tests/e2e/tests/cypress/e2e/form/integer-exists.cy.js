// Coverage for src/Form/Validation/Field.php — integer() and exists().
// Same JSON-probe pattern as the role/group/session specs.

describe('Form Field — integer() and exists() rules', () => {
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

    it('integer() accepts valid integers', () => {
        const cases = ['42', '-5', '999999'];
        cases.forEach((ints) => {
            // role_name uses a value that does exist so we isolate the
            // integer-rule outcome.
            postValidate({ ints, role_name: 'fwapi_KnownRole' }).then((out) => {
                expect(out.hasErrors, `int ${ints}`).to.eq(false);
            });
        });
    });

    it('integer() rejects non-integer input', () => {
        // Empty string is intentionally NOT in this list: the rule wrapper
        // skips per-rule checks when the value is missing/empty (and the
        // field isn't ->required()), so empty input is accepted by integer().
        const cases = ['abc', '1.5', 'not-a-number'];
        cases.forEach((ints) => {
            postValidate({ ints, role_name: 'fwapi_KnownRole' }).then((out) => {
                expect(out.hasErrors, `int ${JSON.stringify(ints)}`).to.eq(true);
            });
        });
    });

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

    it('integer() accepts "0"', () => {
        postValidate({ ints: '0', role_name: 'fwapi_KnownRole' }).then((out) => {
            expect(out.hasErrors).to.eq(false);
        });
    });
});
