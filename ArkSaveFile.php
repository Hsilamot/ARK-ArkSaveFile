<?php

namespace Tomalish\ARK\ArkSaveFile;

class ArkFileHeader {
	public $BinaryDataOffset; //Offset to the binary table from the start of the file.
	public $Unknown; //Unknown
	public $NameTableOffset; //The offset to the name table from the start of the file.
	public $PropertiesBlockOffset; //The offset to the properties block from the start of the file.
	public $GameTime; //In game clock.
	public $Unknown2; //Unknown
	public function __construct($data) {
		if (strlen($data)!=24) {
			trigger_error('ArkFileHeader should be 24 bytes long',E_USER_NOTICE);
			return false;
		}
		$this->BinaryDataOffset = unpack('l',substr($data,0,4));
		$this->BinaryDataOffset = $this->BinaryDataOffset[1];
		$data = substr($data,4);
		$this->Unknown = unpack('l',substr($data,0,4));
		$this->Unknown = $this->Unknown[1];
		$data = substr($data,4);
		$this->NameTableOffset = unpack('l',substr($data,0,4));
		$this->NameTableOffset = $this->NameTableOffset[1];
		$data = substr($data,4);
		$this->PropertiesBlockOffset = unpack('l',substr($data,0,4));
		$this->PropertiesBlockOffset = $this->PropertiesBlockOffset[1];
		$data = substr($data,4);
		$this->GameTime = unpack('f',substr($data,0,4));
		$this->GameTime = $this->GameTime[1];
		$data = substr($data,4);
		$this->Unknown2 = unpack('l',substr($data,0,4));
		$this->Unknown2 = $this->Unknown2[1];
		$data = substr($data,4);
	}
}

class ArkString {
	public $string;
	public function __construct($file) {
		$length = fread($file,4);
		$length = unpack('l',$length);
		$length = $length[1];
		if ($length>0) {
			$this->string = fread($file,$length);
			if (ord(substr($this->string,-1))==0) {
				$this->string = substr($this->string,0,-1);
			}
		} else {
			$this->string='';
		}
		return $this->string;
	}
}

class BinaryDataNames {
	public $array;
	public function __construct($file) {
		$this->array = array();
		$length = fread($file,4);
		$length = unpack('l',$length);
		$length = $length[1];
		while ($length>0) {
			$string =  new String($file);
			$this->array[] = $string->string;
			$length--;
		}
	}
}

class BinaryClassNameTable {
	public $array;
	public function __construct($file) {
		$this->array	= array();
		$this->array[]	= 'INVALID';
		$length = fread($file,4);
		$length = unpack('l',$length);
		$length = $length[1];
		while ($length>0) {
			$string =  new String($file);
			$this->array[] = $string->string;
			$length--;
		}
	}
}

class ArkClassName {
	public $ID;
	public $Name;
	public $Index;
	public function __construct($data,$BinaryClassNameTable) {
		if (strlen($data)!=8) {
			trigger_error('ArkClassName should be 8 bytes long',E_USER_NOTICE);
			return false;
		}
		$this->ID = unpack('l',substr($data,0,4));
		$this->ID = $this->ID[1];
		$data = substr($data,4);
		$this->Index = unpack('l',substr($data,0,4));
		$this->Index = $this->Index[1];
		$data = substr($data,4);
		if (isset($BinaryClassNameTable->array[$this->ID])) {
			$this->Name = $BinaryClassNameTable->array[$this->ID];
		} else {
			$this->Name = 'Error: Not found in table';
		}
	}
}

class Boolean {
	public $value;
	public function __construct($data) {
		$check = unpack('l',substr($data,0,4));
		$check = $check[1];
		if ($check===1) {
			$this->value = true;
		} elseif ($check===0) {
			$this->value = false;
		} else {
			trigger_error('Invalud boolean object created',E_USER_NOTICE);
			return false;
		}
	}
}

