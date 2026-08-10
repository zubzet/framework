{{-- The framework's body essentials, previously layout_essentials.php::essentialsBody().
     Takes the render data as :opt. --}}
@auth
    <script>
        var token_expired_callback = setInterval(function() {
            if(document.cookie.indexOf("z_login_token") < 0) {
                location.reload();
            }
        }, 1000);
    </script>
@endauth

<?= \ZubZet\Framework\ErrorHandling\DebugBar\DebugBarBridge::renderBody(); ?>
