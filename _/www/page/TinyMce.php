<?php
class TinyMce {
    public static function getInitScript($selector = '.tinymce') {
        return '<script type="text/javascript" src="tinymce/tinymce.min.js"></script>'.chr(10)
        .'<script type="text/javascript" src="tinymce/langs/ru.js"></script>'.chr(10)
        .'<script type="text/javascript">'.chr(10)
        .'tinymce.init({selector:"'.$selector.'",width: 980,height: 800,'.chr(10)
        .'plugins: ['.chr(10)
        .'    "advlist autolink link image lists charmap hr anchor pagebreak",'.chr(10)
        .'    "searchreplace wordcount visualblocks visualchars insertdatetime media nonbreaking",'.chr(10)
        .'    "save table contextmenu directionality template paste textcolor code"],'.chr(10)
        .'content_css: "css.css",'.chr(10)
        .'fontsize_formats: "8pt 9pt 10pt 11px 12pt 14pt 18pt 24pt 36pt",'.chr(10)
        .'toolbar: "insertfile undo redo | styleselect fontselect fontsizeselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | l ink image | forecolor backcolor" '
        .'});'.chr(10)
        .'</script>';
    }
}

