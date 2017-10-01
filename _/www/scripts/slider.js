var slidercnt = 0;
var sliderimg = [];
var sliderref = [];
var totalwidth = 0;
var totalheight = 0;
var controls = [];
var leftarrow;
var rightarrow;
var pause = 1;
var canvas;
var timer;
var sliderleft;
var dx;
var imgs = [];
var timeout = 1000;

function isCanvasSupported(){
  var elem = document.createElement('canvas');
  return !!(elem.getContext && elem.getContext('2d'));
}

function SliderStart(Width,Height, Imgarray, Refarray) {
    totalwidth = Width;
    totalheight = Height;
    sliderimg = Imgarray;
    sliderref = Refarray;
    pause = 0;
    bannercnt = 1;
	sliderleft = 0;
    canvas = isCanvasSupported();
    $(document).ready(function(){
        if (canvas) SliderInit();
        else SliderInitNoCanvas();});
}

function SliderAfterChange() {
    clearTimeout(timer);
    timer = setTimeout(SliderBeginRotate,timeout);
    if (sliderleft) slidercnt--;
    else slidercnt++;
    sliderleft = 0;
    if (slidercnt>=sliderimg.length) slidercnt=0;
    else if (slidercnt<0) slidercnt=sliderimg.length-1;
	$('#bannerref').attr('href',sliderref[slidercnt]);
}

function SliderBeginRotate() {
    if (!sliderleft==0) dx = 20;
    else dx = totalwidth-20;
    clearTimeout(timer);
    if (canvas) timer = setTimeout(SliderOnTimer,20);
    //else timer = setTimeout(SliderOnTimerNoCanvas,20);
}

function SliderCreateControls() {
    var half = Math.floor(sliderimg.length / 2);
    //var middle = totalwidth/2;
    for(var i=0;i<sliderimg.length;i++) 
        //controls[i] = [middle+(i-half)*15-5,5];
        controls[i] = [totalwidth-50-(half*2-i)*40,5];
    leftarrow = [10,totalheight/2];
    rightarrow = [totalwidth - 10,totalheight/2];
}

function SliderInitNoCanvas(){
    $('#bannerref').html('<img src="'+sliderimg[0]+'">');
}

function SliderPause(){
    if(!pause){
        clearTimeout(timer);
        pause = 1;
    }else{
        SliderOnTimer();
        pause = 0;
    }
}

function SliderOnTimerNoCanvas(){}

function SliderInit() {
    canv = document.getElementById('banner');
    ctx = canv.getContext('2d');
    
    SliderCreateControls();
    canv.addEventListener('click', OnClick);
    
    for(var i=0;i<sliderimg.length;i++){
        imgs[i] = new Image();
        imgs[i].src = sliderimg[i];
    }
    
    imgs[0].onload = function(){//Событие которое будет исполнено в момент когда изображение будет загружено
        ctx.drawImage(imgs[0],0,0,totalwidth,totalheight);
        DrawControls();
    };
    sliderleft = 0;
    timer = setTimeout(SliderBeginRotate,timeout);
}

function DrawControls() {
    var canv = document.getElementById('banner');
    var ctx = canv.getContext('2d');
    var colors = ["#9a969d","#9a969d","#9a969d","#9a969d","#9a969d","#9a969d","#9a969d"];//9a969d
    for(var i=0;i<sliderimg.length;i++) {
        if (i==slidercnt) ctx.fillStyle = "#ffb15f";//ffb15f
		else ctx.fillStyle = colors[i%7];
		ctx.strokeStyle = "#ffb15f";
        ctx.fillRect(controls[i][0],controls[i][1],15,15);
        //if (i==slidercnt) ctx.strokeRect(controls[i][0]-3,controls[i][1]-3,31,31);
    }
    var x1 = leftarrow[0];
    var y1 = leftarrow[1];
    var x2 = rightarrow[0];
    var y2 = rightarrow[1];
    var arr_r = new Image();
    arr_r.src = 'img/style/arrow_right.png';
    ctx.drawImage(arr_r,770,170);
    var arr_l = new Image();
    arr_l.src = 'img/style/arrow_left.png';
    ctx.drawImage(arr_l,5,170);
	ctx.fillStyle = "rgba(255,255,255,0.7)";
}

function SliderOnTimer() {
    var canv = document.getElementById('banner');
    var ctx = canv.getContext('2d');
    var a,b;
    if (sliderleft == 0) a = slidercnt, b = slidercnt+1;
    else b = slidercnt, a = slidercnt-1;
    if (b>=sliderimg.length) b=0;
    else if (a<0) a = sliderimg.length-1;
    if(dx<totalwidth&&dx>0){
        ctx.fillStyle = "white";
        ctx.fillRect(0,0,totalwidth,totalheight);
        ctx.drawImage(imgs[b], 0,0, totalwidth-dx,totalheight, dx,0, totalwidth-dx,totalheight);
        ctx.drawImage(imgs[a],totalwidth-dx,0,dx,totalheight,0,0,dx,totalheight);
        DrawControls();
        if (!sliderleft==0) dx+=10;
        else dx-=10;
        clearTimeout(timer);
        timer = setTimeout(SliderOnTimer,20);
    }else{
        SliderAfterChange();
        SliderDrawFinal();
    }
}

function SliderDrawFinal() {
    var canv = document.getElementById('banner');
    var ctx = canv.getContext('2d');
    ctx.fillStyle = "white";
    ctx.fillRect(0,0,totalwidth,totalheight);
    ctx.drawImage(imgs[slidercnt],0,0,totalwidth,totalheight);
    DrawControls();
}

function SliderChangeTo(n) {
    clearTimeout(timer);
    slidercnt = n-1;
    SliderAfterChange();
    SliderDrawFinal();
}

function OnClick(event){
    canv = document.getElementById('banner');
    var br=canv.getBoundingClientRect()
    x = event.pageX - br.left;
    y = event.pageY - br.top;
    //event.preventDefault();
    //alert(x+' '+y);
    //alert(leftarrow[0]+' '+leftarrow[1]);
    //Вычитаем высоту прокрутки
    y-= window.pageYOffset;
    x-= window.pageXOffset;
    if(x>leftarrow[0]-5&&x<leftarrow[0]+20&&y>leftarrow[1]-20&&y<leftarrow[1]+20){
        sliderleft = 1;
        SliderBeginRotate();
        event.preventDefault();
    }
    if(x<rightarrow[0]+5&&x>rightarrow[0]-20&&y>rightarrow[1]-20&&y<rightarrow[1]+20){
        sliderleft = 0;
        SliderBeginRotate();
        event.preventDefault();
    }
    for(var i=0;i<controls.length;i++) {
        if(x>controls[i][0]-3&&x<controls[i][0]+16&&y>controls[i][1]-3&&y<controls[i][1]+16)
            {SliderChangeTo(i);event.preventDefault();break;}
    }
}