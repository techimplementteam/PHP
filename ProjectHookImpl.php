<?php

/**
 * ProjectHookImpl
 *
 * All the logic hooks related with project module is defined in this class.
 */
class ProjectHookImpl
{
	public function beforeSave($bean, $event, $arguments) 
	{
		$bean->orig_fetched_row = $bean->fetched_row;
		$this->generateJobID($bean);
		$this->updateName($bean);
		$this->calculateJobValueSports($bean);
	}
	public function afterRelationshipAdd($bean, $event, $arguments) 
	{
		//$this->updateQuoteTitle($bean, $arguments);
	}
	public function afterSave($bean, $event, $arguments){
		$this->calculateJobValueOthers($bean);
		$this->createFTPFolder($bean);
		$this->createGoogleGalleryUser($bean);
	}

	function generateJobID($bean)
	{
	    if (empty($bean->jobid_c)) {
    	    $base = '0000';
    	    $currentYear = date("y");
	        $maxNumber = $this->getMaxNum("SELECT max(jobid_c) as jobid_c FROM project_cstm", $bean);
			if ($maxNumber && !is_null($maxNumber)) {
		        $year = substr($maxNumber, 0, 2);	
		        if($currentYear == $year) {
	        		$bean->jobid_c = ++$maxNumber;
		        } elseif ($currentYear > $year) {
		        	$jobid = $currentYear.$base;
		        	$jobid++;
		        	$bean->jobid_c = $jobid;
		        } elseif ($currentYear < $year) {
	        		$query = 'SELECT MAX(jobid_c) as jobid_c FROM project_cstm WHERE SUBSTRING(CONVERT(jobid_c, CHAR), 1, 2) = '.$currentYear;
	        		$maxNumber = $this->getMaxNum($query, $bean);
	        		if(!empty($maxNumber) && !is_null($maxNumber)) {
	        			$bean->jobid_c = ++$maxNumber;
	        		} else {
		        		$bean->jobid_c = $currentYear.$base;
	        		}
		        }
			} else {			
		        $bean->jobid_c = $currentYear.$base;
			}
    	} 
	}

	function createGoogleGalleryUser($bean)
	{
		if($bean->orig_fetched_row['id'] == '') {
			if(!empty($bean->admin_gallery_password_c)
				&& !empty($bean->client_gallery_password_c)) {
				$this->createUsers($bean);
			}
		} else {
			if($bean->admin_gallery_password_c != $bean->orig_fetched_row['admin_gallery_password_c']
				&& !empty($bean->orig_fetched_row['admin_gallery_password_c'])) {
				$this->createUsers($bean, true);
			} else if($bean->client_gallery_password_c != $bean->orig_fetched_row['client_gallery_password_c']
				&& !empty($bean->orig_fetched_row['client_gallery_password_c'])) {
				$this->createUsers($bean, true);
			}
		}
	}

	private function createUsers($bean, $isUpdate = false)
	{
		$response = $this->checkUser($bean);
		$GLOBALS['log']->fatal('checkUser : ', $response);
		if($response['response'] == 'no') {
			$category = array(
				'Event' => 'events',
				'Formal' => 'formals',
				'Sports' => 'sports'
			);
			$title1 = "Project: " .$bean->name;
			$title2 = $bean->name;
			$user1 = $bean->client_gallery_password_c;
			$user2 = $bean->admin_gallery_password_c;
			$data = array(
				"gallery_type" => $category[$bean->jobcategory_c],
				"EP_PID" => $bean->id,
				"title1" => $title1,
				"title2" => $title2,
				"user_login1" => $user1,
				"user_pass1" => $user1,
				"user_login2" => $user2,
				"user_pass2" => $user2
			);

			if(in_array($bean->jobcategory_c, array_keys($category) )) {
				$url = "http://www.eventphotography.com/api_google/api.php";
				$response = $this->makeCall(array('url'=>$url, 'data'=>$data));
				if(isset($response['status']) && $response['status'] == 'success') {
					$adminURL = 'http://www.eventphotography.com/gallery/auto-login/?p2=a_'.$bean->id;
					$clientURL = 'http://www.eventphotography.com/gallery/auto-login/?p2=s_'.$bean->id;
					$GLOBALS['db']->query("UPDATE project_cstm set googledriveadminlink_c = '".$adminURL."', googledriveclientlink_c = '".$clientURL."' where id_c = '".$bean->id."'");
				}
			}
		}
	}

