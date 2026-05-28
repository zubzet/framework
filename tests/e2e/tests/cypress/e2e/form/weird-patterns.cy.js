// Regression coverage for the "real world" ways views bend the form API.
// These reproduce patterns seen across production projects (genericized):
// conditional wrapper visibility via closest('.form-group'), cascading
// computed values, appending custom DOM into a field's group, a hand-rolled
// hidden-JSON + cards multi-select, and jQuery submit-button manipulation.
// If a Z.js change breaks any of these, these tests catch it.

describe('Form weird-but-real patterns', () => {
    before(() => {
        cy.dbSeed();
    });

    beforeEach(() => {
        cy.visit('/Form/weirdPatterns');
    });

    describe("closest('.form-group') conditional visibility", () => {
        it('toggles another field group based on a select value', () => {
            // Hidden initially (the view called .hide() on the wrapper).
            cy.form('other_detail').should('not.be.visible');

            cy.form('source').select('other');
            cy.form('other_detail').should('be.visible');

            cy.form('source').select('a');
            cy.form('other_detail').should('not.be.visible');
        });

        it('hiding a field group does not hide the rest of the form', () => {
            // The submit button and other fields stay visible — proving the
            // closest('.form-group') match is the field's own row, not the
            // whole inputSpace.
            cy.form('source').should('be.visible');
            cy.query('submit-btn').should('be.visible');
        });
    });

    describe('cascading computed value', () => {
        it("one field's input listener writes another field", () => {
            cy.form('year').type('3');
            cy.form('doubled').should('have.value', '6');

            cy.form('year').clear().type('10');
            cy.form('doubled').should('have.value', '20');
        });
    });

    describe("custom element appended into a field's group", () => {
        it('the appended button lives inside the form and its listener fires', () => {
            // It was appended into the anchor field's .form-group.
            cy.query('appended-btn').should('exist').and('be.visible');

            cy.query('appended-btn').click().click().click();
            cy.form('clicks').should('have.value', '3');
        });
    });

    describe('hand-rolled multi-select (hidden JSON + cards)', () => {
        it('cards toggle values in the hidden JSON field', () => {
            cy.form('selection').should('have.value', '[]');

            cy.query('card-x').click();
            cy.form('selection').should('have.value', '["x"]');

            cy.query('card-y').click();
            cy.form('selection').should('have.value', '["x","y"]');

            // Toggling x off removes just x.
            cy.query('card-x').click();
            cy.form('selection').should('have.value', '["y"]');
        });

        it('marks selected cards (class toggle on the rendered element)', () => {
            cy.query('card-z').click().should('have.class', 'border-success');
            cy.query('card-z').click().should('not.have.class', 'border-success');
        });
    });

    describe('jQuery submit-button manipulation', () => {
        it('the submit button is a real element that jQuery can disable and hide', () => {
            cy.window().then((win) => {
                win.$(win.form.buttonSubmit).attr('disabled', true).html('Working...');
            });
            cy.query('submit-btn').should('be.disabled').and('contain.text', 'Working...');

            cy.window().then((win) => {
                win.$(win.form.buttonSubmit).removeAttr('disabled').hide();
            });
            cy.query('submit-btn').should('not.be.visible');
        });
    });

    describe('bulk write via form.fields[...].input.value', () => {
        it('writing input.value directly is reflected in the fields and getValues', () => {
            cy.window().then((win) => win.bulkWrite());

            cy.form('anchor').should('have.value', 'bulk');
            cy.form('year').should('have.value', '5');

            cy.window().then((win) => {
                const values = win.form.getValues();
                expect(values.anchor).to.eq('bulk');
                expect(values.year).to.eq('5');
            });
        });
    });
});
