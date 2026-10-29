// Full coverage of the Z.Forms CED system (Create / Edit / Delete) end
// to end - Z.Forms-shaped POST -> Request::validateCED -> Response::doCED
// -> z_probe_ced (id, name, note, active).
//
// Two fields (name + note) so a regression in the doCED edit/insert
// field-iteration loop can't sneak through. The probe controller's
// view (Views/response_probe/ced.php) builds the matching Z.Forms.createCED
// widget; the back-end action is ResponseController::action_cedForm.

const postCedForm = (items) => cy.request({
    method: 'POST',
    url: '/Response/cedForm',
    form: true,
    failOnStatusCode: false,
    body: { isFormData: 1, items },
}).then((res) => ({
    ...res,
    parsedBody: typeof res.body === 'string' ? JSON.parse(res.body) : res.body,
}));

const cedRows = () => cy.request('/Response/probeCedRows').its('body');

describe('Form CED (validateCED + doCED)', () => {
    beforeEach(() => cy.dbSeed());

    it('renders the Z.Forms CED view on a bare GET', () => {
        cy.request('/Response/cedForm').then((res) => {
            expect(res.status).to.eq(200);
            expect(res.body).to.include('form.createCED');
            expect(res.body).to.include('"items"');
            // Both fields are wired into the view.
            expect(res.body).to.include('"name"');
            expect(res.body).to.include('"note"');
        });
    });

    describe('Z=create', () => {
        it('inserts a new row with both fields persisted', () => {
            postCedForm([{
                Z: 'create',
                name: 'created-by-ced',
                note: 'first note',
            }]).then((res) => {
                expect(res.parsedBody.result).to.eq('success');
            });

            cedRows().then((rows) => {
                const fresh = rows.find((r) => r.name === 'created-by-ced');
                expect(fresh, 'inserted row').to.exist;
                expect(fresh.note).to.eq('first note');
                expect(fresh.active).to.eq(1);
            });
        });
    });

    describe('Z=edit', () => {
        it('updates BOTH fields on the row identified by dbId', () => {
            postCedForm([{
                Z: 'edit',
                dbId: 1,
                name: 'renamed-baseline',
                note: 'rewritten note',
            }]).then((res) => {
                expect(res.parsedBody.result).to.eq('success');
            });

            cedRows().then((rows) => {
                const row = rows.find((r) => r.id === 1);
                expect(row.name).to.eq('renamed-baseline');
                expect(row.note).to.eq('rewritten note');
            });
        });

        // doCED's edit branch iterates validationResult->fields, so any
        // regression that drops the loop's trailing-comma logic would
        // produce a SQL syntax error here.
        it('produces valid SQL with multiple fields in the SET clause', () => {
            postCedForm([{
                Z: 'edit',
                dbId: 1,
                name: 'multi-field-edit',
                note: 'second-field-also-updated',
            }]).then((res) => {
                // If the SET clause was malformed, doCED's exec would throw
                // and the response would be a 500 / Whoops page - parsing
                // as JSON would fail.
                expect(res.parsedBody.result).to.eq('success');
            });
        });
    });

    describe('Z=delete', () => {
        it('soft-deletes (active=0) the row identified by dbId', () => {
            // validateCED applies the field rules to every entry regardless
            // of Z (production ZController submits role/name for delete
            // entries too), so we provide satisfying values here.
            postCedForm([{
                Z: 'delete',
                dbId: 1,
                name: 'baseline',
                note: 'seeded',
            }]).then((res) => {
                expect(res.parsedBody.result).to.eq('success');
            });

            cedRows().then((rows) => {
                const row = rows.find((r) => r.id === 1);
                expect(row.active).to.eq(0);
            });
        });
    });

    describe('batched submissions', () => {
        it('applies multiple Z entries in order', () => {
            postCedForm([
                { Z: 'create', name: 'mixed-1', note: 'note-1' },
                { Z: 'create', name: 'mixed-2', note: 'note-2' },
                { Z: 'edit', dbId: 1, name: 'mixed-baseline', note: 'mixed-baseline-note' },
            ]).then((res) => {
                expect(res.parsedBody.result).to.eq('success');
            });

            cedRows().then((rows) => {
                const byName = Object.fromEntries(rows.map((r) => [r.name, r]));
                expect(byName).to.have.all.keys(
                    'mixed-1', 'mixed-2', 'mixed-baseline',
                );
                expect(byName['mixed-1'].note).to.eq('note-1');
                expect(byName['mixed-2'].note).to.eq('note-2');
                expect(byName['mixed-baseline'].note).to.eq('mixed-baseline-note');
            });
        });
    });

    describe('validation', () => {
        it('rejects entries that fail field rules; nothing is persisted', () => {
            postCedForm([
                // length(1, 64) on name is violated by the empty string.
                { Z: 'create', name: '', note: 'has-note' },
            ]).then((res) => {
                expect(res.parsedBody.result).to.eq('formErrors');
                expect(res.parsedBody.formErrors).to.satisfy((errs) =>
                    errs.some((e) => e.name === 'items' && e.subname === 'name')
                );
            });

            // The seed row is untouched; no spurious inserts.
            cedRows().then((rows) => {
                expect(rows.filter((r) => r.active === 1)).to.have.lengthOf(1);
            });
        });

        // Both fields are required - the second-field probe catches a
        // bug where validateCED's per-field error reporting would miss
        // anything past the first field.
        it('reports errors per-field with the index of the offending row', () => {
            postCedForm([
                { Z: 'create', name: 'ok', note: '' },
                { Z: 'create', name: '', note: 'ok' },
            ]).then((res) => {
                expect(res.parsedBody.result).to.eq('formErrors');
                const errs = res.parsedBody.formErrors;
                // Index 0: note missing. Index 1: name missing.
                expect(errs).to.satisfy((e) =>
                    e.some((x) => x.index === 0 && x.subname === 'note')
                );
                expect(errs).to.satisfy((e) =>
                    e.some((x) => x.index === 1 && x.subname === 'name')
                );
            });
        });
    });

    describe('error fallthrough and no-op', () => {
        it('unknown Z value triggers the error() fallthrough', () => {
            postCedForm([
                { Z: 'totally-not-a-real-action', name: 'x', note: 'y' },
            ]).then((res) => {
                expect(res.parsedBody.result).to.eq('error');
            });
        });

        // No "items" key at all -> validateCED sets doNothing=true, doCED
        // returns immediately without iterating $_POST.
        it('omitting the CED key entirely is a no-op (doNothing branch)', () => {
            cy.request({
                method: 'POST',
                url: '/Response/cedForm',
                form: true,
                failOnStatusCode: false,
                body: { isFormData: 1, noise: 'irrelevant' },
            }).then((res) => {
                const body = typeof res.body === 'string' ? JSON.parse(res.body) : res.body;
                expect(body.result).to.eq('success');
            });

            // Seed row still untouched.
            cedRows().then((rows) => {
                expect(rows).to.have.lengthOf(1);
                expect(rows[0].name).to.eq('baseline');
                expect(rows[0].note).to.eq('seeded');
                expect(rows[0].active).to.eq(1);
            });
        });
    });
});