class ArkLocationData {
	public $XPosition;
	public $YPosition;
	public $ZPosition;
	public $Pitch;
	public $Yaw;
	public $Roll;
	public function __construct($data) {
		if (strlen($data)!=24) {
			trigger_error('ArkLocationData should be 24 bytes long',E_USER_NOTICE);
			return false;
		}
		$this->XPosition = unpack('f',substr($data,0,4));
		$this->XPosition = $this->XPosition[1];
		$data = substr($data,4);
		$this->YPosition = unpack('f',substr($data,0,4));
		$this->YPosition = $this->YPosition[1];
		$data = substr($data,4);
		$this->ZPosition = unpack('f',substr($data,0,4));
		$this->ZPosition = $this->ZPosition[1];
		$data = substr($data,4);
		$this->Pitch = unpack('f',substr($data,0,4));
		$this->Pitch = $this->Pitch[1];
		$data = substr($data,4);
		$this->Yaw = unpack('f',substr($data,0,4));
		$this->Yaw = $this->Yaw[1];
		$data = substr($data,4);
		$this->Roll = unpack('f',substr($data,0,4));
		$this->Roll = $this->Roll[1];
		$data = substr($data,4);
	}
}

class GameObjectProperties {
	public $array;
	public function __construct($file,$PropertiesBlockOffset,$PropertiesOffset) {
		$this->array = array();
		$current = ftell($file);
		fseek($file,$PropertiesBlockOffset+$PropertiesOffset);
		$length = fread($file,4);
		$length = unpack('l',$length);
		$length = $length[1];
		#echo 'leer en = '.($PropertiesBlockOffset+$PropertiesOffset).chr(10);
		#while ($length>0) {
		#	$string = new String($file);
		#	$this->array[] = $string->string;
		#	$length--;
		#}
		fseek($file,$current);
		#var_dump($this->array);
	}
}

class GameObjectBase {
	public $GUID; //The (overkill) GUID for this object. Appears not to be unique!
	public $ClassName; //The classname for this GameObject.
	public $IsItem; //Is this an item?
	public $ArkClassNameArrayLength; // Length of the upcoming ark class name array.
	public $ArkClassName; 
	public $Unknown; // Some unknown boolean.
	public $Unknown2; // Some unknown integer.
	public $LocationDataExist; // Check if the location data vector exists.
	public $PositionData; // The location data ONLY if the above boolean was true.
	public $PropertiesOffset; // The offset from the Properties Block Offset location in the header.
	public $Properties; // The offset from the Properties Block Offset location in the header.
	public $Unknown3; // An integer that always appears to be 0.
	public function __construct($file,$BinaryClassNameTable,$PropertiesBlockOffset) {
		$this->ArkClassName = array();
		$this->Properties = array();
		$GUID_buffer = fread($file,16);
		$GUID = '';
		while ($GUID_buffer) {
			$GUID .= str_pad(bin2hex(substr($GUID_buffer,-1)),2,'0',STR_PAD_LEFT);
			$GUID_buffer = substr($GUID_buffer,0,-1);
		}
		$this->GUID = $GUID;
		$this->ClassName = new ArkClassName(fread($file,8),$BinaryClassNameTable);
		$this->IsItem = new Boolean(fread($file,4));
		$this->IsItem = $this->IsItem->value;
		#$this->ArkClassName = fread($file,4);
		$this->ArkClassNameArrayLength = fread($file,4);
		$this->ArkClassNameArrayLength = unpack('l',$this->ArkClassNameArrayLength);
		$this->ArkClassNameArrayLength = $this->ArkClassNameArrayLength[1];
		$contador = $this->ArkClassNameArrayLength;
		while ($contador>0) {
			$this->ArkClassName[] = new ArkClassName(fread($file,8),$BinaryClassNameTable);
			$contador--;
		}
		$this->Unknown = new Boolean(fread($file,4));
		$this->Unknown = $this->Unknown->value;
		$this->Unknown2 = fread($file,4);
		$this->Unknown2 = unpack('l',$this->Unknown2);
		$this->Unknown2 = $this->Unknown2[1];
		$this->LocationDataExist = new Boolean(fread($file,4));
		$this->LocationDataExist = $this->LocationDataExist->value;
		if ($this->LocationDataExist) {
			$this->PositionData = new ArkLocationData(fread($file,24));
		}
		$this->PropertiesOffset = fread($file,4);
		$this->PropertiesOffset = unpack('l',$this->PropertiesOffset);
		$this->PropertiesOffset = $this->PropertiesOffset[1];

		$this->Properties = array();
		$this->Properties = new GameObjectProperties($file,$PropertiesBlockOffset,$this->PropertiesOffset);

		$this->Unknown3 = fread($file,4);
		$this->Unknown3 = unpack('l',$this->Unknown3);
		$this->Unknown3 = $this->Unknown3[1];
	}
}

