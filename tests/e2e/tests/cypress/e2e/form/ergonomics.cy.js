// Coverage for the form-ergonomics API (enable/disable, show/hide,
// getValues/setValues, auto-disable-on-submit) plus the _updateLayout
// rebuild guarantees: addCustomHTML/addSeperator content survives a
// rebuild, the row state stays consistent so later createField() calls
// render, and — most importantly — event listeners attached to fields
// keep firing after a rebuild.

describe('Form Ergonomics', () => {
    before(() => {
        cy.dbSeed();
    });

    beforeEach(() => {
        cy.visit('/Form/ergonomics');
    });

    describe('Field disable / enable', () => {
        it('options.disabled starts the field disabled', () => {
            cy.form('field_disabled').should('be.disabled');
            cy.window().then((win) => {
                expect(win.form.fields.field_disabled.isDisabled()).to.eq(true);
            });
        });

        it('field.disable() / enable() toggle the input', () => {
            cy.form('field_a').should('not.be.disabled');

            cy.window().then((win) => win.form.fields.field_a.disable());
            cy.form('field_a').should('be.disabled');

            cy.window().then((win) => {
                expect(win.form.fields.field_a.isDisabled()).to.eq(true);
                win.form.fields.field_a.enable();
            });
            cy.form('field_a').should('not.be.disabled');
        });
    });

    describe('Field show / hide', () => {
        it('options.hidden keeps the field out of the layout', () => {
            cy.form('field_hidden').should('not.exist');
            cy.window().then((win) => {
                expect(win.form.fields.field_hidden.isHidden()).to.eq(true);
            });
        });

        it('hide() removes the field and show() brings it back', () => {
            cy.form('field_a').should('exist');

            cy.window().then((win) => win.form.fields.field_a.hide());
            cy.form('field_a').should('not.exist');

            cy.window().then((win) => win.form.fields.field_a.show());
            cy.form('field_a').should('exist').and('be.visible');
        });

        it('a field hidden from the start can be shown', () => {
            cy.form('field_hidden').should('not.exist');
            cy.window().then((win) => win.form.fields.field_hidden.show());
            cy.form('field_hidden').should('exist').and('be.visible');
        });
    });

    describe('Form disable / enable', () => {
        it('form.disable() disables every field input and the submit button', () => {
            cy.window().then((win) => win.form.disable());

            cy.form('field_a').should('be.disabled');
            cy.form('watched').should('be.disabled');
            cy.form('field_half').should('be.disabled');
            cy.query('submit-btn').should('be.disabled');

            cy.window().then((win) => {
                expect(win.form.isDisabled()).to.eq(true);
                // A field reports disabled because the form is, even though
                // its own flag is false.
                expect(win.form.fields.field_a.isDisabled()).to.eq(true);
            });
        });

        it('form.enable() re-enables fields but preserves a field disabled on its own', () => {
            cy.window().then((win) => {
                win.form.disable();
                win.form.enable();
            });

            // field_a had no individual disable -> back to enabled.
            cy.form('field_a').should('not.be.disabled');
            // field_disabled was disabled on its own -> stays disabled.
            cy.form('field_disabled').should('be.disabled');
        });
    });

    describe('getValues / setValues', () => {
        it('getValues returns a value per (non-CED) field', () => {
            cy.form('field_a').type('hello');
            cy.window().then((win) => {
                const values = win.form.getValues();
                expect(values).to.have.property('field_a', 'hello');
                expect(values).to.have.property('watched');
                expect(values).to.have.property('field_half');
            });
        });

        it('setValues populates the named fields', () => {
            cy.window().then((win) => {
                win.form.setValues({ field_a: 'alpha', field_half: 'beta' });
            });
            cy.form('field_a').should('have.value', 'alpha');
            cy.form('field_half').should('have.value', 'beta');
        });

        it('getValues omits CED fields entirely', () => {
            cy.window().then((win) => {
                const values = win.cedForm.getValues();
                expect(values).to.have.property('ced_text');
                expect(values).to.not.have.property('ced_items');
            });
        });

        it('setValues ignores a CED key without throwing', () => {
            cy.window().then((win) => {
                expect(() =>
                    win.cedForm.setValues({ ced_text: 'x', ced_items: [{ label: 'y' }] })
                ).to.not.throw();
            });
            cy.get('#ced-form').find('input[name=ced_text]').should('have.value', 'x');
        });
    });

    describe('Meta values (non-field keys)', () => {
        it('setValues keeps a non-field key on form.meta and getValues returns it', () => {
            cy.window().then((win) => {
                win.form.setValues({ field_a: 'Ada', id: 42 });
                const values = win.form.getValues();
                expect(values).to.have.property('field_a', 'Ada');
                expect(values).to.have.property('id', 42);
                expect(win.form.meta).to.deep.eq({ id: 42 });
            });
            // The matching field was still populated.
            cy.form('field_a').should('have.value', 'Ada');
        });

        it('meta is client-only — it is not submitted to the backend', () => {
            cy.intercept('POST', '/Form/ergonomics').as('submit');

            cy.window().then((win) => win.form.setValues({ field_a: 'Ada', id: 42 }));
            cy.query('submit-btn').click();

            cy.wait('@submit').then((interception) => {
                // FormData body carries field_a but never the meta-only "id".
                // Match the multipart field-name boundary so "id" doesn't
                // accidentally match inside "field_hidden".
                const body = interception.request.body;
                expect(body).to.contain('name="field_a"');
                expect(body).to.not.contain('name="id"');
            });
        });
    });

    describe('Submit button visibility', () => {
        it('hideSubmit() hides the button, showSubmit() brings it back', () => {
            cy.query('submit-btn').should('be.visible');

            cy.window().then((win) => win.form.hideSubmit());
            cy.query('submit-btn').should('not.be.visible');

            cy.window().then((win) => win.form.showSubmit());
            cy.query('submit-btn').should('be.visible');
        });
    });

    describe('collectOnly (client-only submit)', () => {
        it('submit hands getValues() to saveHook and never POSTs', () => {
            cy.intercept('POST', '/Form/ergonomics').as('liveSubmit');

            cy.get('#live-form').find('input[name=live_text]').type('hi');
            cy.query('live-submit').click();

            // saveHook fired with the collected values.
            cy.query('saved-mirror').should('contain.text', '"live_text":"hi"');

            // No request was made.
            cy.get('@liveSubmit.all').should('have.length', 0);
        });
    });

    describe('inputHook (live value changes)', () => {
        it('fires with getValues() on every keystroke', () => {
            cy.get('#live-form').find('input[name=live_text]').type('ab');
            // Last keystroke leaves the full value in the mirror.
            cy.query('live-mirror').should('contain.text', '"live_text":"ab"');
        });

        it('fires on a select change too', () => {
            cy.get('#live-form').find('select[name=live_select]').select('b');
            cy.query('live-mirror').should('contain.text', '"live_select":"b"');
        });
    });

    describe('Auto-disable during submit', () => {
        it('disables the submit button while the request is in flight, re-enables after', () => {
            cy.intercept('POST', '/Form/ergonomics', (req) => {
                req.reply({ delay: 350, body: JSON.stringify({ result: 'success' }) });
            }).as('submit');

            cy.form('field_a').type('x');
            cy.query('submit-btn').click();

            // In-flight: button disabled.
            cy.query('submit-btn').should('be.disabled');

            cy.wait('@submit');

            // Settled: button enabled again.
            cy.query('submit-btn').should('not.be.disabled');
        });
    });

    describe('_updateLayout preserves non-field content (regression: innerHTML wipe)', () => {
        it('addCustomHTML content survives a layout rebuild triggered by hide()', () => {
            cy.query('custom-marker').should('exist').and('contain.text', 'CUSTOM');
            cy.get('#form hr').should('have.length', 1);

            // Any show/hide rebuilds the whole layout.
            cy.window().then((win) => win.form.fields.field_a.hide());

            // Before the fix, _updateLayout did inputSpace.innerHTML = "" and
            // these would be gone.
            cy.query('custom-marker').should('exist').and('contain.text', 'CUSTOM');
            cy.get('#form hr').should('have.length', 1);
        });

        it('custom content keeps its place across repeated hide/show cycles', () => {
            cy.window().then((win) => {
                win.form.fields.watched.hide();
                win.form.fields.watched.show();
                win.form.fields.field_a.hide();
                win.form.fields.field_a.show();
            });
            cy.query('custom-marker').should('exist');
            cy.get('#form hr').should('have.length', 1);
        });
    });

    describe('_updateLayout keeps row state consistent (regression: stale currentRow)', () => {
        it('a field created after a rebuild is still attached and visible', () => {
            cy.window().then((win) => win.form.fields.field_a.hide());

            cy.window().then((win) => {
                win.form.createField({ name: 'late_field', type: 'text', width: 6 });
            });

            // Before the fix, this.currentRow pointed at a detached row after
            // the rebuild, so the new field's DOM was appended off-document.
            cy.form('late_field').should('exist').and('be.visible');
            // And it really lives inside the rendered form, not limbo.
            cy.get('#form').find('input[name=late_field]').should('have.length', 1);
        });
    });

    describe('Event listeners survive layout rebuilds', () => {
        it('native field.on() listener keeps firing after a sibling hide rebuilds the layout', () => {
            cy.form('watched').type('a');
            cy.query('native-count').should('have.text', '1');

            // Hiding a DIFFERENT field rebuilds the whole layout, re-attaching
            // the watched field's DOM.
            cy.window().then((win) => win.form.fields.field_a.hide());

            cy.form('watched').type('b');
            cy.query('native-count').should('have.text', '2');
        });

        it('jQuery $(field.input).on() listener also keeps firing after a rebuild', () => {
            cy.form('watched').type('a');
            cy.query('jquery-count').should('have.text', '1');

            cy.window().then((win) => win.form.fields.field_a.hide());

            cy.form('watched').type('b');
            cy.query('jquery-count').should('have.text', '2');
        });

        it('listeners survive hiding and re-showing the watched field itself', () => {
            cy.form('watched').type('a');
            cy.query('native-count').should('have.text', '1');
            cy.query('jquery-count').should('have.text', '1');

            cy.window().then((win) => {
                win.form.fields.watched.hide();
                win.form.fields.watched.show();
            });

            cy.form('watched').type('b');
            cy.query('native-count').should('have.text', '2');
            cy.query('jquery-count').should('have.text', '2');
        });
    });

    describe('CED participates in form-level iterations without throwing', () => {
        it('renders a form containing a CED (addField calls field.isHidden())', () => {
            // If ZCED lacked the isHidden() stub the page would have thrown
            // "field.isHidden is not a function" during createCED.
            cy.get('#ced-form').find('input[name=ced_text]').should('exist');
        });

        it('form.disable() and reset() do not throw with a CED present', () => {
            cy.window().then((win) => {
                expect(() => win.cedForm.disable()).to.not.throw();
                expect(() => win.cedForm.enable()).to.not.throw();
                expect(() => win.cedForm.reset()).to.not.throw();
            });
            // The plain field still disabled/enabled as normal.
            cy.window().then((win) => win.cedForm.disable());
            cy.get('#ced-form').find('input[name=ced_text]').should('be.disabled');
        });
    });
});
