$().ready(function() {
    function search() {
        if ($('#search_text').val()) {
            window.location.href = '/search/' + $('#search_text').val();
        }
    }
    $('#search_text').keypress(function(e) {
        if (e.keyCode == 13) {
            search();
        }
    });
    $('#search_icon').click(function() {
        search();
    });
});
