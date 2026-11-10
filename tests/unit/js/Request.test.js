describe("Z.Request.action", () => {
    it("posts the subaction and hands the answer to the handler", () => {
        let received = null;

        Z.Request.action("save", { id: 7 }, (answer) => received = answer);
        ajax.respond({ result: "success" });

        expect(ajax.request.method).to.equal("POST");
        expect(ajax.request.data).to.deep.equal({ id: 7, action: "save" });
        expect(received).to.deep.equal({ result: "success" });
    });
});
