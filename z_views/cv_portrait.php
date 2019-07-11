<?php function head($opt) { ?> <!-- File header -->

    <link rel="stylesheet" href="<?php echo $opt["root"]; ?>assets/css/loadCircle.css">

    <style>
        .img-image {
            width: 100%;
            height: auto;
            background: #505050;
            border: 1px solid black;
            position: absolute;
            top: 0px;
            left: 0px;
        }

        .img-wrapper {
            margin: 10px;
            position: relative;
            top: 0px;
            left: 0px;
        }

        .img-selection {
            border: 2px solid red;
            position: absolute;
            pointer-events: none;
        }

        #selection-square {
            border-color: green;
        }

        #selection-circle {
            border-color: blue;
        }
    </style>

    <script>

        var x1, y1, x2, y2, w, h;
        var sqX1, sqY1, sqX2, sqY2;
        var centerX, centerY, radius;
        var enableCrop = false;

        const minSize = 300;

        $(() => {

            $("#dropzone").on("dragover", e => {
                e.preventDefault();
            });

            $("#dropzone").on("drop", (e) => {
                e.preventDefault();
                if (e.originalEvent.dataTransfer.files) {
                    loadImage(e.originalEvent.dataTransfer.files[0]);
                }
            });

            $("#input-file").on("change", (e) => {
                loadImage($("#input-file")[0].files[0]);
            });

            $("#preview").on("dragstart", (e) => e.preventDefault());

            //Cursor coords
            var cX = 0;
            var cY = 0;
            var lastX = 0;
            var lastY = 0;

            var dragArea = false;
            var shiftArea = false;
            var scaleArea = false;
            var selection = $("#selection");

            $(document).on("mousemove touchmove", (e) => {

                var position = $("#img-wrapper").position(document);

                cX = viewToImageX(e.clientX - position.left);
                cY = viewToImageY(e.clientY - position.top);

                if (dragArea) {
                    e.preventDefault();
                    x2 = cX;
                    y2 = cY;
                    renderSelection();
                }
                if (shiftArea) {
                    var deltaX = cX - lastX;
                    var deltaY = cY - lastY;
                    var lh = y2 - y1;
                    var lw = x2 - x1;
                    x1 = Math.max(Math.min(x1 + deltaX, w - lw), 0);
                    y1 = Math.max(Math.min(y1 + deltaY, h - lh), 0);
                    x2 = x1 + lw;
                    y2 = y1 + lh;
                    renderSelection();
                }

                if (scaleArea) {
                    var deltaCenterX = cX - centerX;
                    var deltaCenterY = cY - centerY;
                    var r = Math.sqrt(deltaCenterX * deltaCenterX + deltaCenterY * deltaCenterY);
                    if (r > (minSize / 2) && (centerX - r) > 0 && (centerY - r) > 0 && (centerX + r) < w && (centerY + r) < h) {
                        x1 = centerX - r;
                        y1 = centerY - r;
                        x2 = centerX + r;
                        y2 = centerY + r;
                    }
                    renderSelection();
                }

                lastX = cX;
                lastY = cY;
            });

            $("#img-wrapper").on("mousedown touchstart", (e) => {
                cX = viewToImageX(e.offsetX);
                cY = viewToImageY(e.offsetY);

                var deltaCenterX = cX - centerX;
                var deltaCenterY = cY - centerY;
                var deltaR = Math.sqrt(deltaCenterX * deltaCenterX + deltaCenterY * deltaCenterY) - radius;
                if (deltaR < 40 && deltaR > -40) {
                    scaleArea = true;
                } else if (cX > sqX1 && cX < sqX2 && cY > sqY1 && cY < sqY2) {
                    shiftArea = true;
                }
            });

            $(document).on("mouseup touchend", () => {
                dragArea = false;
                shiftArea = false;
                scaleArea = false;
            });

            var preview = $("#preview");

            function viewToImageX(viewX) {
                return Math.max(Math.min((viewX / preview.width()) * w, w), 0);
            }
            function viewToImageY(viewY) {
                return Math.max(Math.min((viewY / preview.height()) * h, h), 0);
            }

            updateView();

            $(window).resize("resize", () => {
                updateView();
            });

            $("#button-submit").on("click", () => $("#form").submit());

            $("#form").submit(function(e) {
                console.log("Uploading...");
                $("#loading").show();

                e.preventDefault();    
                setFormData();
                $("#button-submit").attr("disabled", true);
                var formData = new FormData(this);

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    success: function (data) {
                        var obj = JSON.parse(data);//will fail id data is not valid json
                        if (obj.result == "success") {
                            if (location.href.includes("save")) {
                                location.reload();
                            } else {
                                if (location.href.includes("portrait/")) {
                                    location.href += 'save';
                                } else {
                                    location.href += '/save';
                                }
                            }
                        } else {
                            console.log("Upload failed!");
                            console.log(obj);
                        }
                        $("#loading").hide();
                    },
                    cache: false,
                    contentType: false,
                    processData: false
                });
            });
        });

        function renderSelection() {
            var selection = $("#selection");
            var selectionCi = $("#selection-circle");
            var selectionSq = $("#selection-square");
            var preview = $("#preview");
            var pw = preview.width();
            var ph = preview.height();

            var xx1 = Math.min(x1, x2);
            var xx2 = Math.max(x1, x2);
            var yy1 = Math.min(y1, y2);
            var yy2 = Math.max(y1, y2);

            var lw = xx2 - xx1;
            var lh = yy2 - yy1;

            /*var lw = xx2 - xx1;
            var yy2 = yy1 + lw;*/

            selection.css({top: (yy1 / h) * ph, left: (xx1 / w) * pw, right: pw - (xx2 / w) * pw, bottom: ph - (yy2 / h) * ph });

            if (lw > lh) {
                var space = (lw - lh) / 2;
                sqX1 = xx1 + space;
                sqY1 = yy1;
                sqX2 = xx2 - space;
                sqY2 = yy2;
            }
            if (lw < lh) {
                var space = (lh - lw) / 2;
                sqX1 = xx1;
                sqY1 = yy1 + space;
                sqX2 = xx2;
                sqY2 = yy2 - space;
            }
            if (lw == lh) {
                sqX1 = xx1;
                sqY1 = yy1;
                sqX2 = xx2;
                sqY2 = yy2;
            }

            centerX = (sqX1 + sqX2) / 2;
            centerY = (sqY1 + sqY2) / 2;
            radius = lw / 2;

            selectionSq.css({top:(sqY1 / h) * ph, left: ((sqX1) / w) * pw, right: pw - ((sqX2) / w) * pw, bottom: ph - (sqY2 / h) * ph });
            selectionCi.css({top:(sqY1 / h) * ph, left: ((sqX1) / w) * pw, right: pw - ((sqX2) / w) * pw, bottom: ph - (sqY2 / h) * ph, "border-radius": lw / 2 });
        }

        function loadImage(file) {
            var reader = new FileReader();
            var preview = document.getElementById("preview");

            reader.onloadend = function () {

                var image = new Image();

                image.src = reader.result;

                image.onload = function() {
                    w = image.width;
                    h = image.height;

                    if (w < minSize || h < minSize) {
                        alert('<?php $opt["lang"]("min_resolution"); ?>');
                        return;
                    }

                    preview.src = reader.result;

                    $("#img-wrapper").height($(preview).height());
                    enableCrop = true;
                    $(".img-selection").removeClass("hide");
                    $("#button-submit").prop("disabled", false);

                    x1 = 0;
                    y1 = 0;
                    var sq = Math.min(w, h);
                    x2 = x1 + sq;
                    y2 = y1 + sq;
                    renderSelection();
                }

            }
            reader.readAsDataURL(file);
        }

        function updateView() {
            $("#img-wrapper").height($("#preview").height());
            renderSelection();
        }


        const url = "<?php echo $opt["root"]; ?>cv/portrait/upload";

        function setFormData() {
            var xx1 = Math.min(x1, x2);
            var xx2 = Math.max(x1, x2);
            var yy1 = Math.min(y1, y2);
            var yy2 = Math.max(y1, y2);

            $("#input-x1").val(xx1);
            $("#input-y1").val(yy1);
            $("#input-x2").val(xx2);
            $("#input-y2").val(yy2);

            /*
            $.ajax({
                type: 'POST',   
                url: url,
                data: new FormData($("#form")),
                success() {
                    console.log("Uploaded!");
                }
            });*/
        }

    </script>

