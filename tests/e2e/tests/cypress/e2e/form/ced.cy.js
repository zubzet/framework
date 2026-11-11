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

    describe('error fallthrough and no-op', () => {
        it('unknown Z value triggers the error() fallthrough', () => {
            postCedForm([
                { Z: 'totally-not-a-real-action', name: 'x', note: 'y' },
            ]).then((res) => {
                expect(res.parsedBody.result).to.eq('error');
            });
        });
    });
});
