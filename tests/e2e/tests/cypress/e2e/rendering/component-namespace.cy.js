// Component namespace isolation (katanaphp/blade#66), driven by
// ComponentNamespaceController + Views/renderprobe/namespace. The framework
// registers its own components under the "zubzet" namespace, so <x-zubzet::head/>
// resolves to IncludedComponents/views/components/head.blade.php, while a plain
// <x-head/> resolves to the app's own app/Views/components/head.blade.php. The two
// share a bare name on purpose and must never shadow each other.
//
// Stateless render: no cy.dbSeed().

describe('Component namespace isolation', () => {
    it('resolves <x-zubzet::head/> and <x-head/> to their own owners', () => {
        cy.request('/ComponentNamespace/isolation').then((res) => {
            expect(res.status).to.eq(200);

            // Plain <x-head/> -> app component (userspace). If the framework's
            // namespaced head leaked into the plain namespace this marker would be
            // gone, replaced by the framework essentials.
            expect(res.body).to.include('data-test="app-head"');
            expect(res.body).to.include('APP_HEAD_COMPONENT');

            // <x-zubzet::head/> -> framework component. Z.Request.rootPath and the
            // proxied jquery are emitted only by the framework head; if the app
            // component had shadowed the namespaced tag they would be absent.
            expect(res.body).to.include('Z.Request.rootPath');
            expect(res.body).to.include('_zubzet/asset-proxy/js/jquery.min.js');

            // Both component tags compiled away (not echoed literally).
            expect(res.body).to.not.include('<x-zubzet::head');
            expect(res.body).to.not.include('<x-head');
        });
    });
});
