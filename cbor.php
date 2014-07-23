<?php
/*
Written by Peter O. in 2014.

Any copyright is dedicated to the Public Domain.
http://creativecommons.org/publicdomain/zero/1.0/
If you like this, you should donate to Peter O.
at: http://upokecenter.com/d/
 */
 
class CBORException extends Exception {}
 
class CBORTagged {
  var $value;
  var $tag;
  private function getTag(){
   return $this->tag;
  }
  private function getValue(){
   return $this->value;  
  }
  function CBORTagged($tag, $value){
   $this->tag=$tag;
   $this->value=$value;
  }
}

class CBORBignum {
  var $value;
  var $tag;
  private function getValue(){
   return $this->value;  
  }
  public function __toString(){
   return $this->value;
  }
  function CBORBignum($value){
   $this->value=$value."";
  }
}

class CBORSimple {
  var $value;
  private function getValue(){
   return $this->value;  
  }
  function CBORSimple($value){
   $this->value=$value;
  }
}

class CBOR {

private static function fgetb($file){
 return Ord(fgetc($file));
}

private static function fgetw($file){
 $ret=0;
 $val=fread($file,2);
 $ret|=Ord($val[1]);
 $ret|=(Ord($val[0])<<8);
 return $ret;
}

private static function fgetdw($file){
 $val=fread($file,4);
 $ret2=unpack("N",$val);
 return $ret2[1];
}
public static function read($file){
  return CBOR::readInternal($file);
}

private static function readInternal($file,$depth=0,$type=null){
 if($depth>500){
  throw new CBORException("Too deeply nested");
 }
 $type=$type==null ? CBOR::fgetb($file) : $type;
 if($type==null){
  throw new CBORException("End of file $depth");
 }
 $major=($type>>5)&0x07;
 $data=($type)&0x1F;
 $length=0;
 switch($major){
   case 0: // Positive number
     if($data<24)return $data;
     if($data==24){
       return CBOR::fgetb($file);
     }
     if($data==25){
       return CBOR::fgetw($file);
     }
     if($data==26){
       return CBOR::fgetdw($file);
     }
     if($data==27){
       $data=CBOR::fgetdw($file);
       if($data!=0)throw new CBORException("Not supported");
       $data=CBOR::fgetdw($file);
       return $data;
     }
     throw new CBORException("Invalid data");
     break;
   case 1: // Negative number
     if($data<24)return -1-$data;
     if($data==24){
       return -1-CBOR::fgetb($file);
     }
     if($data==25){
       return -1-CBOR::fgetw($file);
     }
     if($data==26){
       return -1-CBOR::fgetdw($file);
     }
     if($data==27){
       $data=CBOR::fgetdw($file);
       if($data!=0)throw new CBORException("Not supported");
       $data=CBOR::fgetdw($file);
       return -1-$data;
     }
     throw new CBORException("Invalid data");
     break;
   case 2: // Byte string
     if($data<24)return ($data==0) ? "" : fread($file,$data);
     if($data==24){
       return fread($file,CBOR::fgetb($file));
     }
     if($data==25){
       return fread($file,CBOR::fgetw($file));
     }
     if($data==26){
       return fread($file,CBOR::fgetdw($file));
     }
     if($data==27){
       $high=CBOR::fgetdw($file);
       if($high!=0){
        throw new CBORException("length bigger than supported");
       }
       $length=CBOR::fgetdw($file);
     }
     if($data==31){
       $ret=array();
       while(true){
        $b=CBOR::fgetb($file);
        if($b==255)break;
        if($b<0x40 || $b>=0x5c)throw new CBORException("Expected byte string chunk");
        array_push($ret,CBOR::readInternal($file,$depth+1,$b));
       }     
       return implode("",$ret);
     }
     throw new CBORException("Invalid data");
     break;
   case 3: // Text string
     if($data<24){
      $ret=($data==0) ? "" : fread($file,$data);
      return $ret;
     }
     if($data==24){
       return fread($file,CBOR::fgetb($file));
     }
     if($data==25){
       return fread($file,CBOR::fgetw($file));
     }
     if($data==26){
       return fread($file,CBOR::fgetdw($file));
     }
     if($data==27){
       $high=CBOR::fgetdw($file);
       if($high!=0){
        throw new CBORException("length bigger than supported");
       }
       $length=CBOR::fgetdw($file);
     }
     if($data==31){
       $ret=array();
       while(true){
        $b=CBOR::fgetb($file);
        if($b==255)break;
        if($b<0x60 || $b>=0x7c)throw new CBORException("Expected text string chunk");
        array_push($ret,CBOR::readInternal($file,$depth+1,$b));
       }     
       return implode("",$ret);
     }
     throw new CBORException("Invalid data");
     break;   
   case 4: // Array
     if($data<24)$length=$data;
     if($data==24){
       $length=CBOR::fgetb($file);
     } else if($data==25){
       $length=CBOR::fgetw($file);
     } else if($data==26){
       $length=CBOR::fgetdw($file);
     } else if($data==27){
       $high=CBOR::fgetdw($file);
       if($high!=0){
        throw new CBORException("length bigger than supported");
       }
       $length=CBOR::fgetdw($file);
     }
     if($data==31){
      $ret=array();
      while(true){
       $b=CBOR::fgetb($file);
       if($b==255)break;
       array_push($ret,CBOR::readInternal($file,$depth+1,$b));
      }     
      return $ret;
     } else if($data<28){
      $ret=array();
      for($i=0;$i<$length;$i++){
       array_push($ret,CBOR::readInternal($file,$depth+1));
      }
      return $ret;
     }
     throw new CBORException("Invalid data");
     break;
   case 5: // Map
     if($data<24)$length=$data;
     if($data==24){
       $length=CBOR::fgetb($file);
     }
     if($data==25){
       $length=CBOR::fgetw($file);
     }
     if($data==26){
       $length=CBOR::fgetdw($file);
     }
     if($data==27){
       $high=CBOR::fgetdw($file);
       if($high!=0){
        throw new CBORException("length bigger than supported");
       }
       $length=CBOR::fgetdw($file);
     }
     if($data==31){
      $ret=array();
      while(true){
       $b=CBOR::fgetb($file);
       if($b==255)break;
       $key=CBOR::readInternal($file,$depth+1,$b);
       $value=CBOR::readInternal($file,$depth+1);
       $ret[$key]=$value;
      }     
      return $ret;
     } else if($data<28){
      $ret=array();
      for($i=0;$i<$length;$i++){
       $key=CBOR::readInternal($file,$depth+1);
       $value=CBOR::readInternal($file,$depth+1);
       $ret[$key]=$value;
      }
      return $ret;
     }
     throw new CBORException("Invalid data");
     break;   
   case 6: // Tagged data
     if($data<24)$length=fread($file,$data);
     if($data==24){
       $length=CBOR::fgetb($file);
     }
     if($data==25){
       $length=CBOR::fgetw($file);
     }
     if($data==26){
       $length=CBOR::fgetdw($file);
     }
     if($data==27){
        throw new CBORException("Not supported");
     }
     if($data>=28){
       throw new CBORException("Invalid data");
     }
     return new CBORTagged($length,CBOR::readInternal($file,$depth+1));
   case 7: // Simple value
     if($data==20)return false;
     if($data==21)return true;
     if($data==22)return null;
     if($data==26){
       $data=CBOR::fgetdw($file);
       throw new CBORException("Not supported");
     } else if($data==25){
       $data=CBOR::fgetw($file);
       throw new CBORException("Not supported");
     } else if($data==27){
       throw new CBORException("Not supported");
     } else if($data==24){
       $data=CBOR::fgetb($file);
       if($data<32)throw new CBORException("Invalid data");
     }
     if($data>=28){
       throw new CBORException("Invalid data");
     }
     return new CBORSimple($data);
   default:
     throw new CBORException("Unreachable");
 }
}

}

?>