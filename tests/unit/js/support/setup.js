import { readFileSync } from "node:fs";
import { expect } from "chai";

/**
 * Loaded by Mocha before the tests (see .mocharc.json). Like in a Cypress
 * spec, a test imports nothing: next to Mocha's `describe` and `it` it finds
 *
 * - `expect`: Chai's assertions.
 * - `Z`: what web/Z.js defines, loaded again before every test, so tests
 *   share no state.
 * - `ajax`: stands in for jQuery's `$.ajax()`. `ajax.request` holds the
 *   options of the latest call, `ajax.respond(answer)` answers it with JSON
 *   as text, and `ajax.respondParsed(answer)` with the object jQuery makes
 *   of a reply that is sent as `application/json`. `ajax.respondText(text)`
 *   answers with any other text.
 */
const source = readFileSync(new URL("../../../../web/Z.js", import.meta.url), "utf8");

globalThis.expect = expect;

export const mochaHooks = {
    beforeEach() {
        let done = null;

        globalThis.ajax = {
            request: null,
            respond: (answer) => done(JSON.stringify(answer)),
            respondParsed: (answer) => done(answer),
            respondText: (text) => done(text),
        };

        globalThis.$ = {
            ajax(options) {
                ajax.request = options;
                return { done: (callback) => done = callback };
            },
        };

        // Z.js is a plain browser script that assigns the global `Z`
        new Function(source)();
    },
};
