// Blade/Katana compatibility guarantees exercised through the real render path.
//
// - /Compat/probe renders a *migrated* legacy view. Literal template markers
//   ({{ }}, {!! !!}, {{-- --}}) that appear in real app HTML/JS must survive the
//   1.3 migration verbatim (they must NOT be interpreted or stripped by Blade),
//   while raw <?php ?> and $opt access keep working.
// - /Compat/component renders an authored .blade.php that uses an anonymous
//   component (<x-alert>) to confirm components work through the adapter.

describe('Blade compatibility', () => {

    describe('migrated view keeps literal template markers (compat/probe)', () => {
        it('renders {{ }} / {!! !!} / {{-- --}} literally, not interpreted', () => {
            cy.request('/Compat/probe').then((res) => {
                expect(res.status).to.eq(200);

                // Literal echo markers survive verbatim.
                expect(res.body).to.include('<div data-test="literal-echo">{{ notBladeEcho }}</div>');
                expect(res.body).to.include('<div data-test="literal-raw">{!! notBladeRaw !!}</div>');
                // The Blade comment marker must NOT be stripped.
                expect(res.body).to.include('<div data-test="literal-comment">{{-- notBladeComment --}}</div>');

                // Raw PHP still executes and reads $opt.
                expect(res.body).to.include('<div data-test="opt-data">OPT_DATA_OK</div>');

                // CSS at-rules pass through untouched.
                expect(res.body).to.include('@media (max-width: 600px)');

                // A mustache embedded in JS stays literal.
                expect(res.body).to.include('var compatTpl = "{{ vueStyleBinding }}";');
            });
        });
    });

    describe('anonymous component renders through the adapter (compat/component)', () => {
        it('renders <x-alert> with attributes, slot and inner {{ }}', () => {
            cy.request('/Compat/component').then((res) => {
                expect(res.status).to.eq(200);
                // Attribute binding, slot content and an evaluated echo inside the slot.
                expect(res.body).to.include('class="alert alert-warning"');
                expect(res.body).to.include('Component slot works 3');
                // The component tag itself must be compiled away (not echoed literally).
                expect(res.body).to.not.include('<x-alert');
            });
        });
    });
});