<?php } function body($opt) { ?> <!-- File body -->	

    <h1><?php $opt["lang"]("profile_picture_upload"); ?></h1>

    <div class="hide-for-small-only">

        <?php if ($opt["ref_save"]) { ?>
            <div class="callout success" id="success-box" data-closable>
                <?php echo 'Saved ('.date("H:i:s", time()).")"; ?>
                <button class="close-button" aria-label="Dismiss alert" type="button" data-close>
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php } ?>

        <div id="preview-container" class="row">
            <div class="small-0 medium-2 large-4"></div>
            <div class="small-12 medium-8 large-4 img-wrapper" id="img-wrapper">
                <img id="preview" src="<?php echo $opt["pp"]; ?>" class="img-image">
                <!--<div id="selection" class="img-selection hide"></div>-->
                <!--<div id="selection-square" class="img-selection hide"></div>-->
                <div id="selection-circle" class="img-selection hide"></div>
            </div>
            <div class="small-0 medium-2 large-4"></div>
        </div>

        <div id="dropzone">
            <label style="height: 100px; display: block" class="callout secondary coloumns" for="input-file"><?php $opt["lang"]("click_or_drop_here"); ?></label>
        </div>

        <form enctype="multipart/form-data" action="<?php echo $opt["root"]; ?>cv/portrait/upload" method="post" id="form">
            <div class="row">
                <!--<div class="small-12 medium-4">
                    <label for="input-file">Or choose file from disk</label>
                </div>-->
                <div class="small-12 medium-8">
                    <input type="file" id="input-file" name="pp" class="hide">
                </div>
            </div>

            <input type="hidden" name="x1" id="input-x1">
            <input type="hidden" name="y1" id="input-y1">
            <input type="hidden" name="x2" id="input-x2">
            <input type="hidden" name="y2" id="input-y2">
        </form>
        <button class="button" id="button-submit" disabled><?php $opt["lang"]("save"); ?></button>
    </div>

    <div class="show-for-mobile-only">
        <?php $opt["lang"]("not_available_on_mobile"); ?>
    </div>

    <div class="loading" id="loading" style="display: none;"><?php $opt["lang"]("loading"); ?>&#8230;</div>

<?php } 
    function getLangArray() {
        return [
            "de_formal" => [
                "profile_picture_upload" => "Profilbild hochladen",
                "click_or_drop_here" => "Hier klicken oder Datei ziehen",
                "not_available_on_mobile" => "Nicht verfügbar auf Mobilgeräten",
                "loading" => "Lade",
                "save" => "Speichern",
                "min_resolution" => "Das von Ihnen hochgeladene Bild erfüllt nicht die Mindestauflösungsanforderungen!"
            ],
            "en" => [
                "profile_picture_upload" => "Profile picture upload",
                "click_or_drop_here" => "Click here or drop image file",
                "not_available_on_mobile" => "Currently not available on mobile devices",
                "loading" => "Loading",
                "save" => "Save",
                "min_resolution" => "The picture you uploaded does not meet the minimum resolution requirements!"
            ]
        ];
    }
?>