/* Честно спизжено отсюда http://alkoweb.ru/dobavlenie-zagruzchika-media-uploader-k-proizvolnomu-polyu-wordpress/ */
var $ = jQuery.noConflict();
$(document).ready(function () {
    /* Обработка события .click() кнопки */
    $("#xls-uploader-submit").click(function () {

        tb_show("Загрузить файл.", "media-upload.php?type=file&TB_iframe=true");
        return false;
    });

    /* Обрабатываем результаты */
    window.send_to_editor = function (html) {
        $("#xls-uploader-input").val($(html).attr('href'));
        tb_remove();
    };

    $('#xls-uploader-process').click(function (e) {
        e.preventDefault();
        var data = 'url_file=' + $("#xls-uploader-input").val();
        $.ajax({
            type: 'post',
            url: '/wp-admin/admin-ajax.php?action=xml_autocomplet',
            data: data,
            success: function (result) {
                console.log('готово!')
            }
        });
    });
});