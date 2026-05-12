// Helper: every Z.Forms POST needs the isFormData flag so hasFormData()
// returns true. Cypress is the back-end client here.
const postForm = (url, body) => cy.request({
    method: 'POST',
    url,
    form: true,
    failOnStatusCode: false,
    body: { isFormData: 1, ...body },
}).then((res) => ({
    ...res,
    parsedBody: typeof res.body === 'string' ? JSON.parse(res.body) : res.body,
}));

describe('Response', () => {
    describe('json()', () => {
        it('sends a JSON body with application/json content-type and 200 status', () => {
            cy.request('/Response/json_happy').then((res) => {
                expect(res.status).to.eq(200);
                expect(res.headers['content-type']).to.eq('application/json');
                expect(res.body).to.deep.eq({ ok: true });
            });
        });

        it('raises JsonException when the payload is not encodable', () => {
            cy.request({
                url: '/Response/json_non_encodable',
                failOnStatusCode: false,
            }).then((res) => {
                expect(res.body).to.include('JsonException');
            });
        });
    });

    // -----------------------------------------------------------------
    // Cookie domain scope - SECURITY CRITICAL.
    //
    // login_scope_allow_subdomains=true tells the framework to emit the
    // z_login_token cookie with a Domain attribute of ".<base-domain>"
    // (e.g. ".example.com"). That tells the browser to send the cookie
    // to *every* subdomain (app.example.com, data-warehouse.example.com),
    // enabling unified login across subdomains.
    //
    // login_scope_allow_subdomains=false (default) emits the cookie with
    // no Domain attribute, so the browser only sends it back to the
    // exact host that issued it - subdomains are isolated.
    //
    // The tests below pin both behaviors so an accidental flip can't
    // silently broaden or narrow the cookie scope.
    // -----------------------------------------------------------------
    describe('cookie domain scope (security)', () => {
        it('getCookieDomainScope() returns "" when subdomain sharing is disabled', () => {
            cy.request('/Response/cookieDomainScopeDefault').then((res) => {
                expect(res.body.scope).to.eq('');
            });
        });

        it('getCookieDomainScope() returns ".<domain>" when subdomain sharing is enabled', () => {
            cy.request('/Response/cookieDomainScopeSubdomain').then((res) => {
                // The leading dot is the wildcard signal to browsers - it's
                // how RFC 6265 (legacy) and modern browsers identify
                // "send this cookie to all subdomains".
                expect(res.body.scope).to.eq(`.${res.body.domain}`);
                expect(res.body.scope.charAt(0)).to.eq('.');
            });
        });

        // The actual Set-Cookie header must reflect the scope. Without
        // this assertion, getCookieDomainScope() could return the right
        // string while loginAs forgets to wire it into setcookie().
        it('loginAs emits Set-Cookie WITH Domain=.<base> when subdomain sharing is on', () => {
            cy.request('/Response/loginAsWithSubdomainScope').then((res) => {
                const headers = [].concat(res.headers['set-cookie'] || []);
                const login = headers.find((h) => h.startsWith('z_login_token='));
                expect(login, 'Set-Cookie carrying z_login_token').to.exist;
                // The Domain= attribute is what makes the cookie travel to
                // sibling subdomains. Lowercase per RFC 6265 §4.1.2.3.
                expect(login.toLowerCase()).to.match(/domain=\.[^;]+/);
                // The bare domain (without the leading dot) appears too.
                expect(login.toLowerCase()).to.include('domain=.localhost');
            });
            cy.clearCookie('z_login_token');
        });

        // The negative case is the actual security boundary - if this
        // assertion ever flips, every login cookie issued by an app
        // suddenly becomes shareable across subdomains by default.
        it('loginAs emits Set-Cookie WITHOUT Domain= when subdomain sharing is off', () => {
            cy.request('/Response/loginAsWithoutSubdomainScope').then((res) => {
                const headers = [].concat(res.headers['set-cookie'] || []);
                const login = headers.find((h) => h.startsWith('z_login_token='));
                expect(login, 'Set-Cookie carrying z_login_token').to.exist;
                expect(login.toLowerCase()).to.not.match(/(^|; ?)domain=/);
            });
            cy.clearCookie('z_login_token');
        });

        // deleteOldLoginCookieDomainScope() clears the cookie on a
        // configured *legacy* domain so the unified-login transition
        // doesn't leave orphaned cookies on the old scope. PHP emits
        // lowercase `domain=` and the cleared value is `deleted` (or "").
        it('deleteOldLoginCookieDomainScope clears the legacy-domain login cookie', () => {
            cy.request('/Response/loginAsWithLegacyScope').then((res) => {
                const headers = [].concat(res.headers['set-cookie'] || []);
                const legacy = headers.find((h) =>
                    h.toLowerCase().includes('domain=.legacy.example'));
                expect(legacy, 'Set-Cookie targeting .legacy.example').to.exist;
                expect(legacy).to.match(/z_login_token=(deleted|);/);
                expect(legacy).to.match(/expires=Thu, 01-Jan-1970/);
            });
            cy.clearCookie('z_login_token');
        });
    });

    // -----------------------------------------------------------------
    // insertDatabase / updateDatabase / insertOrUpdateDatabase exercised
    // through the documented production pattern: a view renders a Z.Forms
    // form, the back-end action validates with validateForm and persists
    // via the helper. Cypress submits each POST directly - same shape as
    // Z.Forms would emit.
    // -----------------------------------------------------------------
    describe('insertDatabase / updateDatabase / insertOrUpdateDatabase via form', () => {
        before(() => cy.dbSeed());

        it('renders the Z.Forms view on a bare GET', () => {
            cy.request('/Response/insertForm').then((res) => {
                expect(res.status).to.eq(200);
                expect(res.body).to.include('Z.Forms.create');
                expect(res.body).to.include('name: "col_a"');
            });
        });

        // GET branches on updateForm + insertOrUpdateForm are the render
        // paths. Without these, the action_updateForm / action_insertOrUpdateForm
        // $res->render() lines stay uncovered (test-project code targets
        // 100% just like src/).
        it('GET updateForm/<id> renders the view prefilled with the row data', () => {
            postForm('/Response/insertForm', {
                col_a: 'render-source',
                col_b: 77,
            }).then((insert) => {
                const id = insert.parsedBody.id;
                cy.request(`/Response/updateForm/${id}`).then((res) => {
                    expect(res.status).to.eq(200);
                    expect(res.body).to.include('Z.Forms.create');
                    // The prefilled values come through JSON-encoded.
                    expect(res.body).to.include('"render-source"');
                    expect(res.body).to.include('77');
                });
            });
        });

        it('GET insertOrUpdateForm renders the view without prefill when no id is supplied', () => {
            cy.request('/Response/insertOrUpdateForm').then((res) => {
                expect(res.status).to.eq(200);
                expect(res.body).to.include('Z.Forms.create');
            });
        });

        it('GET insertOrUpdateForm/<id> renders the view with the row data', () => {
            postForm('/Response/insertForm', {
                col_a: 'iou-render',
                col_b: 88,
            }).then((insert) => {
                const id = insert.parsedBody.id;
                cy.request(`/Response/insertOrUpdateForm/${id}`).then((res) => {
                    expect(res.status).to.eq(200);
                    expect(res.body).to.include('"iou-render"');
                });
            });
        });

        it('POST inserts a row and returns the new id', () => {
            postForm('/Response/insertForm', {
                col_a: 'real-form-insert',
                col_b: 42,
            }).then((res) => {
                expect(res.parsedBody.result).to.eq('success');
                expect(res.parsedBody.id).to.be.a('number').and.greaterThan(0);

                cy.request(`/Response/probeRow/${res.parsedBody.id}`).then((row) => {
                    expect(row.body.col_a).to.eq('real-form-insert');
                    expect(row.body.col_b).to.eq(42);
                    expect(row.body.created_by).to.eq(7);
                });
            });
        });

        // updateDatabase target: do an insert first via the same form,
        // then PUT-style update via the same action.
        it('POST to updateForm/<id> rewrites the row', () => {
            postForm('/Response/insertForm', {
                col_a: 'before-update',
                col_b: 100,
            }).then((insert) => {
                const id = insert.parsedBody.id;
                postForm(`/Response/updateForm/${id}`, {
                    col_a: 'after-update',
                    col_b: 200,
                }).then((upd) => {
                    expect(upd.parsedBody.result).to.eq('success');
                });
                cy.request(`/Response/probeRow/${id}`).then((row) => {
                    expect(row.body.col_a).to.eq('after-update');
                    expect(row.body.col_b).to.eq(200);
                });
            });
        });

        // insertOrUpdate: no id -> insertDatabase branch; same id -> updateDatabase branch.
        it('insertOrUpdateForm covers both branches via a single action', () => {
            // First call: no pk -> insertDatabase.
            postForm('/Response/insertOrUpdateForm', {
                col_a: 'insertOrUpdate-first',
                col_b: 1,
            }).then((first) => {
                expect(first.parsedBody.result).to.eq('success');
                const id = first.parsedBody.id;

                // Second call: known pk -> updateDatabase.
                postForm(`/Response/insertOrUpdateForm/${id}`, {
                    col_a: 'insertOrUpdate-second',
                    col_b: 2,
                }).then((second) => {
                    expect(second.parsedBody.result).to.eq('success');
                    expect(second.parsedBody.id).to.eq(id);
                });

                cy.request(`/Response/probeRow/${id}`).then((row) => {
                    expect(row.body.col_a).to.eq('insertOrUpdate-second');
                    expect(row.body.col_b).to.eq(2);
                });
            });
        });

        // validateForm fails -> formErrors short-circuit before any SQL runs.
        it('rejects an invalid submission with formErrors (no row inserted)', () => {
            postForm('/Response/insertForm', {
                col_a: '',
                col_b: 'not-a-number',
            }).then((res) => {
                expect(res.parsedBody.result).to.eq('formErrors');
                expect(res.parsedBody.formErrors).to.satisfy((errs) =>
                    errs.some((e) => e.name === 'col_a' && e.type === 'required')
                );
            });
        });

        // Regression for the noSave fix: an optional file field that
        // wasn't uploaded must not pollute INSERT/UPDATE column lists.
        // (The optional `file_id` rule passes validation, uploadFromForm
        // sets noSave=true, and insertDatabase must skip it - same
        // contract updateDatabase already honored.)
        it('insertDatabase honors noSave when an optional file field is empty', () => {
            postForm('/Response/insertForm', {
                col_a: 'no-file-row',
                col_b: 9,
            }).then((res) => {
                cy.request(`/Response/probeRow/${res.parsedBody.id}`).then((row) => {
                    expect(row.body.col_a).to.eq('no-file-row');
                    expect(row.body.col_b).to.eq(9);
                    expect(row.body.file_id, 'file_id stayed at DB default').to.eq(null);
                });
            });
        });

        it('updateDatabase honors noSave when an optional file field is empty', () => {
            // Insert with col_a/col_b only, then update with col_a/col_b only.
            // updateDatabase must not zero out file_id (it was DB default NULL
            // and stays NULL).
            postForm('/Response/insertForm', {
                col_a: 'update-no-file-before',
                col_b: 5,
            }).then((insert) => {
                const id = insert.parsedBody.id;
                postForm(`/Response/updateForm/${id}`, {
                    col_a: 'update-no-file-after',
                    col_b: 6,
                }).then(() => {
                    cy.request(`/Response/probeRow/${id}`).then((row) => {
                        expect(row.body.col_a).to.eq('update-no-file-after');
                        expect(row.body.file_id).to.eq(null);
                    });
                });
            });
        });
    });

    // -----------------------------------------------------------------
    // Response::reroute() - non-alias branch and $final=true exit.
    // Alias-mode reroute is covered by the existing
    // /Advanced/aliases flow (AdvancedController).
    // -----------------------------------------------------------------
    describe('reroute()', () => {
        it('non-alias reroute delegates execution to the new path', () => {
            cy.request('/Response/rerouteNonAlias').then((res) => {
                expect(res.body).to.eq('Controller Action');
            });
        });

        it('$final=true exits before any post-reroute output runs', () => {
            cy.request('/Response/rerouteFinal').then((res) => {
                expect(res.body).to.eq('Controller Action');
                expect(res.body).to.not.include('AFTER_FINAL_MARKER');
            });
        });
    });

    // CED (validateCED + doCED) coverage lives in its own spec:
    // tests/cypress/e2e/form/ced.cy.js.
});
