// Coverage for the checkbox field type: Bootstrap form-check layout
// (box left, label right, label-click toggles), checked-based value
// semantics, and submission ("1" when checked, omitted when not — so
// ->required() catches an unticked box).

describe('Form Checkbox', () => {
    before(() => {
        cy.dbSeed();
    });

    beforeEach(() => {
        cy.visit('/Form/validationCheckbox');
    });

    describe('Rendering', () => {
        it('renders the box before its label inside a form-check', () => {
            cy.get('input[name=agree]')
                .should('have.class', 'form-check-input')
                .parent().should('have.class', 'form-check');
            // The label follows the input and is a form-check-label.
            cy.get('input[name=agree]')
                .next('label')
                .should('have.class', 'form-check-label')
                .and('contain.text', 'I agree');
        });
    });

    describe('Label click toggles the box', () => {
        it('clicking the label checks and unchecks the box', () => {
            cy.get('input[name=agree]').should('not.be.checked');

            cy.contains('label', 'I agree').click();
            cy.get('input[name=agree]').should('be.checked');

            cy.contains('label', 'I agree').click();
            cy.get('input[name=agree]').should('not.be.checked');
        });
    });

    describe('value reflects the checked state', () => {
        it('getter returns a boolean', () => {
            cy.window().then((win) => {
                expect(win.form.fields.agree.value).to.eq(false);
            });
            cy.get('input[name=agree]').check();
            cy.window().then((win) => {
                expect(win.form.fields.agree.value).to.eq(true);
            });
        });

        it('setter checks/unchecks (accepts true, "1", false)', () => {
            cy.window().then((win) => {
                win.form.fields.agree.value = true;
            });
            cy.get('input[name=agree]').should('be.checked');

            cy.window().then((win) => {
                win.form.fields.agree.value = false;
            });
            cy.get('input[name=agree]').should('not.be.checked');

            cy.window().then((win) => {
                win.form.fields.agree.value = '1';
            });
            cy.get('input[name=agree]').should('be.checked');
        });

        it('default: true starts the box checked', () => {
            cy.get('input[name=subscribed]').should('be.checked');
            cy.window().then((win) => {
                expect(win.form.fields.subscribed.value).to.eq(true);
            });
        });

        it('getValues returns booleans for checkboxes', () => {
            cy.get('input[name=agree]').check();
            cy.window().then((win) => {
                const values = win.form.getValues();
                expect(values.agree).to.eq(true);
                expect(values.subscribed).to.eq(true);
                expect(values.terms).to.eq(false);
            });
        });
    });

    describe('reset', () => {
        it('returns each box to its default (unchecked, or default:true)', () => {
            cy.get('input[name=agree]').check();
            cy.get('input[name=subscribed]').uncheck();

            cy.window().then((win) => win.form.reset());

            cy.get('input[name=agree]').should('not.be.checked');
            cy.get('input[name=subscribed]').should('be.checked'); // default true
        });
    });

    describe('Submission', () => {
        it('submits "1" when checked and "0" when not (so toggling off persists)', () => {
            cy.intercept('POST', '/Form/validationCheckbox').as('submit');

            cy.get('input[name=agree]').check();
            cy.get('input[name=subscribed]').uncheck(); // default-checked -> now off
            cy.get('input[name=terms]').check();        // satisfy ->checked()
            cy.query('submit-btn').click();

            cy.wait('@submit').then((interception) => {
                const json = JSON.parse(interception.response.body);
                expect(json.result).to.eq('success');
                expect(json.agree).to.eq('1');
                // Unchecked submits "0" (not omitted) so the column updates.
                expect(json.subscribed).to.eq('0');
                expect(json.terms).to.eq('1');
            });
        });
    });

    describe('->checked() rule (must be ticked)', () => {
        it('fails when the box is not ticked', () => {
            cy.intercept('POST', '/Form/validationCheckbox').as('submit');

            // Leave terms unticked.
            cy.get('input[name=agree]').check();
            cy.query('submit-btn').click();

            cy.wait('@submit').then((interception) => {
                const json = JSON.parse(interception.response.body);
                expect(json.result).to.eq('formErrors');
                expect(json.formErrors).to.satisfy((errs) =>
                    errs.some((e) => e.name === 'terms')
                );
            });
        });

        it('passes once the box is ticked', () => {
            cy.intercept('POST', '/Form/validationCheckbox').as('submit');

            cy.get('input[name=terms]').check();
            cy.query('submit-btn').click();

            cy.wait('@submit').then((interception) => {
                const json = JSON.parse(interception.response.body);
                expect(json.result).to.eq('success');
            });
        });
    });
});
