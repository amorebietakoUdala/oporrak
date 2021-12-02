import $ from 'jquery';

import '../../js/common/select2';

$(function() {
    $('.js-save').on('click', function(e) {
        $(document.user).attr('action', $(e.currentTarget).data("url"));
        document.user.submit();
    });

    $('.js-select2').select2();
});