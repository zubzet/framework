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
});
