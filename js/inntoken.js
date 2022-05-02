jQuery(function ($) {
    $(document).ready(function () {
        $("#amount").on("change paste keyup", function () {
            var without_toman = ($(this).val()).replace("تومان", "")
            var without_dot = without_toman.replace(",", "")
            $("#token").text(without_dot / 30 + " توکن نوآوری (INN)");
        });
        $('#amount').autoNumeric('init', {aSign: 'تومان ', aPad: false});
        $('.menu .item').tab();
    });
});

function copy(text) {
    navigator.clipboard.writeText(text);
}