	private function checkUser($bean)
	{
		$url ='http://www.eventphotography.com/api_wordpress/check_user.php';
		$data = array(
			'pid' => $bean->id,
		);
		return $this->makeCall(array('url'=>$url, 'data'=>$data));
	}

	private function makeCall($params = array())
	{
		if(empty($params)) {
			return;
		}

		$url = $params['url'];
		$headers = [
		    'Content-Type: application/x-www-form-urlencoded',
		];
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_POST, 1);//0 for a get request
		curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query($params['data']));
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		$result = curl_exec($ch);
		if($result === FALSE) {
			$GLOBALS['log']->fatal('CURL Failed: ', curl_error($ch));
			$response = array('status'=>'fail');
		}else{
			$response = array('status'=>'success', 'response' => $result);
		}
		curl_close ($ch);
		return $response;
	}

	function getMaxNum($sql, $bean)
	{
        $sql_lock = "Lock TABLES project_cstm READ";
        $bean->db->query($sql_lock); 
        $result = $bean->db->query($sql);
        
        $sql_unlock = "UNLOCK TABLES";
        $bean->db->query($sql_unlock);
        
        $row = $bean->db->fetchByAssoc($result);
        return $row['jobid_c'];
	}

	function updateName($bean)
	{
		$name = explode(':', $bean->name);
		$bean->name = $bean->jobid_c . ': ' . end($name);
	}

	function updateQuoteTitle($bean, $arguments)
	{
		if ($arguments['relationship'] == 'aos_quotes_project') {
			$quoteBean = $arguments['related_bean'];
			$quoteBean->name = $bean->name;
			$quoteBean->save();
		}
	}

	function calculateJobValueSports($bean){
		if ($bean->jobcategory_c == "Sports") {
			if ($bean->servicetype_c == "Clubpostpaid" || $bean->servicetype_c == "Onlineorders") {
				$bean->valueofjob_c = $bean->stotalplayers_c * $bean->swhatamountclubbecharged_c * 0.75;
			} else {
				$bean->valueofjob_c = $bean->stotalplayers_c * $bean->swhatamountclubbecharged_c;
			}
		}
	}

	function calculateJobValueOthers($bean){
		if ($bean->jobcategory_c != "Sports") {
			global $db;
			$sql = "UPDATE project_cstm SET valueofjob_c = (SELECT SUM(total_amount) FROM aos_quotes t
			INNER JOIN aos_quotes_cstm tc ON t.id = tc.id_c 
			INNER JOIN aos_quotes_project_c qp ON qp.aos_quotes1112_quotes_ida = t.id AND qp.deleted = 0 AND qp.aos_quotes7207project_idb = '{$bean->id}' WHERE  t.deleted = 0 AND tc.quotesstages_c = 'Accepted') 
			WHERE id_c = '{$bean->id}'";
			$res = $db->query($sql);
		}
	}

	function createFTPFolder($bean)
	{
		$year = substr($bean->jobid_c, 0, 2);
		if($bean->fetched_row['jobstatus_c'] != $bean->jobstatus_c
			&& $bean->jobstatus_c == 'ReadytoStart')
		{
			include_once('custom/include/FTPCrossCheck/ftpConnection.php');
	        $conn = FTPConnection::createFTPConnection();
	        if(!$conn || empty($conn)) {
	        	$GLOBALS['log']->fatal('Unbale to connect with FTP Server.',$this->conn);
	            return 0;

	        }
			$dir = '/'.$year.'/'.$bean->jobid_c;
	        if(!$this->FTPDirExist($conn, $dir)) {
	        	if (ftp_mkdir($conn, $dir)) {
					$GLOBALS['log']->fatal('FTP Directory cerated'.$dir);
				} else {
					$GLOBALS['log']->fatal('Failed to create FTP Directory for job '.$bean->jobid_c);
				}
	        }       
	        FTPConnection::FTPConnectionClose($conn);
		}
	}

	function FTPDirExist($conn, $dir)
	{
	    $origin = ftp_pwd($conn); 
	    if (@ftp_chdir($conn, $dir)) 
	    {
	        ftp_chdir($ftp, $origin);    
	        return true; 
	    }
	    return false; 
	}
}
