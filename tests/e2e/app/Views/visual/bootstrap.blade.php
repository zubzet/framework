<style>
    body { background: #f8f9fa !important; }
    *, *::before, *::after { animation: none !important; transition: none !important; }
    .phpdebugbar, div.phpdebugbar-openhandler { display: none !important; }
    .audit-section { padding: 24px; }
    .audit-section + .audit-section { border-top: 1px solid #dee2e6; }
</style>

<div data-test="visual-page">

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <a class="navbar-brand" href="#">Brand</a>
        <ul class="navbar-nav mr-auto">
            <li class="nav-item active"><a class="nav-link" href="#">Home</a></li>
            <li class="nav-item"><a class="nav-link" href="#">Features</a></li>
            <li class="nav-item"><a class="nav-link" href="#">Pricing</a></li>
            <li class="nav-item"><a class="nav-link disabled" href="#">Disabled</a></li>
        </ul>
    </nav>

    <div class="audit-section">
        <h2>Buttons</h2>
        <div>
            <button class="btn btn-primary">Primary</button>
            <button class="btn btn-secondary">Secondary</button>
            <button class="btn btn-success">Success</button>
            <button class="btn btn-danger">Danger</button>
            <button class="btn btn-warning">Warning</button>
            <button class="btn btn-info">Info</button>
            <button class="btn btn-light">Light</button>
            <button class="btn btn-dark">Dark</button>
            <button class="btn btn-link">Link</button>
        </div>
        <div class="mt-2">
            <button class="btn btn-outline-primary">Primary</button>
            <button class="btn btn-outline-secondary">Secondary</button>
            <button class="btn btn-outline-success">Success</button>
            <button class="btn btn-outline-danger">Danger</button>
        </div>
        <div class="mt-2">
            <button class="btn btn-primary btn-sm">Small</button>
            <button class="btn btn-primary">Default</button>
            <button class="btn btn-primary btn-lg">Large</button>
            <button class="btn btn-primary" disabled>Disabled</button>
        </div>
    </div>

    <div class="audit-section">
        <h2>Card</h2>
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">Header</div>
                    <div class="card-body">
                        <h5 class="card-title">Card title</h5>
                        <p class="card-text">Some quick example text to build on the card title.</p>
                        <a href="#" class="btn btn-primary">Action</a>
                    </div>
                    <div class="card-footer text-muted">Footer</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Success card</h5>
                        <p class="card-text">Coloured card variant.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-info">
                    <div class="card-body text-info">
                        <h5 class="card-title">Bordered</h5>
                        <p class="card-text">Border-only variant.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="audit-section">
        <h2>List group</h2>
        <ul class="list-group">
            <li class="list-group-item active">Active item</li>
            <li class="list-group-item">Default item</li>
            <li class="list-group-item disabled">Disabled item</li>
            <li class="list-group-item list-group-item-success">Success</li>
            <li class="list-group-item list-group-item-warning">Warning</li>
            <li class="list-group-item list-group-item-danger">Danger</li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                With badge
                <span class="badge badge-primary badge-pill">14</span>
            </li>
        </ul>
    </div>

    <div class="audit-section">
        <h2>Forms</h2>
        <form class="row">
            <div class="form-group col-md-6">
                <label for="audit-email">Email</label>
                <input type="email" class="form-control" id="audit-email" value="user@example.com">
            </div>
            <div class="form-group col-md-6">
                <label for="audit-password">Password</label>
                <input type="password" class="form-control" id="audit-password" value="secret">
            </div>
            <div class="form-group col-md-6">
                <label for="audit-select">Select</label>
                <select class="form-control" id="audit-select">
                    <option>Option A</option>
                    <option selected>Option B</option>
                    <option>Option C</option>
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="audit-textarea">Textarea</label>
                <textarea class="form-control" id="audit-textarea" rows="3">Sample text content.</textarea>
            </div>
            <div class="form-group col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="audit-check" checked>
                    <label class="form-check-label" for="audit-check">Checked checkbox</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="audit-check-2">
                    <label class="form-check-label" for="audit-check-2">Unchecked checkbox</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" id="audit-radio" name="audit-radio" checked>
                    <label class="form-check-label" for="audit-radio">Radio A</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" id="audit-radio-2" name="audit-radio">
                    <label class="form-check-label" for="audit-radio-2">Radio B</label>
                </div>
            </div>
            <div class="form-group col-12">
                <input class="form-control is-valid" value="Valid input" readonly>
            </div>
            <div class="form-group col-12">
                <input class="form-control is-invalid" value="Invalid input" readonly>
                <div class="invalid-feedback">This field is required.</div>
            </div>
            <div class="col-12">
                <button type="button" class="btn btn-primary">Submit</button>
            </div>
        </form>
    </div>

    <div class="audit-section">
        <h2>Alerts</h2>
        <div class="alert alert-primary">Primary alert</div>
        <div class="alert alert-success">Success alert</div>
        <div class="alert alert-warning">Warning alert</div>
        <div class="alert alert-danger">Danger alert</div>
    </div>

</div>

<script>
    document.fonts.ready.then(function () {
        document.body.setAttribute("data-fonts-ready", "1");
    });
</script>
