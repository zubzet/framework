// Coverage for the array-aware variants of length / regex / exists and
// the new ->in() allow-list rule.
//
// length: counts items when the value is an array.
// regex:  applied per item; field fails if any item fails.
// exists: original (table, field) check still works on scalar AND array
//         field values (per-item DB check).
// in:     new rule, value(s) must appear in an in-memory allow-list.
//         Per-item for arrays. Works for both plain select and
//         multi-select. Guards against tampered POST payloads.

describe('Form List-aware rules', () => {
    before(() => {
        cy.dbSeed();
    });

    beforeEach(() => {
        cy.visit('/Form/validationListRules');
    });

    describe('length() on arrays counts items', () => {
        it('Submitting one selection satisfies length(1, 2)', () => {
            cy.intercept('POST', '/Form/validationListRules').as('submit');

            cy.form('field_list_length').select('one');
            cy.form('field_list_in_array').select('one');
            cy.form('field_list_in_select').select('one');
            cy.query('form').find('button').click();

            cy.wait('@submit').then((interception) => {
                const json = JSON.parse(interception.response.body);
                expect(json.result).to.eq('success');
            });
        });

        it('Submitting three selections trips length(1, 2)', () => {
            cy.intercept('POST', '/Form/validationListRules').as('submit');

            cy.form('field_list_length').select('one');
            cy.form('field_list_length').select('two');
            cy.form('field_list_length').select('three');
            cy.form('field_list_in_array').select('one');
            cy.form('field_list_in_select').select('one');
            cy.query('form').find('button').click();

            cy.wait('@submit').then((interception) => {
                const json = JSON.parse(interception.response.body);
                expect(json.result).to.eq('formErrors');
                expect(json.formErrors).to.satisfy((errs) =>
                    errs.some((e) => e.name === 'field_list_length' && e.type === 'length')
                );
            });
        });
    });

    describe('regex() on arrays runs per item', () => {
        it('All lowercase items pass the per-item regex', () => {
            cy.intercept('POST', '/Form/validationListRules').as('submit');

            cy.form('field_list_regex').select('abc');
            cy.form('field_list_regex').select('def');
            cy.form('field_list_in_array').select('one');
            cy.form('field_list_in_select').select('one');
            cy.query('form').find('button').click();

            cy.wait('@submit').then((interception) => {
                const json = JSON.parse(interception.response.body);
                expect(json.result).to.eq('success');
            });
        });

        it('A single mixed-case item is enough to fail the per-item regex', () => {
            cy.intercept('POST', '/Form/validationListRules').as('submit');

            cy.form('field_list_regex').select('abc');
            cy.form('field_list_regex').select('XYZ');
            cy.form('field_list_in_array').select('one');
            cy.form('field_list_in_select').select('one');
            cy.query('form').find('button').click();

            cy.wait('@submit').then((interception) => {
                const json = JSON.parse(interception.response.body);
                expect(json.result).to.eq('formErrors');
                expect(json.formErrors).to.satisfy((errs) =>
                    errs.some((e) => e.name === 'field_list_regex' && e.type === 'regex')
                );
            });
        });
    });

    describe('in() on multi-select', () => {
        it('Picking values from the allow-list succeeds', () => {
            cy.intercept('POST', '/Form/validationListRules').as('submit');

            cy.form('field_list_in_array').select('one');
            cy.form('field_list_in_array').select('two');
            cy.form('field_list_in_select').select('one');
            cy.query('form').find('button').click();

            cy.wait('@submit').then((interception) => {
                const json = JSON.parse(interception.response.body);
                expect(json.result).to.eq('success');
            });
        });

        it('Picking a value outside the allow-list fails', () => {
            cy.intercept('POST', '/Form/validationListRules').as('submit');

            // "three" is in the dropdown but not in the allow-list ["one","two"].
            cy.form('field_list_in_array').select('three');
            cy.form('field_list_in_select').select('one');
            cy.query('form').find('button').click();

            cy.wait('@submit').then((interception) => {
                const json = JSON.parse(interception.response.body);
                expect(json.result).to.eq('formErrors');
                expect(json.formErrors).to.satisfy((errs) =>
                    errs.some((e) => e.name === 'field_list_in_array' && e.type === 'in')
                );
            });
        });

        it('Mixing one allowed and one disallowed pick still fails (per-item check)', () => {
            cy.intercept('POST', '/Form/validationListRules').as('submit');

            cy.form('field_list_in_array').select('one');
            cy.form('field_list_in_array').select('three');
            cy.form('field_list_in_select').select('one');
            cy.query('form').find('button').click();

            cy.wait('@submit').then((interception) => {
                const json = JSON.parse(interception.response.body);
                expect(json.result).to.eq('formErrors');
                expect(json.formErrors).to.satisfy((errs) =>
                    errs.some((e) => e.name === 'field_list_in_array' && e.type === 'in')
                );
            });
        });
    });

    describe('exists() per item on multi-select', () => {
        it('Picking only DB-existing role names succeeds', () => {
            cy.intercept('POST', '/Form/validationListRules').as('submit');

            cy.form('field_list_exists_multi').select('fwapi_KnownRole');
            cy.form('field_list_exists_multi').select('fwapi_RoleState');
            cy.form('field_list_in_array').select('one');
            cy.form('field_list_in_select').select('one');
            cy.query('form').find('button').click();

            cy.wait('@submit').then((interception) => {
                const json = JSON.parse(interception.response.body);
                expect(json.result).to.eq('success');
            });
        });

        it('Picking a non-existing role name fails', () => {
            cy.intercept('POST', '/Form/validationListRules').as('submit');

            cy.form('field_list_exists_multi').select('NoSuchRole_NotSeeded');
            cy.form('field_list_in_array').select('one');
            cy.form('field_list_in_select').select('one');
            cy.query('form').find('button').click();

            cy.wait('@submit').then((interception) => {
                const json = JSON.parse(interception.response.body);
                expect(json.result).to.eq('formErrors');
                expect(json.formErrors).to.satisfy((errs) =>
                    errs.some((e) => e.name === 'field_list_exists_multi' && e.type === 'exist')
                );
            });
        });

        it('Mixing one existing and one non-existing role fails (per-item check)', () => {
            cy.intercept('POST', '/Form/validationListRules').as('submit');

            cy.form('field_list_exists_multi').select('fwapi_KnownRole');
            cy.form('field_list_exists_multi').select('NoSuchRole_NotSeeded');
            cy.form('field_list_in_array').select('one');
            cy.form('field_list_in_select').select('one');
            cy.query('form').find('button').click();

            cy.wait('@submit').then((interception) => {
                const json = JSON.parse(interception.response.body);
                expect(json.result).to.eq('formErrors');
                expect(json.formErrors).to.satisfy((errs) =>
                    errs.some((e) => e.name === 'field_list_exists_multi' && e.type === 'exist')
                );
            });
        });
    });

    describe('in() on plain select', () => {
        it('Picking an allowed value succeeds', () => {
            cy.intercept('POST', '/Form/validationListRules').as('submit');

            cy.form('field_list_in_array').select('one');
            cy.form('field_list_in_select').select('two');
            cy.query('form').find('button').click();

            cy.wait('@submit').then((interception) => {
                const json = JSON.parse(interception.response.body);
                expect(json.result).to.eq('success');
            });
        });

        it('Picking a value outside the allow-list fails', () => {
            cy.intercept('POST', '/Form/validationListRules').as('submit');

            cy.form('field_list_in_array').select('one');
            cy.form('field_list_in_select').select('three');
            cy.query('form').find('button').click();

            cy.wait('@submit').then((interception) => {
                const json = JSON.parse(interception.response.body);
                expect(json.result).to.eq('formErrors');
                expect(json.formErrors).to.satisfy((errs) =>
                    errs.some((e) => e.name === 'field_list_in_select' && e.type === 'in')
                );
            });
        });
    });
});
