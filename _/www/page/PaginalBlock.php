<?php
include_once 'libs/SimpleLink.php';

class PaginalBlock {
    public $isdone = false;
    
    public $curpagenum;
    public $totalpages;
    
    public $items=array();
    public $pagefooter=false;
    public $timestamp=0;
    public function run(ItemRequest $request,$perpage) {
        if ($perpage==0) return DError::raise(baseclass::eWrongArgument,"perpage can't be 0");
        Url::parse();
        $cnt = $request->getAgregation(true);
        if ($cnt==0) return true;
        $pagecnt = intval(($cnt-1)/$perpage)+1;
        $this->totalpages = $pagecnt;
        $pagenum = Get::Int('page');
        $this->curpagenum = $pagenum;
        if ($pagenum>0) $pagenum--;
        if ($pagenum>=0) {$request->limits = array($pagenum*$perpage,$perpage);}
        $items = Item::getlist($request,false,Item::rfAutoType);
        SimpleLink::resolveLinks($items, $request->values);
        $timestamp = 0;
        $out = array();
        foreach($items as $x) {
            if (!($x instanceof IPageBlock)) continue;
            $out[] = $x;
            if ($x->getTimestamp()>$timestamp) $timestamp = $x->getTimestamp();
        }
        $this->items = $out;
        $this->timestamp = $timestamp;
        if ($pagecnt<=0) return true;
        //ПАГИНАЦИЯ
        if($pagenum == '') $pagenum = 1;
        else $pagenum++;
        $lngth = 5;//max ширина пагинации,[3,...)
        $right = (int)($lngth/2);//max справа от активной
        $left0 = $right + ($lngth/2 - $right)*2 - 1;//max слева от активной
        $actve = 'class="active"';
        if($pagecnt < $lngth) $lngth = $pagecnt;//ширина не может быть больше кол-ва страниц
        $right_delta = $pagecnt - $pagenum - $right;//сколько справа места
        if($right_delta > 0) $right_delta = 0;//отрицательное -> не хватает места
        $start = $pagenum - ($left0 - $right_delta);//вычисляем стартовую позицию
        if($start < 1) $start = 1;//в случае если слева мало места
        for($i = 0; $i < $lngth; $i++){
            $page = $i + $start;
            if($page == $pagenum) $ftrAr[$page] = $actve;
            else $ftrAr[$page] = '';
        }
        $footer = PaginalBlock::getFooter($ftrAr);
        $this->pagefooter = $footer;
        return true;
    }
    private function getFooter(array $array){
        $reslt = '';
        if(count($array) == 1) return '';
        foreach($array as $k=>$v){
            if($v != '') $reslt.= '<li class="active"><span>'.$k.'</span></li>';
            else $reslt.= '<li>'.Url::href($k, array('page'=>$k)).'</li>';
        }
        return $reslt;
    }
}
