// DOM-structure contract for Z.js fields.
//
// Production views reach into the rendered markup around inputs — most
// commonly `$(field.input).closest('.form-group')` to show/hide or append
// to a field's wrapper, plus direct use of `field.dom`, `field.label` and
// `field.input`. This spec locks that structure so any change to it is
// deliberate: if a field's skeleton or wrapper contract changes, a test
// here fails and names exactly what moved.
//
// The skeleton is a normalized projection — tag + sorted classes + nesting,
// ignoring ids/names/values/text — i.e. the structure, not the cosmetics.

function skeleton(el) {
    const tag = el.tagName.toLowerCase();
    const cls = Array.from(el.classList).sort().join('.');
    const head = cls ? tag + '.' + cls : tag;
    const kids = Array.from(el.children).map(skeleton);
    return kids.length ? head + '(' + kids.join(',') + ')' : head;
}

// The contract. Changing any of these is a real markup change that breaks
// the reach-around patterns in dom-contract's sibling spec — update only
// with intent.
const EXPECTED = {
    c_text:         'div.col.col-12.col-md-12(label,input.form-control,span.form-text.text-danger)',
    c_textarea:     'div.col.col-12.col-md-12(label,textarea.form-control,span.form-text.text-danger)',
    c_checkbox:     'div.col.col-12.col-md-12(div.form-check(input.form-check-input,label.form-check-label),span.form-text.text-danger)',
    c_file:         'div.col.col-12.col-md-12(label,div.custom-file(label.custom-file-label.text-truncate,input.custom-file-input.form-control),span.form-text.text-danger)',
    c_hidden:       'div.col.col-12.col-md-0.d-none(label,input,span.form-text.text-danger)',
    c_prepend:      'div.col.col-12.col-md-12(label,div.input-group(div.input-group-prepend(span.input-group-text),input.form-control),span.form-text.text-danger)',
    c_select:       'div.col.col-12.col-md-12(label,select.form-control(option,option),span.form-text.text-danger)',
    c_multi_select: 'div.col.col-12.col-md-12(label,div(select.form-control(option,option),div.mt-2),span.form-text.text-danger)',
    c_autocomplete: 'div.col.col-12.col-md-12(label,div(input.form-control,div.list-group),span.form-text.text-danger)',
};

describe('Z.js DOM contract', () => {
    before(() => {
        cy.dbSeed();
    });

    beforeEach(() => {
        cy.visit('/Form/domContract');
    });

    describe('field.dom structure per type', () => {
        Object.entries(EXPECTED).forEach(([name, expected]) => {
            it(`${name} renders the expected skeleton`, () => {
                cy.window().then((win) => {
                    expect(skeleton(win.form.fields[name].dom)).to.eq(expected);
                });
            });
        });
    });

    describe("closest('.form-group') wrapper contract", () => {
        it('every field input has a .form-group ancestor (the reach-around target)', () => {
            cy.window().then((win) => {
                Object.values(win.form.fields).forEach((field) => {
                    const group = field.input.closest('.form-group');
                    expect(group, field.name + ' .form-group').to.not.be.null;
                });
            });
        });

        it('the wrapper isolates one field — hiding it does not hide siblings', () => {
            cy.window().then((win) => {
                const textGroup = win.form.fields.c_text.input.closest('.form-group');
                // A full-width field gets its own row/group, so the text
                // field's group must not also contain another field's input.
                expect(textGroup.contains(win.form.fields.c_select.input)).to.eq(false);
                expect(textGroup.contains(win.form.fields.c_text.input)).to.eq(true);
            });
        });
    });

    describe('field handle contract', () => {
        it('field.dom is the col wrapper, field.label is its label, field.input is the control', () => {
            cy.window().then((win) => {
                const f = win.form.fields.c_text;
                expect(f.dom.tagName).to.eq('DIV');
                expect(f.dom.classList.contains('col')).to.eq(true);
                expect(f.label.tagName).to.eq('LABEL');
                expect(f.dom.contains(f.label)).to.eq(true);
                expect(f.dom.contains(f.input)).to.eq(true);
                expect(f.input.getAttribute('name')).to.eq('c_text');
                expect(f.input.classList.contains('form-control')).to.eq(true);
            });
        });
    });
});
