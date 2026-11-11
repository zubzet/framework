describe('Storage - File', () => {
    before(() => {
        cy.dbSeed();
    });

    function requestJson(path) {
        return cy.request(path).then((res) => JSON.parse(res.body));
    }

    const byIdFile = {
        "id": 500,
        "reference": "file_byId_Active",
        "name": "byId.txt",
        "type": "text/plain",
        "extension": "txt",
        "size": 11
    };

    const byReferenceFile = {
        "id": 502,
        "reference": "file_byReference",
        "name": "byReference.png",
        "type": "image/png",
        "extension": "png",
        "size": 13
    };

    it('should return a file (byId)', () => {
        requestJson('/file/byId').then((output) => {
            expect(output).to.deep.equal(byIdFile);
        });
    });

    it('should not return a file (byId) because its inactive', () => {
        requestJson('/file/byIdInactive').then((output) => {
            expect(output).to.deep.equal({"found": false});
        });
    });

    it('should return only the active files (byIds)', () => {
        requestJson('/file/byIds').then((output) => {
            expect(output).to.have.deep.members([byIdFile, byReferenceFile]);
            expect(output).to.have.length(2);
        });
    });

    it('should return only the active files (byUuids)', () => {
        requestJson('/file/byUuids').then((output) => {
            expect(output).to.have.deep.members([byIdFile, byReferenceFile]);
            expect(output).to.have.length(2);
        });
    });

    it('should return only active files (all)', () => {
        requestJson('/file/all').then((output) => {
            expect(output).to.include.members([500, 502, 504, 505, 506, 507]);
            expect(output).to.not.include.members([501]);
            expect(output).to.not.include.members([509]);
        });
    });

    it('should return the uuid and creation date', () => {
        requestJson('/file/getters').then((output) => {
            expect(output).to.deep.equal({
                "uuid": "01890000-0000-7000-8000-000000000500",
                "created": "2000-01-01 12:00:00"
            });
        });
    });

    it('should return a file (byUuid)', () => {
        requestJson('/file/byUuid').then((output) => {
            expect(output).to.deep.equal(byIdFile);
        });
    });

    it('should return a file (byReference)', () => {
        requestJson('/file/byReference').then((output) => {
            expect(output).to.deep.equal(byReferenceFile);
        });
    });

    it('should not return a file (byReference) because its inactive', () => {
        requestJson('/file/byReferenceInactive').then((output) => {
            expect(output).to.deep.equal({"found": false});
        });
    });

    it('should be possible to create a new file (add)', () => {
        const expected = {
            "id": 10000,
            "reference": "file_add_New",
            "name": "added.pdf",
            "type": "application/pdf",
            "extension": "pdf",
            "size": 4096
        };

        requestJson('/file/add').then((output) => {
            expect(output).to.deep.equal({
                "createdFileDirect": expected,
                "createdFileGet": expected
            });
        });
    });

    it('should create a file with only a reference (add)', () => {
        requestJson('/file/addDefaults').then(({id, ...output}) => {
            // A retry reseeds, so the id depends on which tests ran before
            expect(id).to.be.a('number');
            expect(output).to.deep.equal({
                "reference": "file_add_Defaults",
                "name": "",
                "type": "",
                "extension": "",
                "size": 0
            });
        });
    });

    it('should be possible to update a file (update)', () => {
        const expected = {
            "id": 515,
            "reference": "file_update_New",
            "name": "updated.pdf",
            "type": "application/pdf",
            "extension": "pdf",
            "size": 4096
        };

        requestJson('/file/update').then((output) => {
            expect(output).to.deep.equal({
                "cleared": true,
                "refreshed": expected,
                "reloaded": expected
            });
        });
    });

    it('should reset the omitted fields on update (update)', () => {
        requestJson('/file/updateDefaults').then((output) => {
            expect(output).to.deep.equal({
                "id": 516,
                "reference": "file_update_DefaultsNew",
                "name": "",
                "type": "",
                "extension": "",
                "size": 0
            });
        });
    });

    it('should be possible to remove a file', () => {
        requestJson('/file/remove').then((output) => {
            expect(output).to.deep.equal({
                "file": {"found": false},
                "rowKept": true,
                "active": 0
            });
        });
    });

    it('should detach the instance on remove', () => {
        requestJson('/file/removeDetaches').then((output) => {
            expect(output).to.deep.equal({
                "id": true,
                "removeAgain": true,
                "refresh": true,
                "update": true,
                "replaceWith": true,
                "replaceWithDetached": true,
                "otherUntouched": null
            });
        });
    });

    it('should reload the fields on refresh', () => {
        requestJson('/file/refresh').then((output) => {
            expect(output).to.deep.equal({
                "beforeRefresh": null,
                "afterRefresh": 511
            });
        });
    });

    it('should be possible to replace a file (replaceWith)', () => {
        requestJson('/file/replaceWith').then((output) => {
            expect(output).to.deep.equal({
                "direct": 505,
                "reloaded": 505,
                "replacementUntouched": null
            });
        });
    });

    it('should overwrite an existing replacement (replaceWith)', () => {
        requestJson('/file/replaceWithOverwrite').then((output) => {
            expect(output).to.deep.equal({
                "id": 514,
                "reference": "file_replaceWith_Next",
                "name": "next.txt",
                "type": "text/plain",
                "extension": "txt",
                "size": 25
            });
        });
    });

    it('should return the replacing file (replacedBy)', () => {
        requestJson('/file/replacedBy').then((output) => {
            expect(output).to.deep.equal({
                "id": 507,
                "reference": "file_replacedBy_New",
                "name": "new.txt",
                "type": "text/plain",
                "extension": "txt",
                "size": 18
            });
        });
    });

    it('should return null when a file was not replaced (replacedBy)', () => {
        requestJson('/file/replacedByNone').then((output) => {
            expect(output).to.deep.equal({"found": false});
        });
    });

    it('should return null when the replacing file was removed (replacedBy)', () => {
        requestJson('/file/replacedByRemoved').then((output) => {
            expect(output).to.deep.equal({"found": false});
        });
    });

    it('should load an uploaded file by its id and reference', () => {
        cy.visit('/file/byId');
        cy.window().then((win) => {
            const body = new win.FormData();
            body.append('file', new win.Blob(['hello file'], {type: 'text/plain'}), 'upload.txt');

            return cy.wrap(win.fetch('/file/upload', {method: 'POST', body}).then((res) => res.json()));
        }).then((output) => {
            expect(output).to.deep.equal({
                "code": 0,
                "sameId": true,
                "sameReference": true,
                "byReference": true,
                "name": "upload.txt",
                "type": "text/plain",
                "extension": "txt",
                "size": 10
            });
        });
    });
});
