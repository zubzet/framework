{{-- The framework's head essentials, previously layout_essentials.php::essentialsHead().
     Registered as a shared anonymous-component path by the Katana engine, so every layout
     resolves <x-zubzet::head/> whatever view root it lives under. Takes the render
     data as :opt, and :custom-bootstrap to skip the bundled Bootstrap JS. --}}
<script src="<?php $opt["generateResourceLink"]("_zubzet/asset-proxy/js/jquery.min.js"); ?>"></script>
<script src="<?php $opt["generateResourceLink"]("_zubzet/asset-proxy/js/popper.min.js"); ?>"></script>

@if(!($customBootstrap ?? false))
    <script src="<?php $opt["generateResourceLink"]("_zubzet/asset-proxy/js/bootstrap.min.js"); ?>"></script>
@endif

<script src="<?php $opt["generateResourceLink"]("_zubzet/asset-proxy/js/bs-custom-file-input.js"); ?>"></script>
<script src="<?php $opt["generateResourceLink"]("_zubzet/asset-proxy/Z.js") ?>"></script>

<link href="<?php $opt["generateResourceLink"]("_zubzet/asset-proxy/css/bootstrap.min.css"); ?>" rel="stylesheet">

<link rel="stylesheet" href="<?php $opt["generateResourceLink"]("_zubzet/asset-proxy/css/font-awesome/all.min.css") ?>">
<link rel="stylesheet" href="<?php $opt["generateResourceLink"]("_zubzet/asset-proxy/css/font-awesome/brands.min.css") ?>">
<link rel="stylesheet" href="<?php $opt["generateResourceLink"]("_zubzet/asset-proxy/css/font-awesome/v4-shims.min.css") ?>">
<link rel="stylesheet" href="<?php $opt["generateResourceLink"]("_zubzet/asset-proxy/css/font-awesome/fontawesome.min.css") ?>">

<script>
    Z.Request.rootPath = "<?= $opt["root"]; ?>";
    Z.Request.rootHost = "<?= $opt["request"]->getRoot(); ?>";
    Z.Request.absRoot = "<?= $opt["absRoot"]; ?>";

    Z.Lang.unsaved = "<i class='fas fa-pen text-dark'></i> <?= __("form.unsaved") ?>";
    Z.Lang.submit = "<i class='fas fa-check'></i> <?= __("form.submit") ?>";
    Z.Lang.saved = "<i class='fas fa-check text-dark'></i> <?= __("form.saved") ?>";
    Z.Lang.choose_file = "<?= __("form.choose_file") ?>";
    Z.Lang.error_filter = "<?= __("form.error_filter") ?>";
    Z.Lang.error_length = "<?= __("form.error_length") ?>";
    Z.Lang.error_required = "<?= __("form.error_required") ?>";
    Z.Lang.error_unique = "<?= __("form.error_unique") ?>";
    Z.Lang.error_exist = "<?= __("form.error_exist") ?>";
    Z.Lang.error_range = "<?= __("form.error_range") ?>";
    Z.Lang.error_file_to_big = "<?= __("form.error_file_to_big") ?>";

</script>

<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>

<?= \ZubZet\Framework\ErrorHandling\DebugBar\DebugBarBridge::renderHead(); ?>
