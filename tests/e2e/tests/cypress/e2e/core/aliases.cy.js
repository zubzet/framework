// Runtime regression for src/Aliases.php This guarantees every legacy alias
// (`z_db`, `Request`, `User`) still resolves to its canonical FQCN.
// Single source of truth for the alias contract is AliasController::$pairs.

function fetchCheckSync() {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', '/alias/check', false);
    xhr.send();
    if(200 !== xhr.status) throw new Error(`GET /alias/check returned ${xhr.status}`);
    return JSON.parse(xhr.responseText);
}

describe('Class aliases (src/Aliases.php)', () => {
    Object.entries(fetchCheckSync()).forEach(([alias, info]) => {
        it(`${alias} resolves to its canonical FQCN`, () => {
            expect(info.exists, `${alias} class_exists`).to.equal(true);
            expect(
                info.match,
                `${alias} → ${info.resolvesTo} (expected ${info.canonical})`,
            ).to.equal(true);
        });
    });
});
