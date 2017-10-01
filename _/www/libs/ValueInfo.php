<?php
class ValueInfo
{
    const ffEditable = 0x01;
    const ffDontShow = 0x02;
    const ffConfirm  = 0x04;
    //public $id;
    public $type;
    public $textname;
    public $formname;
    public $defaultvalue;
    public $errortext;
    public $validator;
    
    public static function create($type,$textname,$formname,$defaultvalue=0,$errortext=false,$validator=false) {
        $x = new self;
        $x->type = $type;
        $x->textname = $textname;
        $x->formname = $formname;
        $x->defaultvalue = $defaultvalue;
        $x->errortext = $errortext;
        $x->validator = $validator;
        return $x;
    }
}


function printformfields($item,array $values,$extra=false,$place=false) { /*@var $item Item*/
    $t = '';
    $array = array();
    foreach($values as $id=>$flags) {
        if ($id==-1){
            $array = array('Активен',DForm::Checkbox('active', $item->checkflags(Item::fEnabled)));
            continue;
        }
        $subAr = array();
        $vi = $item->gettypesinfo($id);    /*@var $vi ValueInfo */
        if ($vi){
            array_push($subAr,$vi->textname);
            if (($flags&ValueInfo::ffDontShow)>0) $val='';
            else $val = $item->value($id);
            if(count($place)>0) $plHld = 'placeholder="'.$place[$id].'"';
            if (($flags&ValueInfo::ffEditable)>0) array_push($subAr,DForm::Text($vi->formname,$val,$plHld));
            else array_push($subAr,$val);
            if (($flags&ValueInfo::ffConfirm)>0) array_push($subAr,DForm::Checkbox ($vi->formname.'ch', false).'Изменить</td>');
            else array_push($subAr,DForm::Hidden($vi->formname.'ch', 1));
        }
        else array_push($subAr,array('Неизвестно'.$id,' '));
    array_push($array,$subAr);
    }
    if(!$extra){
        if(count($array)==1 && count($subAr)==0){
            foreach($array as $a) $t.='<td>'.$a.'</td>';
            $t = '<table><tr>'.$t.'</tr></table>';
        }else{
            foreach($array as $subAr){
                $t.='<tr>';
                foreach($subAr as $a) $t.='<td>'.$a.'</td>';
                $t.='</tr>';
            }
            $t = '<table>'.$t.'</table>';
        }
    }elseif($extra='span'){
        foreach($array as $subAr){
            foreach($subAr as $k=>$a){
                if($k==0) $t.='<span class="field">'.$a.'</span><br />';
                else $t.= $a.'<br />';
            }
        }
    }
    if($item->isvalid()) $t = $t.DForm::Hidden('id', $item->id);
    return $t;
}

function _applyformpost($postname,$type) {
    if (!isset($_POST[$postname])) return false;
    switch($type) {
        case VT::Int: return intval($_POST[$postname]);
        case VT::Double: return doubleval($_POST[$postname]);
        case VT::String256:
        case VT::String64:
        case VT::StringLong: return $_POST[$postname];
    }
}

function applyform($classname, array $values) {
    $errors = array();
    if (isset($_POST['id'])) $item = call_user_func(array($classname,'read'),intval($_POST['id']));
    else $item = call_user_func(array($classname,'create')); /*@var $item Item*/
    foreach($values as $value) {
        if ($value==-1) {
            $item->setflags(Item::fEnabled,isset($_POST['active']));
            continue;
        }
        $vi = $item->gettypesinfo($value);    /*@var $vi ValueInfo */
        if (!$vi) continue;
        if (!isset($_POST[$vi->formname.'ch'])) continue;
        $val = _applyformpost($vi->formname, $vi->type);
        if ($vi->defaultvalue && !$val) $val = $vi->defaultvalue;
        if ($vi->validator) $val2 = call_user_func($vi->validator,$val);
        else $val2 = $val;
        if ($vi->errortext && !$val2) {$errors[$value] = $vi->errortext;$val2 = $val;}
        $item->setvalue($value, $vi->type, $val2);
    }
    return array($item,$errors);
}