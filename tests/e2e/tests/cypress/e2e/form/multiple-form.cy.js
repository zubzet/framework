describe('Multiple Forms', () => {
    before(() => {
        cy.dbSeed();
    });

    describe('two forms in one view', () => {
        beforeEach(() => {
            cy.intercept('POST', '/MultipleForm/multiple').as('submit');
            cy.visit('/MultipleForm/multiple');
        });

        it('routes the billing submit to the billing branch only', () => {
            cy.form('billing_value').type('invoice address');
            cy.form('shipping_value').type('delivery address');
            cy.query('billing-form').find('button').click();

            cy.wait('@submit').then((interception) => {
                const json = JSON.parse(interception.response.body);
                expect(json.result).to.eq('success');
                expect(json.form).to.eq('billing');
                expect(json.billing_value).to.eq('invoice address');
                expect(json.shipping_value).to.be.null;
            });

            cy.query('billing-form').contains('Saved!');
            cy.query('shipping-form').should('not.contain.text', 'Saved!');
        });

        it('routes the shipping submit to the shipping branch only', () => {
            cy.form('billing_value').type('invoice address');
            cy.form('shipping_value').type('delivery address');
            cy.query('shipping-form').find('button').click();

            cy.wait('@submit').then((interception) => {
                const json = JSON.parse(interception.response.body);
                expect(json.result).to.eq('success');
                expect(json.form).to.eq('shipping');
                expect(json.shipping_value).to.eq('delivery address');
                expect(json.billing_value).to.be.null;
            });

            cy.query('shipping-form').contains('Saved!');
            cy.query('billing-form').should('not.contain.text', 'Saved!');
        });

        it('shows validation errors only on the submitted form', () => {
            cy.query('billing-form').find('button').click();

            cy.wait('@submit').then((interception) => {
                const json = JSON.parse(interception.response.body);
                expect(json.result).to.eq('formErrors');
            });

            cy.form('billing_value').parent().contains('Please fill in this field');
            cy.query('shipping-form').should('not.contain.text', 'Please fill in this field');
        });
    });

    describe('formAction sent by Z.Forms', () => {
        it('uses the explicit name option, overriding the dom id', () => {
            cy.intercept('POST', '/MultipleForm/named').as('submit');
            cy.visit('/MultipleForm/named');

            cy.form('named_value').type('named payload');
            cy.query('form').find('button').click();

            cy.wait('@submit').then((interception) => {
                const json = JSON.parse(interception.response.body);
                expect(json.result).to.eq('success');
                expect(json.formAction).to.eq('named-action');
            });
            cy.query('form').contains('Saved!');
        });

        it('falls back to the dom id when no name is configured', () => {
            cy.intercept('POST', '/MultipleForm/domFallback').as('submit');
            cy.visit('/MultipleForm/domFallback');

            cy.form('fallback_value').type('fallback payload');
            cy.query('form').find('button').click();

            cy.wait('@submit').then((interception) => {
                const json = JSON.parse(interception.response.body);
                expect(json.result).to.eq('success');
                expect(json.formAction).to.eq('fallback-form');
            });
            cy.query('form').contains('Saved!');
        });

        it('sends no formAction when neither name nor dom is set', () => {
            cy.intercept('POST', '/MultipleForm/unnamed').as('submit');
            cy.visit('/MultipleForm/unnamed');

            cy.form('unnamed_value').type('legacy payload');
            cy.query('form').find('button').click();

            cy.wait('@submit').then((interception) => {
                const json = JSON.parse(interception.response.body);
                expect(json.result).to.eq('success');
                expect(json.formAction).to.be.null;
            });
            cy.query('form').contains('Saved!');
        });
    });

    describe('hasFormData() matching rules', () => {
        const probe = (body) => cy.zRequest({
            method: 'POST',
            url: '/MultipleForm/probe',
            form: true,
            body,
        });

        it('matches any form submission when called without an action', () => {
            probe({ isFormData: 1 }).then((res) => {
                expect(res.body).to.deep.eq({ any: true, named: false });
            });
        });

        it('matches a named action only on the exact formAction', () => {
            probe({ isFormData: 1, formAction: 'target-action' }).then((res) => {
                expect(res.body).to.deep.eq({ any: true, named: true });
            });
        });

        it('does not match a named action on a different formAction', () => {
            probe({ isFormData: 1, formAction: 'other-action' }).then((res) => {
                expect(res.body).to.deep.eq({ any: true, named: false });
            });
        });

        it('is no form data without the isFormData flag, even with a formAction', () => {
            probe({ formAction: 'target-action' }).then((res) => {
                expect(res.body).to.deep.eq({ any: false, named: false });
            });
        });
    });
});
