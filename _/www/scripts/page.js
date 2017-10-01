var m1 = 0;/*высота шапки*/
var m2 = 0;/*отступ, когда во время прокрутки шапка уже не видна*/
var menuID = "navigation";
var menuOpacity = "1";
$(window).ready(function(){
    setMenuPosition();
});
$(document).ready(function(){
    scroll_if_anchor(window.location.hash);//после загрузки страницы
    $("body").on("click", "a", scroll_if_anchor);//клик по ссылке
});
/*функция регистрирует вычисление позиции «плавающего» меню при прокрутке страницы*/
function setMenuPosition(){
    if(typeof window.addEventListener != "undefined"){
        window.addEventListener("scroll", marginMenuTop, false);
    } else if(typeof window.attachEvent != "undefined"){
        window. attachEvent("onscroll", marginMenuTop);
    }
    marginMenuTop();
}
/*стили sticky-navigation*/
function marginMenuTop(){
    //var top = getScrollTop();
    var s = document.getElementById(menuID);
    if(typeof s == "undefined" || !s) return;
    /*if (top+m2 < m1){
        s.style.top = "0px";
        s.style.position = "static";
    }else{*/
        s.style.top = m2 + "px";
        s.style.position = "fixed";
    //}
}
/*функция кроссбраузерного определения отступа от верха документа к текущей позиции скроллера прокрутки*/
function getScrollTop(){
    var scrOfY = 0;
    if(typeof(window.pageYOffset) == "number"){
        //Netscape compliant
        scrOfY = window.pageYOffset;
    }else if(document.body && (document.body.scrollLeft||document.body.scrollTop)){
        //DOM compliant
        scrOfY = document.body.scrollTop;
    }else if(document.documentElement && (document.documentElement.scrollLeft||document.documentElement.scrollTop)){
        //IE6 Strict
        scrOfY = document.documentElement.scrollTop;
    }
    return scrOfY;
}
/***STICKY MENU END***/
function scroll_if_anchor(href){
    href = typeof(href) == "string" ? href : $(this).attr("href");
    var fromTop = 40;
    if(href.indexOf("#") == 0){
        var $target = $(href);
        if($target.length){
            $('html, body').animate({ scrollTop: $target.offset().top - fromTop},800);
            if(history && "pushState" in history) {
                history.pushState({}, document.title, window.location.pathname + href);
                return false;
            }
        }
    }
}