class EmbededBinaryData {
	public $array;
	public function __construct($file) {
		$this->array	= array();
		$this->array[]	= 'INVALID';
		$length = fread($file,4);
		$length = unpack('l',$length);
		$length = $length[1];
		while ($length>0) {
			$length--;
		}
	}
}

class UnknownData {
	public $array;
	public function __construct($file) {
		$this->array	= array();
		$this->array[]	= 'INVALID';
		$length = fread($file,4);
		$length = unpack('l',$length);
		$length = $length[1];
		// TODO: implementar correctamente la lecutra del siguiente array
		while ($length>0) {
			$Flags = fread($file,4);
			$Flags = unpack('l',$Flags);
			$Flags = $Flags[1];
			$ObjectCount = fread($file,4);
			$ObjectCount = unpack('l',$ObjectCount);
			$ObjectCount = $ObjectCount[1];
			$string = new String($file);
			$this->array[] = $string->string;
			$length--;
		}
	}
}

class GameObject {
	public $array;
	public function __construct($file,$BinaryClassNameTable,$PropertiesBlockOffset) {
		$this->array = array();
		$length = fread($file,4);
		$length = unpack('l',$length);
		$length = $length[1];
		#while ($length>0) {
		#	$objecto = new GameObjectBase($file,$BinaryClassNameTable,$PropertiesBlockOffset);
		#	$this->array[] = $objecto;
		#	#$this->array[] = new GameObjectBase($file,$BinaryClassNameTable);
		#	$length--;
		#}
		for ($i=0;$i<1000;$i++) {
			$objecto = new GameObjectBase($file,$BinaryClassNameTable,$PropertiesBlockOffset);
			$this->array[] = $objecto;
			#$this->array[] = new GameObjectBase($file,$BinaryClassNameTable);
			$length--;
		}
	}
}

class ArkFile {
	public $saveVersion;
	public $gameTime;
	public function __construct($filename) {
		// Open file handler
		$file = fopen($filename,'r');
		// First 2 Bytes of the file are the file version
		$version = fread($file,2);
		$version = unpack('s',$version);
		$version = $version[1];
		if ($version!=9) {
			trigger_error('Unknown version of file expecting 9, got '.$version,E_USER_NOTICE);
			return false;
		}
		$this->saveVersion = $version;
		// Next we read the File header wich consists of 24 Bytes of data
		$ArkFileHeader = new ArkFileHeader(fread($file,24));
		$this->gameTime = $ArkFileHeader->GameTime;
		$this->saveCount = 0;
		//Now we get the Binary Data Names which is a variable length
		$BinaryDataNames = new BinaryDataNames($file);

		//Now we save the current file position as we will continue reading the file from this position
		$currentposition = ftell($file);
		//Move the pointer to the NameTableOffset
		fseek($file,$ArkFileHeader->NameTableOffset);
		//Read the ClassNameTable
		$BinaryClassNameTable = new BinaryClassNameTable($file);
		//Move the pointer to the Previously saved position so we can continue reading the file
		fseek($file,$currentposition);
		//Now we Read the Embeded Binary Data
		$EmbededBinaryData = new EmbededBinaryData($file);
		//Now we Read some Unknown Data
		$UnknownData = new UnknownData($file);
		//Now we can read the GameObject data
		$GameObject = new GameObject($file,$BinaryClassNameTable,$ArkFileHeader->PropertiesBlockOffset);
		foreach ($GameObject->array as $item) {
			echo $item->ClassName->Name;
			echo ' '.$item->ArkClassNameArrayLength;
			if ($item->LocationDataExist) {
				echo ' '.$item->PositionData->XPosition;
				echo ' '.$item->PositionData->YPosition;
				echo ' '.$item->PositionData->ZPosition;
			}
			echo chr(13).chr(10);
		}
		#for ($i=0;$i<count($GameObject->array);$i++) {
			#echo $GameObject->array[$i]->ClassName->Name.chr(10);
			#var_dump($GameObject->array[$i]);
		#}
	}
}

$save = new ArkFile('extinction.ark');

/*
$file = fopen('TheIsland.ark','r');
$data = fread($file,1024);
#echo $data;
while ($data) {
	echo dechex(ord(substr($data,0,1))).' ';
	$data = substr($data,1);
}
fclose($file);