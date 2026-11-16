describe("Z.Request.action", () => {
    it("posts the subaction and hands the answer to the handler", () => {
        let received = null;

        Z.Request.action("save", { id: 7 }, (answer) => received = answer);
        ajax.respond({ result: "success" });

        expect(ajax.request.method).to.equal("POST");
        expect(ajax.request.data).to.deep.equal({ id: 7, action: "save" });
        expect(received).to.deep.equal({ result: "success" });
    });

    // jQuery parses a reply that is sent as application/json itself
    it("hands an answer that jQuery has already parsed to the handler", () => {
        let received = null;

        Z.Request.action("save", {}, (answer) => received = answer);
        ajax.respondParsed({ result: "success", id: 5 });

        expect(received).to.deep.equal({ result: "success", id: 5 });
    });

    it("does not call the handler for an answer that is no JSON", () => {
        let called = false;
        const logged = silenceConsoleError();

        Z.Request.action("save", {}, () => called = true);
        ajax.respondText("<html>an error page</html>");

        expect(called).to.equal(false);
        expect(logged()).to.equal(1);
    });

    for (const [name, respond] of answers()) {
        it(`asks for the second factor instead of calling the handler, ${name}`, () => {
            let called = false;
            let asked = 0;
            Z.Presets.RefreshTwoFactor = () => asked++;

            Z.Request.action("save", {}, () => called = true);
            respond({ twoFactorRenew: true });

            expect(asked).to.equal(1);
            expect(called).to.equal(false);
        });
    }
});

describe("Z.Request.root", () => {
    for (const [name, respond] of answers()) {
        it(`posts to the path below the root and hands the answer to the handler, ${name}`, () => {
            let received = null;
            Z.Request.rootPath = "/app/";

            Z.Request.root("users/list", "load", { page: 2 }, (answer) => received = answer);
            respond({ result: "success", rows: [1, 2] });

            expect(ajax.request.url).to.equal("/app/users/list");
            expect(ajax.request.data).to.deep.equal({ page: 2, action: "load" });
            expect(received).to.deep.equal({ result: "success", rows: [1, 2] });
        });

        it(`asks for the second factor instead of calling the handler, ${name}`, () => {
            let called = false;
            let asked = 0;
            Z.Presets.RefreshTwoFactor = () => asked++;

            Z.Request.root("users/list", "load", {}, () => called = true);
            respond({ twoFactorRenew: true });

            expect(asked).to.equal(1);
            expect(called).to.equal(false);
        });
    }

    it("hands an answer that is no JSON to the handler as it arrived", () => {
        let received = null;
        silenceConsoleError();

        Z.Request.root("users/export", "csv", {}, (answer) => received = answer);
        ajax.respondText("a;b;c");

        expect(received).to.equal("a;b;c");
    });

    it("leaves the answer alone when it is not to be parsed", () => {
        let received = null;

        Z.Request.root("users/list", "load", {}, (answer) => received = answer, true, false);
        ajax.respondText('{"result":"success"}');

        expect(received).to.equal('{"result":"success"}');
    });
});

// The two ways an answer reaches Z.js: as JSON text, or parsed by jQuery for a reply sent as application/json
function answers() {
    return [
        ["sent as text", (answer) => ajax.respond(answer)],
        ["sent as application/json", (answer) => ajax.respondParsed(answer)],
    ];
}

// Z.js reports an answer it cannot read on the console, which has no place in the test output
let consoleError = null;

function silenceConsoleError() {
    let calls = 0;
    consoleError = console.error;
    console.error = () => calls++;
    return () => calls;
}

afterEach(() => {
    if (consoleError === null) return;

    console.error = consoleError;
    consoleError = null;
});
