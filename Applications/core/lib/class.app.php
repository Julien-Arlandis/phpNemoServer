<?php
class App
{
	function __construct()
	{

	}
	
	function setProjection($key)
    {
    	/*
    	Syntaxe pour extraire un tableau :
    	Data.References:3,5 => renvoie les cellules 3, 4 et 5 => $slice: [ 3, 5 ]
    	Data.References:2,N => renvoie les cellules 2 à N
    	Data.References:2,N-5 => renvoie les cellules de 2 à N-5
    	Data.References:N-2,N => renvoie les cellules de N-2 à N => $slice:-2
    	*/
        $key = explode(':', $key);
        $res = array();
    
        if( count($key) != 2) {
            return 1;
        }else{
            $key = explode(',', $key[1]);
            if( count($key) <= 2) {
                foreach($key as $ind => $value) {
                    if(is_numeric($value)) {
                        $key[$ind] = $value;
                    }else{
                        if($key[$ind] == 'N') {
                            $key[$ind] = 'end';
                        } else {
                            $attr = explode('N-', $key[$ind]);
                            if( count($attr) == 2 ) {
                                $key[$ind] = -$attr[1];
                            }else{
                                $key[$ind] = 0;
                            }
                        }
                    }
                    array_push($res, $key[$ind]);
                }
                if(count($res) == 1) {
                    if( is_numeric($res[0]) ) { return(array('$slice'=>array( $res[0]-1, 1) )); }
                    elseif($res[0] == 'end') { return(array('$slice'=>-1)); }
                    else { return(array('$slice'=>array($res[0], 1))); }
                }else{
                    if( is_numeric($res[0]) ) {
                            if( is_numeric($res[1]) && $res[1] > 0) { return(array('$slice'=>array( $res[0]-1, $res[1]-1) )); }
                            elseif($res[1] == 'end') { return(array('$slice'=>array( $res[0]-1, 999999) )); }
                            else { return(array('$slice'=>array( $res[0]-1, 999999) )); }
                    }else{
                        if( $res[0] == 'end' ) {
                            return 1;
                        }else{
                            if( is_numeric($res[1]) && $res[1] > 0 ) { return(array('$slice'=>$res[0] )); }
                            elseif($res[1] == 'end') { return(array('$slice'=>$res[0])); }
                            else { return(array('$slice'=>array($res[0], $res[1]-$res[0]) )); }
                        }
                    }
                }
            }else{
                return 1;
            }
        }
    }
	
}
