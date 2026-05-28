// Coverage for the multi-select field type in Z.js. The view at
// Views/form/validationMultiSelect.php renders three fields covering
// the three food shapes (values only, text only, value+text), and the
// controller persists the value+text one to model_test_insert.value_json.

describe('Form Multi Select', () => {
    before(() => {
        cy.dbSeed();
    });

    beforeEach(() => {
        cy.visit('/Form/validationMultiSelect');
    });

    it('renders one option per food entry (placeholder excluded)', () => {
        cy.form('field_multi_select_values_only').find('option').should('have.length', 4);
        cy.form('field_multi_select_text_only').find('option').should('have.length', 4);
        cy.form('field_multi_select').find('option').should('have.length', 4);
    });

    describe('Display behavior', () => {
        it('Values only: option text falls back to the value', () => {
            cy.form('field_multi_select_values_only').find('option')
                .eq(1).should('have.value', 'one').and('contain.text', 'one');

            cy.form('field_multi_select_values_only').select('two');
            cy.form('field_multi_select_values_only')
                .parent().find('[data-value="two"]').should('contain.text', 'two');
        });

        it('Text only: text is used as the value', () => {
            cy.form('field_multi_select_text_only').find('option')
                .eq(1).should('have.value', 'One').and('contain.text', 'One');

            cy.form('field_multi_select_text_only').select('One');
            cy.form('field_multi_select_text_only')
                .parent().find('[data-value="One"]').should('contain.text', 'One');
        });

        it('Value + text: badge shows text, value stays separate', () => {
            cy.form('field_multi_select').find('option')
                .eq(1).should('have.value', 'one').and('contain.text', 'One');

            cy.form('field_multi_select').select('one');
            cy.form('field_multi_select')
                .parent().find('[data-value="one"]').should('contain.text', 'One');
        });
    });

    describe('Selection management', () => {
        it('Selecting an option resets the dropdown to placeholder', () => {
            cy.form('field_multi_select').select('one');
            cy.form('field_multi_select').should('have.value', '');
        });

        it('Selecting the same option twice does not add a duplicate badge', () => {
            cy.form('field_multi_select').select('one');
            cy.form('field_multi_select').select('one');
            cy.form('field_multi_select')
                .parent().find('[data-value="one"]').should('have.length', 1);
        });

        it('Clicking a badge unselects that value', () => {
            cy.form('field_multi_select').select('one');
            cy.form('field_multi_select').select('two');
            cy.form('field_multi_select')
                .parent().find('[data-value]').should('have.length', 2);

            cy.form('field_multi_select')
                .parent().find('[data-value="one"]').click();
            cy.form('field_multi_select')
                .parent().find('[data-value="one"]').should('not.exist');
            cy.form('field_multi_select')
                .parent().find('[data-value="two"]').should('exist');
        });

        it('Selected options disappear from the dropdown and return on unselect', () => {
            cy.form('field_multi_select').find('option:not([hidden])').should('have.length', 4);

            cy.form('field_multi_select').select('one');
            cy.form('field_multi_select').find('option:not([hidden])').should('have.length', 3);
            cy.form('field_multi_select').find('option[value="one"]').should('have.attr', 'hidden');

            cy.form('field_multi_select').select('two');
            cy.form('field_multi_select').find('option:not([hidden])').should('have.length', 2);

            cy.form('field_multi_select')
                .parent().find('[data-value="one"]').click();
            cy.form('field_multi_select').find('option:not([hidden])').should('have.length', 3);
            cy.form('field_multi_select').find('option[value="one"]').should('not.have.attr', 'hidden');
        });

        it('form.reset() also restores every option back into the dropdown', () => {
            cy.form('field_multi_select').select('one');
            cy.form('field_multi_select').select('two');
            cy.form('field_multi_select').find('option:not([hidden])').should('have.length', 2);

            cy.window().then((win) => {
                win.form.fields.field_multi_select.reset();
            });

            cy.form('field_multi_select').find('option:not([hidden])').should('have.length', 4);
        });

        it('form.reset() clears all selected badges', () => {
            cy.form('field_multi_select').select('one');
            cy.form('field_multi_select').select('two');

            cy.window().then((win) => {
                win.form.reset();
            });

            cy.form('field_multi_select')
                .parent().find('[data-value]').should('have.length', 0);
        });
    });

    describe('Value getter / setter', () => {
        it('value getter returns the selected values as an array', () => {
            cy.form('field_multi_select').select('one');
            cy.form('field_multi_select').select('three');

            cy.window().then((win) => {
                expect(win.form.fields.field_multi_select.value)
                    .to.deep.eq(['one', 'three']);
            });
        });

        it('value getter returns an empty array when no items are selected', () => {
            cy.window().then((win) => {
                expect(win.form.fields.field_multi_select.value).to.deep.eq([]);
            });
        });

        it('value setter (array) populates badges using the food map for text', () => {
            cy.window().then((win) => {
                win.form.fields.field_multi_select.value = ['two', 'three'];
            });

            cy.form('field_multi_select')
                .parent().find('[data-value="two"]').should('contain.text', 'Two');
            cy.form('field_multi_select')
                .parent().find('[data-value="three"]').should('contain.text', 'Three');
        });

        it('value setter ignores non-array input (e.g. JSON string)', () => {
            cy.form('field_multi_select').select('one');

            cy.window().then((win) => {
                win.form.fields.field_multi_select.value = '["two","three"]';
            });

            cy.form('field_multi_select')
                .parent().find('[data-value]').should('have.length', 0);
        });
    });

    describe('Submission', () => {
        it('Posts each multi-select as a native PHP array (name[]=value)', () => {
            cy.intercept('POST', '/Form/validationMultiSelect').as('submit');

            cy.form('field_multi_select_values_only').select('one');
            cy.form('field_multi_select_text_only').select('Two');
            cy.form('field_multi_select').select('three');
            // Required field must be filled or the whole submission rejects.
            cy.form('field_multi_select_required').select('one');

            cy.query('form').find('button').click();

            cy.wait('@submit').then((interception) => {
                const body = interception.response.body;
                const json = typeof body === 'string' ? JSON.parse(body) : body;
                expect(json.result).to.eq('success');
                expect(json.values_only).to.deep.eq(['one']);
                expect(json.text_only).to.deep.eq(['Two']);
                expect(json.both).to.deep.eq(['three']);
                expect(json.required).to.deep.eq(['one']);
            });
        });

        it('Posts multiple selections as a multi-element array', () => {
            cy.intercept('POST', '/Form/validationMultiSelect').as('submit');

            cy.form('field_multi_select').select('one');
            cy.form('field_multi_select').select('three');
            cy.form('field_multi_select_required').select('two');

            cy.query('form').find('button').click();

            cy.wait('@submit').then((interception) => {
                const body = interception.response.body;
                const json = typeof body === 'string' ? JSON.parse(body) : body;
                expect(json.result).to.eq('success');
                expect(json.both).to.deep.eq(['one', 'three']);
            });
        });

        it('Persists the value+text selection to value_json via insertDatabase', () => {
            cy.form('field_multi_select').select('one');
            cy.form('field_multi_select').select('two');
            cy.form('field_multi_select_required').select('one');

            cy.query('form').find('button').click();
            cy.query('form').contains('Saved!');
        });

        it('Omits empty optional multi-selects from the submission entirely', () => {
            cy.intercept('POST', '/Form/validationMultiSelect').as('submit');

            cy.form('field_multi_select_required').select('one');
            cy.query('form').find('button').click();

            cy.wait('@submit').then((interception) => {
                const body = interception.response.body;
                const json = typeof body === 'string' ? JSON.parse(body) : body;
                expect(json.result).to.eq('success');
                // Empty multi-selects aren't in $_POST at all -> getPost() -> null.
                expect(json.values_only).to.eq(null);
                expect(json.text_only).to.eq(null);
                expect(json.both).to.eq(null);
                expect(json.required).to.deep.eq(['one']);
            });
        });
    });

    describe('Required', () => {
        it('Renders a red asterisk on the required field label', () => {
            cy.form('field_multi_select_required')
                .parents('.col').find('label .text-danger').should('contain.text', '*');
        });

        it('Submitting with the required field empty returns a required form error', () => {
            cy.intercept('POST', '/Form/validationMultiSelect').as('submit');

            cy.query('form').find('button').click();

            cy.wait('@submit').then((interception) => {
                const body = interception.response.body;
                const json = typeof body === 'string' ? JSON.parse(body) : body;
                expect(json.result).to.eq('formErrors');
                expect(json.formErrors).to.satisfy((errs) =>
                    errs.some((e) =>
                        e.name === 'field_multi_select_required' && e.type === 'required'
                    )
                );
            });
        });

        it('Filling the required field clears the error and the submission succeeds', () => {
            cy.form('field_multi_select_required').select('two');

            cy.query('form').find('button').click();
            cy.query('form').contains('Saved!');
        });

        it('Removing the only selected badge re-empties a required multi-select', () => {
            cy.intercept('POST', '/Form/validationMultiSelect').as('submit');

            cy.form('field_multi_select_required').select('one');
            cy.form('field_multi_select_required')
                .parent().find('[data-value="one"]').click();

            cy.query('form').find('button').click();

            cy.wait('@submit').then((interception) => {
                const body = interception.response.body;
                const json = typeof body === 'string' ? JSON.parse(body) : body;
                expect(json.result).to.eq('formErrors');
            });
        });
    });

    describe('Placeholder', () => {
        it('Uses the placeholder text as the first option (replaces "---")', () => {
            cy.form('field_multi_select_placeholder')
                .find('option').eq(0)
                .should('have.value', '').and('contain.text', 'Pick one...');
        });

        it('Falls back to "---" when no placeholder is given', () => {
            cy.form('field_multi_select')
                .find('option').eq(0)
                .should('have.value', '').and('contain.text', '---');
        });
    });

    describe('Default value', () => {
        it('default: [...] pre-fills the badges on first render', () => {
            cy.form('field_multi_select_default')
                .parent().find('[data-value]').should('have.length', 1);
            cy.form('field_multi_select_default')
                .parent().find('[data-value="two"]').should('contain.text', 'Two');
        });

        it('reset() restores the default selection (not just an empty list)', () => {
            cy.form('field_multi_select_default').select('one');
            cy.form('field_multi_select_default')
                .parent().find('[data-value]').should('have.length', 2);

            cy.window().then((win) => {
                win.form.fields.field_multi_select_default.reset();
            });

            cy.form('field_multi_select_default')
                .parent().find('[data-value]').should('have.length', 1);
            cy.form('field_multi_select_default')
                .parent().find('[data-value="two"]').should('exist');
        });

        it('reset() also re-hides the default option from the dropdown', () => {
            // Default ["two"] should be hidden in the dropdown on load.
            cy.form('field_multi_select_default')
                .find('option[value="two"]').should('have.attr', 'hidden');

            cy.form('field_multi_select_default')
                .parent().find('[data-value="two"]').click();
            cy.form('field_multi_select_default')
                .find('option[value="two"]').should('not.have.attr', 'hidden');

            cy.window().then((win) => {
                win.form.fields.field_multi_select_default.reset();
            });

            cy.form('field_multi_select_default')
                .find('option[value="two"]').should('have.attr', 'hidden');
        });
    });

    describe('Optgroups', () => {
        it('Builds an <optgroup> for each food optgroup entry', () => {
            cy.form('field_multi_select_optgroup').find('optgroup').should('have.length', 2);
            cy.form('field_multi_select_optgroup')
                .find('optgroup').eq(0).should('have.attr', 'label', 'Numbers');
            cy.form('field_multi_select_optgroup')
                .find('optgroup').eq(1).should('have.attr', 'label', 'Letters');
        });

        it('Groups subsequent option entries under the preceding optgroup', () => {
            cy.form('field_multi_select_optgroup')
                .find('optgroup[label="Numbers"] option').should('have.length', 2);
            cy.form('field_multi_select_optgroup')
                .find('optgroup[label="Letters"] option').should('have.length', 2);
        });

        it('Selection still works for grouped options (badge shows the option text)', () => {
            cy.form('field_multi_select_optgroup').select('a');
            cy.form('field_multi_select_optgroup')
                .parent().find('[data-value="a"]').should('contain.text', 'Alpha');
        });

        it('Hides the optgroup once every option under it has been picked', () => {
            cy.form('field_multi_select_optgroup').select('one');
            cy.form('field_multi_select_optgroup')
                .find('optgroup[label="Numbers"]').should('not.have.attr', 'hidden');

            cy.form('field_multi_select_optgroup').select('two');
            cy.form('field_multi_select_optgroup')
                .find('optgroup[label="Numbers"]').should('have.attr', 'hidden');

            // The other group is untouched.
            cy.form('field_multi_select_optgroup')
                .find('optgroup[label="Letters"]').should('not.have.attr', 'hidden');
        });

        it('Restores the optgroup when an option underneath is unselected again', () => {
            cy.form('field_multi_select_optgroup').select('one');
            cy.form('field_multi_select_optgroup').select('two');
            cy.form('field_multi_select_optgroup')
                .find('optgroup[label="Numbers"]').should('have.attr', 'hidden');

            cy.form('field_multi_select_optgroup')
                .parent().find('[data-value="one"]').click();
            cy.form('field_multi_select_optgroup')
                .find('optgroup[label="Numbers"]').should('not.have.attr', 'hidden');
        });
    });

    describe('Disabled lock (PR 143 compat)', () => {
        it('A disabled multi-select ignores badge-click removals', () => {
            cy.form('field_multi_select').select('one');
            cy.form('field_multi_select').select('two');

            cy.window().then((win) => {
                // PR 143 introduces field.isDisabled(); stub it until merged.
                win.form.fields.field_multi_select.isDisabled = () => true;
            });

            cy.form('field_multi_select')
                .parent().find('[data-value="one"]').click();

            cy.form('field_multi_select')
                .parent().find('[data-value="one"]').should('exist');
            cy.form('field_multi_select')
                .parent().find('[data-value]').should('have.length', 2);
        });
    });

    describe('Pre-loaded value', () => {
        it('value: [...] populates badges on page load with text from food map', () => {
            cy.form('field_multi_select_preloaded')
                .parent().find('[data-value]').should('have.length', 2);
            cy.form('field_multi_select_preloaded')
                .parent().find('[data-value="one"]').should('contain.text', 'One');
            cy.form('field_multi_select_preloaded')
                .parent().find('[data-value="three"]').should('contain.text', 'Three');
        });

        it('value: <?= json_encode(...) ?> populates badges on page load', () => {
            cy.form('field_multi_select_preloaded_json')
                .parent().find('[data-value]').should('have.length', 1);
            cy.form('field_multi_select_preloaded_json')
                .parent().find('[data-value="two"]').should('contain.text', 'Two');
        });

        it('Pre-loaded values are reflected in the field value getter', () => {
            cy.window().then((win) => {
                expect(win.form.fields.field_multi_select_preloaded.value)
                    .to.deep.eq(['one', 'three']);
                expect(win.form.fields.field_multi_select_preloaded_json.value)
                    .to.deep.eq(['two']);
            });
        });

        it('Pre-loaded values can still be unselected by clicking the badge', () => {
            cy.form('field_multi_select_preloaded')
                .parent().find('[data-value="one"]').click();
            cy.form('field_multi_select_preloaded')
                .parent().find('[data-value]').should('have.length', 1);
            cy.form('field_multi_select_preloaded')
                .parent().find('[data-value="three"]').should('exist');
        });
    });
});
