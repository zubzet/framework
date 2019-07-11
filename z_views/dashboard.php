<?php function head($opt) { ?> <!-- File header -->

    <link href="<?php echo $opt["root"]; ?>assets/css/dashboardElements.css" rel="stylesheet">

<?php } function body($opt) { ?> <!-- File body -->	

    <!-- Image card -->
    <div class="row align-center columns container-padded">

        <div class="row expanded collapse">
            <div class="column">
                <div class="large-article-header no-cache-bg" data-background-image="https://unsplash.it/1132/240/?blur=1">
                    <div class="large-article-header-content">
                        <div class="center-container">
                            <div class="article-date">
                                <p class="shadowText"><?php echo $opt["date"]; ?></p>
                            </div>
                            <div class="article-title">
                                <h1 class="shadowTextBig"><?php $opt["lang"]("title"); ?></h1>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php } function getLangArray() {
    
    return [
        "DE_Formal" => [
            "title" => "Your Website"
        ], 
        "en" => [
            "title" => "Your Website"
        ]
    ];

} ?>