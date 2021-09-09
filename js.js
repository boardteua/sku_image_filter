
jQuery(document).ready(function ($) {
    var button = '<button type="button" class="button media-button recheck-image">Assign Images</button>'
    modal = $('.assing-modal');
    stop = false;
    $('.media-toolbar-secondary').append(button);
    $('.media-frame-content').on('click', '.recheck-image', function () {
        if (Cookies.get('pointer')) {
            p = Cookies.get('pointer');
            $('.reassign-raady').html(p);
            console.log(p);
        } else {
            p = 0;
        }
        modal.show();
    });

    $('#close_reassign').on('click', function () {
        modal.hide();
        modal.find('.reassign-output ul').html('');
    });
    $('#start_reassign').on('click', function () {
        stop = false;
        $('#stop_reassign').show();
        $('#start_reassign').hide();


        product_proces(p);

    });
    $('#stop_reassign').on('click', function () {
        stop = true;
        $('#start_reassign').show();
        $('#stop_reassign').hide();
        modal.find('.reassign-output ul').html('');
    });

    $('#reset_reassign').on('click', function () {
        stop = true;
        $('#start_reassign').show();
        $('#stop_reassign').hide();
        modal.find('.reassign-output ul').html('');
        Cookies.set('pointer', 0);
        $('.reassign-raady').html(0);
    });
    product_proces = function (pointer) {
        if (!stop) {
            $.post(ajaxurl,
                    {
                        action: 'proces_product',
                        pointer: pointer
                    },
                    function (res) {
                        if (res.data) {
                            var pointer = res.data.pointer;
                            var product = res.data.product;
                            var msg = res.data.msg;
                            var end = res.data.end;
                            if (end == false || typeof product != 'undefined') {
                                modal.find('.reassign-output ul').append('<li><span class="reassign-output-msg">' + msg + '</span> <span class="reassign-output-title">' + product + '</span></li>')
                                pointer = Number(pointer) + Number(1);
                                Cookies.set('pointer', pointer);
                                $('.reassign-raady').html(pointer);
                                product_proces(pointer);
                            } else {
                                modal.find('.reassign-output ul').append('<li>End of list</li>');
                                stop = true;
                                $('#start_reassign').show();
                                $('#stop_reassign').hide();
                                Cookies.set('pointer', 0);
                            }
                        } else {
                            modal.find('.reassign-estimate').html(res.msg);
                        }
                    });
        }
    }

    $(document).ajaxStart(function () {
        $(".reassign-raady").css("color", "blue  ");
    });
    $(document).ajaxComplete(function () {
        $(".reassign-raady").css("color", "green");
    });
});

