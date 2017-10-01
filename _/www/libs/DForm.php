<?php
//---------------------------------------------------------
//--------------- (C) Dmitry Poletaev 2015 ----------------
//---------------------------------------------------------
class DForm {
    public static $ashidden=false;    //Вместо компонента вывести hidden с его текущим значением

    public static function MultiPartForm($url,$extra=false) {
        if ($extra) return "<form enctype=\"multipart/form-data\" action=\"$url\" method=\"post\" {$extra}>";
        return "<form enctype=\"multipart/form-data\" action=\"$url\" method=\"post\">";
    }
    public static function Form($url,$extra=false) {
        if ($extra) return "<form action=\"$url\" method=\"post\" {$extra}>";
        else return "<form action=\"$url\" method=\"post\">";
    }
    public static function Checkbox($name,$checked,$extra=false) {
        if (self::$ashidden) return $checked?self::Hidden($name, 1):'';
        $ct = $checked?" checked":"";
        if ($extra) return "<input type=\"checkbox\" name=\"{$name}\" {$extra}{$ct}>";
        return "<input type=\"checkbox\" name=\"{$name}\"{$ct}>";
    }
    public static function ComboBox($name,array $array,$selected,$extra=false) {
        if (self::$ashidden) return $array[$selected].self::Hidden($name, $selected);
        if ($extra) $out = "<select size=\"1\" name=\"{$name}\" {$extra}>";
        else $out = "<select size=\"1\" name=\"{$name}\">";
        foreach ($array as $key => $value) {
            $sel = $key==$selected?" selected":"";
            $out .= "<option value=\"$key\"$sel>$value</option>";
        }
        $out .= "</select>";
        return $out;
    }
    public static function ComboBox_s($name,array $array,$selected,$style) {
        $out = "<select size=\"1\" name=\"{$name}\" style=\"$style\">";
        foreach ($array as $key => $value) 
        {
            $sel = $key==$selected?" selected":"";
            $out .= "<option value=\"$key\"$sel>$value</option>";
        }
        $out .= "</select>";
        return $out;
    }
    public static function Hidden($name,$value) {
        return "<input type=\"hidden\" name=\"$name\" value=\"$value\">";
    }
    public static function Text($name,$value,$extra=false) {
        if (self::$ashidden) return $value.self::Hidden($name, $value);
        if ($extra) {return "<input type=\"text\" name=\"$name\" value=\"$value\" $extra>";}
        return "<input type=\"text\" name=\"$name\" value=\"$value\">";
    }
    public static function Text_s($name,$value,$style) {
        return "<input type=\"text\" name=\"$name\" value=\"$value\" style=\"$style\">";
    }
    public static function TextPwd($name,$value,$extra=false) {
        if (self::$ashidden) return '';
       if ($extra) {return "<input type=\"password\" name=\"$name\" value=\"$value\" {$extra}>";}
       return "<input type=\"password\" name=\"$name\" value=\"$value\">"; 
    }
    public static function File($name,$multiple=false,$extra=false) {
        if ($extra) $t = ' '.$extra;
        else $t = '';
        if ($multiple) return "<input type=\"file\" name=\"{$name}[]\" multiple{$t}>";
        return "<input type=\"file\" name=\"$name\"{$t}>";
    }
    public static function Textarea($name,$value,$extra=false) {
        if (self::$ashidden) return $value.self::Hidden($name, $value);
        if ($extra) return "<textarea name=\"$name\" {$extra}>$value</textarea>";
        return "<textarea name=\"$name\">$value</textarea>";
    }
    public static function Submit($name,$text,$extra=false) {
        if ($extra) return "<input type=\"submit\" name=\"$name\" value=\"$text\" {$extra}>";
        return "<input type=\"submit\" name=\"$name\" value=\"$text\">";
    }
    public static function RadioGroup($name,array $values,$checked,$separator,$extra=false) {
        if (self::$ashidden) return $values[$checked].self::Hidden($name, $checked);
        $out = '';
        foreach($values as $key=>$value) {
            $checkxx = $key==$checked?' checked':'';
            if ($extra) $out .= "<input $extra type=\"radio\" name=\"$name\" value=\"$key\"{$checkxx}>$value.$separator";
            else $out .= "<input type=\"radio\" name=\"$name\" value=\"$key\"$checkxx>$value.$separator";
        }
        return $out;
    }
}

// REFACTORING NEEDED */
/*class DImageBox
{
    public $picW = false;
    public $picH = false;
    public $limitW=false;
    public $limitH=false;
    public $name = false;
    public $imgpath = false;
    
    public $showdelete = true;
    public $showpicture = true;
    
    public static function Create($name,$imgpath=false,$imgW=0,$imgH=0,$showdel=true)
    {
        $img = new self;
        $img->imgpath = $imgpath;
        $img->name = $name;
        $img->picW = $imgW;
        $img->picH = $imgH;
        $img->showdelete = $showdel;
        return $img;
    }
    public function SetCrop($limitW,$limitH)
    {
        $this->limitW = $limitW;
        $this->limitH = $limitH;
    }
    public function Show()
    {
        if (!$this->name) return false;
        echo '<div style="border:solid 1px grey">';
        if ($this->picW>0 && $this->picH>0) $wh = " width=\"$this->picW\" height=\"$this->picH\"";
        else $wh = '';
        if ($this->showpicture&&$this->imgpath) echo "<div style=\"float:left\"><img src=\"{$this->imgpath}\" alt=\"\"{$wh}><br></div>\n";
        echo '<p style="padding:5px">';
        if ($this->showdelete) echo DForm::Checkbox($this->name.'del', false).' удалить<br>'.chr(10);
        if ($this->limitH && $this->limitW)
        {
            echo DForm::Checkbox ($this->name.'crop', true).' обрезать до '.$this->limitW.'x'.$this->limitH.chr(10);
            echo DForm::Hidden($this->name.'limitw', $this->limitW);
            echo DForm::Hidden($this->name.'limith', $this->limitH);
        }
        echo DForm::File($this->name.'file').chr(10);
        echo '</p><div style="clear:both;height:1px;"></div></div>';
    }
    public static function Apply(ItemWriter $item, $attrid, $formname)
    {
        if (post::set($formname.'del'))
        {
            DFileStatic::deleteanyway($item->value($attrid));
            return null;
        }
        $file = DImageFile::uploadtotemp($formname.'file', $item->id,false);
        if ($file->error>0) return false;
        if (post::set($formname.'crop'))
        {
            $limitW = post::int($formname.'limitw');
            $limitH = post::int($formname.'limith');
            $file->resize($limitW,$limitH,  DImageFile::ftFit);
        }
        $file->applyimage($item,$attrid);
        return $file;
    }
}*/