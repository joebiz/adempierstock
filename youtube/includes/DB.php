<?php
class DB {
	public $tableName = 'tmp_videos';
	public $tableNameCatalogVideo = 'tsht_catalog_product_entity_text'; 
	//public $dbHost = $dbHost;
	//public $dbUsername =$dbUsername; 
	//public $dbPassword =$dbPassword;
	//public $dbName =$dbName;
	protected $db;
                function __construct($dbHost,$dbUsername, $dbPassword, $dbName){
		//Database configuration
		//$dbHost = 'localhost'; 
		//$dbUsername = 'mldemo2_clients';
		//$dbPassword = '123456a2';
		//$dbName = 'mldemo2_itshot-erp2'; 
//		$this->db->$dbHost     = $dbHost;
//		$this->db->$dbUsername = $dbUsername;
//		$this->db->$dbHost     = $dbPassword;
//		$this->db->$dbHost     = $dbName;
		//Connect database
		$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);
		if($conn->connect_error){
			die("Failed to connect with MySQL: " . $conn->connect_error);
		}else{
			$this->db = $conn;
		}
	}
	
	function getLastRow(){
		$sql = "SELECT * FROM $this->tableName ORDER BY video_id DESC LIMIT 1";
		$query = $this->db->query($sql);
		$result = $query->fetch_assoc();
		if($result){
			return $result;
		}else{
			return false;
		}
	} 
	
	function insert($videoTitle,$videoDesc,$videoTags,$videoFilePath,$productId,$sku,$video_type){
		$sql = "INSERT INTO $this->tableName (video_title,video_description,video_tags,video_path,productid,sku,video_type,youtube_video_id) VALUES('".addslashes($videoTitle)."','".addslashes($videoDesc)."','".addslashes($videoTags)."','".addslashes($videoFilePath)."','".$productId."','".$sku."','".$video_type."','')";  
		 //echo "<br>";
		if($this->db->query($sql)){
			//echo "sssssssssssssssssssssss".$this->db->insert_id;die;
			return $this->db->insert_id;
			
		}else{
			return $this->db->error;
		}
	}
	
	function update($video_id,$youtube_video_id){
		$sql = "UPDATE  $this->tableName SET youtube_video_id = '".$youtube_video_id."' WHERE video_id = ".$video_id;
		$this->db->query($sql);
		return true;
	}
	
	function updateYoutubecodeOld($productId,$youtubeCode){
		$sql = "UPDATE  $this->tableNameCatalogVideo SET youtube_code = '".$youtubeCode."' WHERE product_id = ".$productId;
		$this->db->query($sql);
		return true;
	}
	
	function updateYoutubecode($productId,$youtubeCode){
		$videoCode = "https://www.youtube.com/watch?v=".$youtubeCode;
		 $sql = "UPDATE  $this->tableNameCatalogVideo SET value = '".$videoCode."' WHERE attribute_id=2046 AND entity_id = ".$productId;
		$this->db->query($sql);
		return true;
	}
}
?>
