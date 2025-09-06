<?php
if (isset($_SESSION['toastr'])) {
    $msg  = json_encode($_SESSION['toastr']['message']);
    $type = $_SESSION['toastr']['type'];
    echo "<script>
        $(document).ready(function() {
            toastr.$type($msg);
        });
    </script>";
    unset($_SESSION['toastr']);
}
?>
