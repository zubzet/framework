<?php return ["layout" => function($opt, $body, $head) { ?>
    <html>
        <head>
            <meta charset="utf-8"/>
            <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
            <style>
                body {
                    background: #ebeced;
                }

                .text-primary {
                    color: #024aae;
                }

                .mb-3 {
                    margin-bottom: 1rem;
                }

                .font-weight-bold {
                    font-weight: bold;
                }

                .container {
                    width: 60%;
                    padding-right: 15px;
                    padding-left: 15px;
                    margin-right: auto;
                    margin-left: auto;
                    padding-top: 20px;
                    padding-bottom: 20px;
                }

                .card {
                    border-top: 4px solid #024aae;
                    box-shadow: 0 .125rem .25rem rgba(0,0,0,.075) !important;
                    padding: 1.25rem;
                    background: white;
                }
            </style>
            <?= $head($opt); ?>
        </head>
        <body>
            <div class="container">
                <div class="card">
                    <?= $body($opt); ?>
                </div>
            <div>
        </body>
    </html>
<?php }]; ?>
