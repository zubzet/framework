<?php return ["layout" => function ($opt, $body, $head) { ?>
    <!doctype html>
    <html class="no-js" lang="de">
        <head>
            <?php $opt["layout_essentials_head"]($opt); ?>
            <?php $head($opt); ?>
        </head>
        <body id="top" data-test="dashboard-top">

            <div class="container">
                <div class="bg-light mt-3 py-4  px-3 shadow border border-primary rounded-lg">
                    <div class="row justify-content-center">
                        <div class="col-10 col-lg-6 col-xl-4">
                            <div class="card shadow-sm rounded-lg">
                                <div class="card-body text-center h3 font-weight-bold text-primary mb-0">
                                    ZubZet QA Suite
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <?php $body($opt); ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php $opt["layout_essentials_body"]($opt); ?>
        </body>
    </html>
<?php }]; ?>