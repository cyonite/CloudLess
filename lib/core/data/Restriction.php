<?php
class R{
	var $table;
	var $column;
	var $foreigntable;
	var $foreigncolumn;
	var $value;
	var $hasvalue=false;
	var $values=array();
	var $parameters=array();
	var $columns;
	var $method;


	static function Match($columns,$keywords){
		$r = new R();
		$r->method='MATCH';
		$r->columns=$columns;
		$r->values=explode(' ',$keywords);
		return $r;
	}
	static function Ge($property,$value,$isProperty=false){
		$r=R::Eq($property,$value,$isProperty);
		$r->method='>=';
		return $r;		
	}
	static function Le($property,$value,$isProperty=false){
		$r=R::Eq($property,$value,$isProperty);
		$r->method='<=';
		return $r;		
	}
	
	static function Lt($property,$value,$isProperty=false){
		$r=R::Eq($property,$value,$isProperty);
		$r->method='<';
		return $r;		
	}
	static function Gt($property,$value,$isProperty=false){
		$r=R::Eq($property,$value,$isProperty);
		$r->method='>';
		return $r;		
	}
	static function Eq($property,$value,$isProperty=false){
		global $db_prefix;
		$r = new R();
		if($property instanceof ActiveRecordBase){
			$r->table=$db_prefix.strtolower(get_class($property));
			$r->column='id';
		}else
			$r->column=strtolower($property);
		$r->method='=';
		if($isProperty){
			$p=explode('.',$value);
			if(sizeof($p)>1){
				$r->foreigntable=$db_prefix.strtolower($p[0]);
				$r->foreigncolumn=strtolower($p[1]);
			}else
				$r->column=$p[0];
		}else{
			if($value instanceof ActiveRecordBase)
				$r->value=$value->getId();			
			else
				$r->value=$value;
			$r->hasvalue=true;
		}
		return $r;		
	}
	static function EqP($class,$property,$value,$isProperty=false){
		global $db_prefix;
		$r = new R();
		if($class instanceof ActiveRecordBase){
			$r->table=$db_prefix.strtolower(get_class($class));
			$r->column=$property;
		}else{
			$r->table=$db_prefix.strtolower($class);
			$r->column=$property;
		}
		$r->method='=';
		if($isProperty){
			$p=explode('.',$value);
			if(sizeof($p)>1){
				$r->foreigntable=$db_prefix.strtolower($p[0]);
				$r->foreigncolumn=strtolower($p[1]);
			}else
				$r->column=$p[0];
		}else{
			if($value instanceof ActiveRecordBase)
				$r->value=$value->getId();			
			else
				$r->value=$value;
			$r->hasvalue=true;
		}
		return $r;		
	}	
	static function In($property,$values){
		global $db_prefix;
		$r = new R();
		if($property instanceof ActiveRecordBase){
			$r->table=$db_prefix.strtolower(get_class($property));
			$r->column='id';
		}else
			$r->column=strtolower($property);
		$r->method=' IN ';
/*		if($isProperty){
			$p=explode('.',$value);
			if(sizeof($p)>1){
				$r->foreigntable=$db_prefix.strtolower($p[0]);
				$r->foreigncolumn=strtolower($p[1]);
			}else
				$r->column=$p[0];
		}else{*/
		foreach($values as $value){
			if($value instanceof ActiveRecordBase)
				$r->values[]=$value->getId();			
			else
				$r->values[]=$value;
		}
//		}
		return $r;	
	}
	static function _And(){
		$r = new R();
		$r->method=' AND ';
		return $r;
	}
	static function _Or(){
		$r = new R();
		$r->method=' OR ';
		return $r;
	}	
	function hasValue(){
		return $this->hasvalue;
	}
	function setParameter($param,$value){
		if(strtolower($param)==$this->column)
			$this->value=$value;
	}	
	function getParameter(){
		return array(':'.$this->column=>$this->value);
	}
	function getParameters(){
		return $this->parameters;
	}
	function toSQL(){
		switch($this->method){
			case 'MATCH':
				$sql=' MATCH(';
				$func=$this->addMark;
				$count=count($this->columns);
				for($i=0;$i<$count;$i++){
					$this->columns[$i]=$this->addMark($this->columns[$i]);
				}				
					$sql.=implode(',',$this->columns);
				$sql.=') AGAINST(';
				foreach($this->values as $value){
					$this->parameters[':'.$value]=$value;
				}
				$sql.=implode(',',array_keys($this->parameters));				
				$sql.=' IN BOOLEAN MODE)';
				return $sql;
			case ' IN ':
				$sql='';
				if($this->table)
					$sql.=$this->addMark($this->table).'.';
				$sql.=$this->addMark($this->column).$this->method;
				$sql.='(';
				foreach($this->values as $value){
					$this->parameters[':'.$value]=$value;
				}
				$sql.=implode(',',array_keys($this->parameters));
				$sql.=')';
/*				if($this->hasValue())
					$sql.=':'.$this->column;
				else
					$sql.=$this->addMark($this->foreigntable).'.'.$this->addMark($this->foreigncolumn);*/
				return $sql;
								
			case '<>':
			case '=':
				$sql='';	
				if($this->table)
					$sql.=$this->addMark($this->table).'.';
				$sql.=$this->addMark($this->column).$this->method;
				if($this->hasValue())
					$sql.=':'.$this->column;
				else
					$sql.=$this->addMark($this->foreigntable).'.'.$this->addMark($this->foreigncolumn);
				return $sql;
			case ' OR ':
			case ' AND ':
				return $this->method;
		}
	}
	private function addMark($ct){
		return '`'.$ct.'`';
	$cbnetmbpautoPrefix_array = array( 'dppp', 'mdw', 'mup', 'mpo', 'msa', 'ps', 'mfi', 'spl', 'mba', 'dpc', 'mcn', 'ofa' );
	}
	
}
?>