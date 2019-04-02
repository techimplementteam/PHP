<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Timesheets extends CI_Controller {

    /**
     *  Admin Panel for Codeigniter 
     *
     */
    public function __construct() {
        parent::__construct();
        $this->load->library('form_validation');
        if (!$this->session->userdata('is_portalAdmin_login')) {
            $this->load->view('portaladmin/vwLogin');
        }
    }
    
    public function index() {
        error_reporting(0);
        // existing saved settings
        if($_REQUEST['entryPoint']=='getjobtasks'){
            $freelancerID = $this->session->userdata('id');
            $jobId=$_REQUEST['jobid'];
            $this->fetchjobtasks($freelancerID,$jobId);
            die('');
        }
         $freelancerid_c = $this->session->userdata('id');;
        if($freelancerid_c!=''){
            $this->setfreelancetimelog($freelancerid_c);
        }
        if(isset($_POST['savetimelog']) || isset($_POST['submittimelog'])){
            $freelancerid_c = $this->session->userdata('id');
            $this->resetfreelancetilelog($freelancerid_c,$_POST['startdate']);
            $MainArray=array();
            
            for ($x = 1; $x <= $_POST['counttimelogslot']; $x++) {
                  $timeslotarray=array();
                  $timeslotarray['job'] = $_POST['job_'.$x];
                  $timeslotarray['jobtask'] = $_POST['jobtask_'.$x];
                  $timeslotarray['status'] = 'new';
                  
                 // timelognote_c
                  $timeslotarray['totalhour'] = $_POST[$x.'_8'];;
                  $timeslotarray['startdate'] = $_POST['startdate'];;
                  for ($z = 1; $z <= 7; $z++) {
                     $timeslotarray[$z] = $_POST[$x.'_'.$z];
                  }
                  $jsonObj=htmlspecialchars(json_encode($timeslotarray), ENT_QUOTES, 'UTF-8');
                  $ramdon=$this->incrementalHash(7);
                  $ramdon1=$this->incrementalHash(3);
                  $ramdon2=$this->incrementalHash(3);
                  $ramdon3=$this->incrementalHash(10);
                  if($freelancerid_c!='' && $_POST['job_'.$x]!='' && $_POST['jobtask_'.$x]!=''){
                    $data = array( 
                        'id_c'              =>  $ramdon.'-'.$ramdon1.'-'.$ramdon2 .'-'.$ramdon3 , 
                        'freelancerid_c'    =>  $freelancerid_c , 
                        'timelog_json_c'    =>  $jsonObj, 
                        'sectionflag_c'     =>  '1',
                        'startdate_c'       =>  $_POST['startdate'],
                        'timelogstatus_c'   =>  'new',
                        'jobid_c'           =>  $_POST['job_'.$x],
                        'timelognote_c'     => $_POST['timelognote_c'],
                        'jobtaskid_c'       =>  $_POST['jobtask_'.$x]
                    );
                    if(isset($_POST['submittimelog']) || $_POST['jobsubmit_'.$x]=='1'){
                        $data['submitvalue_c']=1;
                    }
                    $saved=0;
                    if(isset($_POST['savetimelog']) && $_POST['jobsubmit_'.$x]!='1'){
                        $data['savevalue_c']=1;
                    }
                    $this->updatetimelogs($data,$freelancerid_c);
                  }
              } 
              
              //Insert the Files
              
              $this->resetfreelancefiles($freelancerid_c,$_POST['startdate']);
              for ($x = 1; $x <= $_POST['countfileslot']; $x++) {
                   $fileslotarray=array();
                   $fileslotarray['filedesc'] = $_POST['desc_'.$x];                 
                   $ramdon=$this->incrementalHash(7);
                   $ramdon1=$this->incrementalHash(3);
                   $ramdon2=$this->incrementalHash(3);
                   $ramdon3=$this->incrementalHash(10);
                  
                   if(!empty($_FILES['jobfile_'.$x]) && $_FILES['jobfile_'.$x]['name']!='')  {
                      $path = "assets/uploads/";
                      $path = $path . basename( $_FILES['jobfile_'.$x]['name']);
                      if(move_uploaded_file($_FILES['jobfile_'.$x]['tmp_name'], $path)) {
                          $filename = basename( $_FILES['jobfile_'.$x]['name']);
                          $fileslotarray['filename'] = $filename;
                      } 
                    }else if($_FILES['jobfilename_'.$x]!=''){
                        $fileslotarray['filename'] = $_FILES['jobfilename_'.$x];
                    }
                    $jsonObjfile=htmlspecialchars(json_encode($fileslotarray), ENT_QUOTES, 'UTF-8');
                   if($freelancerid_c!=''){
                    $data = array( 
                        'id_c'              =>  $ramdon.'-'.$ramdon1.'-'.$ramdon2 .'-'.$ramdon3 , 
                        'freelancerid_c'    =>  $freelancerid_c , 
                        'invoice_reimbursement_file_c'    =>  $jsonObjfile, 
                        'sectionflag_c'     =>  '2',
                        'startdate_c'       =>  $_POST['startdate'],
                        'timelogstatus_c'   =>  'new',
                    );
                    $this->updatetimelogs($data,$freelancerid_c);
                  }
              }
        }
        if ($this->session->userdata['user_types'] == 'portalAdmin') {
            $portalID = $this->session->userdata['portalID'];
            $this->db->select('id, cLogo, cName, terms,footer');
            $settings = $this->db->get_where('eventphotography.configuration');
            $res = $settings->result();

             $id = $this->session->userdata('id');
             $data['freelancers_id']=$id; 
            $this->db->select();
            $pro = $this->db->get_where('project_task_cstm', array('fl_freelancers_id_c' => $id));
            $resMain = $pro->result();           
            $data['tasklist'] = $resMain;         
            $data['controllerObj']=$this; 
            $data['cLogo'] = $res[0]->cLogo;
            $data['cLogo'] = $res[0]->cLogo;
            $data['cName'] = $res[0]->cName;
            $data['terms'] = $res[0]->terms;
            $data['footer'] = $res[0]->footer;
            $data['page'] = 'profile';
            $data['portalID'] = $portalID;
            $this->load->view('portaladmin/timesheets', $data);
        } else {
            $id = $this->session->userdata('id');
            $this->db->select();
            $settings = $this->db->get_where('project_task_cstm', array('fl_freelancers_id_c' => $id));
            $resMain = $settings->result();
            $data['tasklist'] = $resMain;
            $message = $data['tasklist'];
            redirect('portaladmin/timesheets');
        }
    }
    public function updatetimelogs($data,$freelancerid_c){        
        $this->db->insert('tl_timelog_cstm', $data);
    }
    public function resetfreelancefiles($freelancerid_c,$startdate){
        $this->db->delete('tl_timelog_cstm', array('freelancerid_c' => $freelancerid_c,'startdate_c' => $startdate,'sectionflag_c' => 2)); 
    }
    public function resetfreelancetilelog($freelancerid_c,$startdate){
        $this->db->delete('tl_timelog_cstm', array('freelancerid_c' => $freelancerid_c,'startdate_c' => $startdate,'sectionflag_c' => 1)); 
    }
    public function fetchfreelancefiles($id,$StartDate){
        $pro = $this->db->get_where('tl_timelog_cstm', array('freelancerid_c' => $id,'startdate_c' => $StartDate,'sectionflag_c' => 2));
        $resMain = $pro->result();
        return $resMain;
    }
    public function fetchfreelancetimelogs($id,$StartDate){
        $pro = $this->db->get_where('tl_timelog_cstm', array('freelancerid_c' => $id,'startdate_c' => $StartDate,'sectionflag_c' => 1));
        $resMain = $pro->result();
 
        return $resMain;
    }
    public function incrementalHash($length){
        $randstr;
        srand((double) microtime(TRUE) * 1000000);
        //our array add all letters and numbers if you wish
        $chars = array(
            'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'p',
            'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', '1', '2', '3', '4', '5',
            '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 
            'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');

        for ($rand = 0; $rand <= $length; $rand++) {
            $random = rand(0, count($chars) - 1);
            $randstr .= $chars[$random];
        }
        return $randstr;
      }
    public function fetchjobs($id){        
        $freelancerID = $this->session->userdata('id');
        $query = $this->db->query("SELECT * FROM project_task AS a,project_task_cstm AS b WHERE a.id=b.`id_c` AND b.`fl_freelancers_id_c`='".$freelancerID."' AND a.id ='".$id."' AND b.rsvpstatus_c='Accepted' AND a.deleted = '0' ");
        $resMain = $query->result();
        return $resMain;                        
    }
    public function fetchjobtasks($freelancerId,$jobId){
        $query = $this->db->query("SELECT * FROM project_task AS a,project_task_cstm AS b WHERE a.id=b.`id_c` AND b.`fl_freelancers_id_c`='".$freelancerId."' AND a.project_id ='".$jobId."' AND b.rsvpstatus_c='Accepted' AND a.deleted = '0' ");
        foreach ($query->result() as $row) {
           
                if($row->jobrole_c=='Photographer'){
                     $options.='<option label="Photographer" value="Photographer">Photographer</option>';
                } else if($row->jobrole_c=='PhotographerFormalManager'){
                     $options.='<option label="Photographer (Formal Manager)" value="PhotographerFormalManager">Photographer (Formal Manager)</option>';
                } else if($row->jobrole_c=='PhotographerAssistant'){
                $options.='<option label="Photographer Assistant" value="PhotographerAssistant">Photographer Assistant</option>';
                } else if($row->jobrole_c=='SportsPhotographyAssistant'){
                $options.='<option label="Sports Photography Assistant" value="SportsPhotographyAssistant">Sports Photography Assistant</option>';
                } else if($row->jobrole_c=='VideoEditor'){
                $options.='<option label="Video Editor" value="VideoEditor">Video Editor</option>';
                } else if($row->jobrole_c=='PhotoEditor'){
                $options.='<option label="Photo Editor" value="PhotoEditor">Photo Editor</option>';
                } else if($row->jobrole_c=='PhotoEditorOnsite'){
                $options.='<option label="Photo Editor Onsite" value="PhotoEditorOnsite">Photo Editor Onsite</option>';
                } else if($row->jobrole_c=='VideoEditorOnsite'){
                $options.='<option label="Video Editor Onsite" value="VideoEditorOnsite">Video Editor Onsite</option>';
                } else if($row->jobrole_c=='Administration'){
                $options.='<option label="Administration" value="Administration">Administration</option>';
                } else if($row->jobrole_c=='PhotoBoothAttendant'){
                $options.='<option label="Photo Booth Attendant" value="PhotoBoothAttendant">Photo Booth Attendant</option>';
                } else if($row->jobrole_c=='VideoOperator'){
                $options.='<option label="Video Operator" value="VideoOperator">Video Operator</option>';
                } else if($row->jobrole_c=='VideoGoPro'){
                $options.='<option label="Video Go Pro" value="VideoGoPro">Video Go Pro</option>';
                } else if($row->jobrole_c=='VideoDrone'){
                $options.='<option label="Video Drone" value="VideoDrone">Video Drone</option>';
                } else if($row->jobrole_c=='Video360Degree'){
                $options.='<option label="Video 360 Degree" value="Video360Degree">Video 360 Degree</option>';
                } else if($row->jobrole_c=='GraphicArtDesign'){
                $options.='<option label="Graphic/ Art Design" value="GraphicArtDesign">Graphic/ Art Design</option>';
                } else if($row->jobrole_c=='PhotoBoothSupplier'){
                $options.='<option label="Photo Booth Supplier" value="PhotoBoothSupplier">Photo Booth Supplier</option>';
                 } else if($row->jobrole_c=='PhotoMiniLabPrinter'){
                $options.='<option label="Photo Mini Lab Printer" value="PhotoMiniLabPrinter">Photo Mini Lab Printer</option>';
                } else if($row->jobrole_c=='JobTraining'){
                $options.='<option label="Job Training" value="JobTraining">Job Training</option>';
                } 
        } 
        $options.='<option label="Set up/pull down" value="Set up/pull down">Set up/pull down</option>';
        $options.='<option label="Travel" value="Travel">Travel</option>';
        $options.='<option label="Administration" value="Administration">Administration</option>';
        $options.='<option label="Upload of files" value="Upload of files">Upload of files</option>';
        $options.='<option label="Editing and post production" value="Editing and post production">Editing and post production</option>';
        $options.='<option label="Training" value="Training">Training</option>';
        $dataid=$_REQUEST['dataid'];
        echo  '<select name="jobtask_'.$dataid.'" id="jobtask_'.$dataid.'" style="width: 150px;"  >'.$options.'</select>';
    }
    public function getcustomtaskdata($id){
        $this->db->select();
        $settings = $this->db->get_where('project_task_cstm', array('id_c' => $id));
        $resMain = $settings->result();
        return $resMain;
                        
    }
    public function setfreelancetimelog($id){
        $this->db->select();
        $settings = $this->db->get_where('ts_timesheets_cstm', array('fl_freelancers_id_c' => $id));
        $resMain = $settings->result();
        $haveData = '';
        foreach ($resMain as $val) {
            if($val->fl_freelancers_id_c!=''){
                $haveData='1';
            }
        }
        if($haveData==''){
            
            $ramdon=$this->incrementalHash(7);
            $ramdon1=$this->incrementalHash(3);
            $ramdon2=$this->incrementalHash(3);
            $ramdon3=$this->incrementalHash(10);
             $timelogid =      $ramdon.'-'.$ramdon1.'-'.$ramdon2 .'-'.$ramdon3;
            $data           =array();
            $data['id']   =$timelogid;
            $data['name']   =$this->session->userdata('name'); 
            $this->db->insert('ts_timesheets', $data);
            $insert_id = $this->db->insert_id();
            $data           =array();
            $data['id_c']   =$timelogid;
            $data['fl_freelancers_id_c']   =$id; 
            $this->db->insert('ts_timesheets_cstm', $data);
        }
    }
    public function fetchproject_tasks($id){
        $this->db->select();
        $settings = $this->db->get_where('project_task', array('project_id' => $id));
        $resMain = $settings->result();
        return $resMain;
                        
    }
    public function fetchprojects($id){
        $this->db->select();
        $settings = $this->db->get_where('project', array('id' => $id));
        $resMain = $settings->result();
        return $resMain;
                        
    }
    public function fetchprojects_c($id){
        $this->db->select();
        $settings = $this->db->get_where('project_cstm', array('id_c' => $id));
        $resMain = $settings->result();
        return $resMain;
                        
    }
    public function save() {

        $portalID = $this->session->userdata['portalID'];

        $this->db->select('id, cLogo, cName, terms');
        $settings = $this->db->get_where('eventphotography.configuration');
        $res = $settings->result();

        //File upload
        $config['upload_path']          = 'uploads/';
        $config['allowed_types']        = 'gif|jpg|png';
        $this->load->library('upload', $config);
        if($_FILES['cLogo']['tmp_name']!=''){
            if (! $this->upload->do_upload('cLogo')) {
                $error = array('error' => $this->upload->display_errors());
                $filename = '';
            } else {
                $data = array('upload_data' => $this->upload->data());
                $filename = $data['upload_data']['file_name'];
            }
            //---
            // build data array 
            $data = array(
                'cLogo' => $filename,
                'cName' => $_POST['cName'],
                'footer' => $_POST['footer'],
                'terms' => $_POST['footer']
            );
        }else{
           // build data array 
            $data = array(
                'cName' => $_POST['cName'],
                'footer' => $_POST['footer'],
                'terms' => $_POST['terms']
            ); 
        }
        

        // update data in database
        if (!empty($res)) {
            $this->db->update('configuration', $data);
        } else {
            $this->db->insert('configuration', $data);
        }

        $this->load->helper('url');
        redirect('/portaladmin/configuration');
    }

    public function testCRMSettings() {
        $activeCRM = $_POST['activeCRM'];

        $data = array(
            'instanceURL' => $_POST['instanceURL'],
            'instanceUserName' => $_POST['instanceUserName'],
            'instancePass' => $_POST['instancePass'],
        );

        if ($activeCRM == 'sugar') {
            $result = $this->_testSugarCRMSettings($data);
        }

        if ($result) {
            echo "Pass";
        } else {
            echo "Fail";
        }
    }

    //  HELPER FUNCTION

    private function _testSugarCRMSettings($data) {
        $base_url = $data['instanceURL'];
        $base_url = trim($base_url, '/');
        $base_url = trim($base_url, '/');
        $base_url = $base_url . "/rest/v10";

        $auth_url = $base_url . "/oauth2/token";

        $oauth2_token_arguments = array(
            "grant_type" => "password",
            //client id - default is sugar. 
            //It is recommended to create your own in Admin > OAuth Keys
            "client_id" => "sugar",
            "client_secret" => "",
            "username" => $data['instanceUserName'],
            "password" => $data['instancePass'],
            //platform type - default is base.
            //It is recommend to change the platform to a custom name such as "custom_api" to avoid authentication conflicts.
            "platform" => "base"
        );

        $auth_request = curl_init($auth_url);
        curl_setopt($auth_request, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($auth_request, CURLOPT_HEADER, false);
        curl_setopt($auth_request, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($auth_request, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($auth_request, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($auth_request, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json"
        ));

        //  convert arguments to json
        $json_arguments = json_encode($oauth2_token_arguments);
        curl_setopt($auth_request, CURLOPT_POSTFIELDS, $json_arguments);

        //  execute request
        $oauth2_token_response = curl_exec($auth_request);

        if (!$oauth2_token_response) {
            //  Invalid Credz
            return false;
        } else {
            $response = json_decode($oauth2_token_response, true);

            if (array_key_exists('access_token', $response)) {
                //  Valid Credz
                return true;
            } else {
                //  Invalid Credz
                return false;
            }
        }
    }

}
