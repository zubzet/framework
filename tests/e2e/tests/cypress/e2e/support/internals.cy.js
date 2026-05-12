// Defensive branches in src/Support/* that aren't reachable from any
// natural request flow — StaticCache::get error paths, HasDynamicAttributes
// guards, and Checkpoint::restore. Probes live in SupportProbeController.
// No cy.dbSeed — none of these touch the database.

describe('Support internals', () => {

    const requestJson = (path) =>
        cy.request(path).then((res) => JSON.parse(res.body));

    // ------------------------------------------------------------------
    describe('StaticCache::get', () => {
        it('throws InvalidArgumentException when the type bucket is missing', () => {
            requestJson('/SupportProbe/staticCacheMissingType').then((out) => {
                expect(out.message).to.match(/type/i);
                expect(out.message).to.include('does not exist in the cache');
            });
        });

        it('throws InvalidArgumentException when the key is missing under an existing type', () => {
            requestJson('/SupportProbe/staticCacheMissingKey').then((out) => {
                expect(out.message).to.match(/key/i);
                expect(out.message).to.include('does not exist in the cache');
            });
        });
    });

    // ------------------------------------------------------------------
    describe('HasDynamicAttributes guards', () => {
        it('rejects direct __get of the internal dynamicAttributesStore', () => {
            requestJson('/SupportProbe/dynamicAttributesGetStore').then((out) => {
                expect(out.message).to.eq('The attribute store cannot be accessed directly.');
            });
        });

        it('rejects direct __isset of the internal dynamicAttributesStore', () => {
            requestJson('/SupportProbe/dynamicAttributesIssetStore').then((out) => {
                expect(out.message).to.eq('The attribute store cannot be accessed directly.');
            });
        });

        it('throws when reading an attribute that was never set', () => {
            requestJson('/SupportProbe/dynamicAttributesMissing').then((out) => {
                expect(out.message).to.include('does not exist in the attribute store');
            });
        });
    });

    // ------------------------------------------------------------------
    describe('Checkpoint::restore()', () => {
        it('restores initialized props and unsets ones uninitialized at snapshot time', () => {
            requestJson('/SupportProbe/checkpointRestore').then((out) => {
                expect(out.initializedAfter, 'initialized prop restored').to.eq('initial-value');
                // After restore, the previously-unset typed property is unset
                // again — proving the `unset($this->target->$name)` branch ran.
                expect(out.uninitializedSetAfter, 'uninitialized prop unset again').to.be.false;
            });
        });
    });
});
