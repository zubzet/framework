// Drives src/Message/Input/State.php through StateProbeController. Each
// `it` checks one semantic property; the probe builds a fresh State,
// applies a single wither, and emits a JSON snapshot which the assertion
// inspects. No DB seeding needed - the probes are pure in-memory state
// manipulation.

const probe = (action) => cy.request(`/StateProbe/${action}`);

describe('Input/State', () => {

    describe('fromOverwrite()', () => {
        // Patch semantic (intended asymmetry vs withGet): per-key merge
        // into the existing array, leaving untouched keys in place.
        it('merges per-key into the existing arrays', () => {
            probe('fromOverwrite_basic_merge').then((res) => {
                expect(res.body.parent_GET, 'parent left intact')
                    .to.deep.equal({ a: '1', b: '2' });
                expect(res.body.child_GET, 'child merges new keys, overwrites collisions')
                    .to.deep.equal({ a: '1', b: 'overridden', c: 'new' });
            });
        });

        // `$child->previous = &$input;` is a reference, not a clone.
        // Mutating the parent after the call must be visible through
        // the child's back-pointer.
        it('child->previous tracks the parent by reference', () => {
            probe('fromOverwrite_previous_ref').then((res) => {
                expect(res.body.child_previous_GET).to.deep.equal({ a: 'mutated-after' });
                // The child's own GET was cloned at fromOverwrite time;
                // it must not have observed the post-hoc parent mutation.
                expect(res.body.child_GET).to.deep.equal({ a: 'original' });
            });
        });

        it('rejects unknown overwrite keys with InvalidArgumentException', () => {
            probe('fromOverwrite_unknown_throws').then((res) => {
                expect(res.body.threw).to.eq(true);
                expect(res.body.message).to.match(/unknown type.*NOT_A_FIELD/);
            });
        });

        // PHP arrays are value-typed, so mutations on the clone shouldn't
        // leak back into the parent. Pin it down anyway: a future shift
        // to objects-by-reference (e.g. typed value-objects) would break
        // this without this guard.
        it('parent is unaffected by clone mutations', () => {
            probe('fromOverwrite_parent_isolated').then((res) => {
                expect(res.body.parent_POST).to.deep.equal({ k: 'parent' });
                expect(res.body.child_POST).to.deep.equal({ k: 'child' });
            });
        });
    });

    describe('withUrl()', () => {
        it('wires scheme, host, path, query, GET in one call', () => {
            probe('withUrl_all_parts').then((res) => {
                expect(res.body.scheme).to.eq('https');
                expect(res.body.https).to.eq('on');
                expect(res.body.host).to.eq('example.com');
                expect(res.body.request_uri).to.eq('/foo/bar?x=1&y=two');
                expect(res.body.redirect_url).to.eq('/foo/bar');
                expect(res.body.query_string).to.eq('x=1&y=two');
                expect(res.body.GET).to.deep.equal({ x: '1', y: 'two' });
            });
        });

        it('https scheme sets SERVER.HTTPS=on', () => {
            probe('withUrl_https_on').then((res) => {
                expect(res.body.scheme).to.eq('https');
                expect(res.body.https).to.eq('on');
            });
        });

        it('http scheme sets SERVER.HTTPS=off', () => {
            probe('withUrl_https_off').then((res) => {
                expect(res.body.scheme).to.eq('http');
                expect(res.body.https).to.eq('off');
            });
        });

        // parse_url on a leading-slash string yields only `path`. Scheme
        // and host on the State must therefore be left as-is.
        it('path-only URL leaves prior scheme/host intact', () => {
            probe('withUrl_path_only').then((res) => {
                expect(res.body.scheme).to.eq('preserved');
                expect(res.body.host).to.eq('preserved.example');
                expect(res.body.request_uri).to.eq('/just/a/path');
            });
        });

        // Implementation calls withGet() before withPath() - withGet
        // rewrites REQUEST_URI from the path-less side, withPath then
        // overwrites again. With a query-only URL, withPath is never
        // called, so withGet's view of REQUEST_URI must already contain
        // the prior path.
        it('query-only URL replaces query while preserving path', () => {
            probe('withUrl_query_only').then((res) => {
                expect(res.body.request_uri).to.eq('/original?fresh=value');
                expect(res.body.query_string).to.eq('fresh=value');
                expect(res.body.GET).to.deep.equal({ fresh: 'value' });
            });
        });
    });

    describe('withPath()', () => {
        it('rewrites REQUEST_URI and REDIRECT_URL', () => {
            probe('withPath_basic').then((res) => {
                expect(res.body.request_uri).to.eq('/foo/bar');
                expect(res.body.redirect_url).to.eq('/foo/bar');
            });
        });

        it('strips leading slashes before reformatting', () => {
            probe('withPath_strips_leading_slashes').then((res) => {
                expect(res.body.request_uri).to.eq('/foo');
                expect(res.body.redirect_url).to.eq('/foo');
            });
        });

        // Documented carry-over: a path-only change keeps the prior
        // query string on REQUEST_URI (REDIRECT_URL never includes it).
        // Confirmed intended behavior, not a bug.
        it('preserves any prior QUERY_STRING on REQUEST_URI', () => {
            probe('withPath_preserves_query').then((res) => {
                expect(res.body.request_uri).to.eq('/new-path?x=1&y=two');
                expect(res.body.redirect_url).to.eq('/new-path');
            });
        });

        it('omits the query separator when no QUERY_STRING is set', () => {
            probe('withPath_no_query').then((res) => {
                expect(res.body.request_uri).to.eq('/new-path');
            });
        });
    });

    describe('withGet()', () => {
        it('replaces GET wholesale (intended asymmetry vs fromOverwrite)', () => {
            probe('withGet_replaces').then((res) => {
                expect(res.body.GET).to.deep.equal({ only: 'new' });
                expect(res.body.GET).to.not.have.property('old');
                expect(res.body.query_string).to.eq('only=new');
                expect(res.body.request_uri).to.eq('/foo?only=new');
                expect(res.body.REQUEST).to.deep.equal({ only: 'new' });
            });
        });

        it('empty array clears both query string and REQUEST_URI query', () => {
            probe('withGet_empty_clears_query').then((res) => {
                expect(res.body.request_uri).to.eq('/foo');
                expect(res.body.query_string).to.eq('');
                // PHP json_encodes empty associative arrays as []; either
                // shape is acceptable as "empty".
                expect(res.body.GET).to.be.empty;
            });
        });

        // strtok-based path extraction must keep the prior path while
        // swapping out only the query. Pins down the implementation choice.
        it('keeps the path while swapping the query', () => {
            probe('withGet_preserves_base_path').then((res) => {
                expect(res.body.request_uri).to.eq('/some/path?fresh=yes');
            });
        });

        // Regression: withGet() must tolerate a State that has never set
        // SERVER["REQUEST_URI"] (CLI / ad-hoc). State.php uses `?? ""`.
        it('survives a State without prior SERVER.REQUEST_URI', () => {
            probe('withGet_missing_request_uri').then((res) => {
                expect(res.body.threw, 'withGet must not throw on a fresh State').to.eq(false);
                expect(res.body.query_string).to.eq('fresh=value');
                expect(res.body.GET).to.deep.equal({ fresh: 'value' });
            });
        });
    });

    describe('withPost() / withFiles() / withSession() / withBody() / withMethod() / withCookies() / withReferer()', () => {
        it('withPost replaces POST and refreshes REQUEST', () => {
            probe('withPost_replaces_and_updates_request').then((res) => {
                expect(res.body.POST).to.deep.equal({ fresh: 'value' });
                expect(res.body.REQUEST).to.deep.equal({ fresh: 'value' });
            });
        });

        it('withFiles replaces FILES wholesale', () => {
            probe('withFiles_replaces').then((res) => {
                expect(res.body.FILES).to.deep.equal({ after: { name: 'after.txt' } });
            });
        });

        it('withSession replaces SESSION wholesale', () => {
            probe('withSession_replaces').then((res) => {
                expect(res.body.SESSION).to.deep.equal({ after: 2 });
            });
        });

        it('withBody overwrites the raw body string', () => {
            probe('withBody_sets').then((res) => {
                expect(res.body.body).to.eq('new body');
            });
        });

        it('withMethod overwrites SERVER.REQUEST_METHOD', () => {
            probe('withMethod_sets').then((res) => {
                expect(res.body.method).to.eq('DELETE');
            });
        });

        it('withCookies replaces COOKIE and refreshes REQUEST', () => {
            probe('withCookies_replaces_and_updates_request').then((res) => {
                expect(res.body.COOKIE).to.deep.equal({ after: '2' });
                expect(res.body.REQUEST).to.deep.equal({ after: '2' });
            });
        });

        it('withReferer overwrites HTTP_REFERER verbatim', () => {
            probe('withReferer_sets').then((res) => {
                expect(res.body.referer).to.eq('https://prev.example/page');
            });
        });
    });

    // updateRequest() is private; observe it via withGet -> withPost
    // -> withCookies. array_merge(GET, POST, COOKIE) means COOKIE wins
    // on collision, POST beats GET. This pins down the precedence so
    // a refactor of updateRequest doesn't silently reorder.
    describe('updateRequest() merge precedence', () => {
        it('REQUEST = array_merge(GET, POST, COOKIE) - COOKIE > POST > GET', () => {
            probe('updateRequest_precedence').then((res) => {
                expect(res.body.REQUEST).to.deep.equal({
                    common: 'fromCookie',
                    g: 'g',
                    p: 'p',
                    c: 'c',
                });
            });
        });
    });

    describe('withArgs()', () => {
        // CLI convention: argv[0] is the script, argv[1] is the command.
        // withArgs forks the *sub-arguments* while keeping that prefix.
        it('keeps argv[0..1] and replaces the rest', () => {
            probe('withArgs_keeps_first_two').then((res) => {
                expect(res.body.argv).to.deep.equal([
                    'index.php', 'command', 'fresh1', 'fresh2', 'fresh3',
                ]);
            });
        });

        // No prior argv → array_slice on null defaults to [] and the
        // result is just the supplied args.
        it('survives a missing SERVER.argv (treats it as empty)', () => {
            probe('withArgs_empty_prior_argv').then((res) => {
                expect(res.body.argv).to.deep.equal(['a', 'b']);
            });
        });
    });

    describe('withPreviousAsReferer()', () => {
        it('builds scheme://host/path from the previous state', () => {
            probe('withPreviousAsReferer_happy').then((res) => {
                expect(res.body.referer).to.eq('https://prev.example/old/page?x=1');
            });
        });

        it('throws LogicException when there is no previous state', () => {
            probe('withPreviousAsReferer_no_previous_throws').then((res) => {
                expect(res.body.threw).to.eq(true);
                expect(res.body.message).to.match(/no previous input/i);
            });
        });

        it('throws LogicException when the previous HTTP_HOST is empty', () => {
            probe('withPreviousAsReferer_no_host_throws').then((res) => {
                expect(res.body.threw).to.eq(true);
                expect(res.body.message).to.match(/HTTP_HOST/);
            });
        });

        // Mirrors the HTTP_HOST guard - the function refuses to produce
        // a partial (path-only) referer. Without this guard the call
        // used to silently emit just `/old/page`.
        it('throws LogicException when the previous REQUEST_SCHEME is empty', () => {
            probe('withPreviousAsReferer_no_scheme_throws').then((res) => {
                expect(res.body.threw).to.eq(true);
                expect(res.body.message).to.match(/REQUEST_SCHEME/);
            });
        });
    });

    describe('fromRequest()', () => {
        // POST bodies prefixed with `<#decURI#>` get rawurldecoded before
        // landing in State.POST. This is the one wither-independent piece
        // of fromRequest() that has user-visible semantics.
        it('rawurldecodes <#decURI#>-prefixed POST values', () => {
            cy.request({
                method: 'POST',
                url: '/StateProbe/decURI',
                form: true,
                body: { raw: '<#decURI#>foo%20bar%26baz' },
            }).then((res) => {
                expect(res.body.decoded).to.eq('foo bar&baz');
            });
        });
    });
});
