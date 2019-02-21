<?php
 require_once "config.php";
 $response = array();
 if(isset($_GET['apicall'])){
	switch($_GET['apicall'])
	{
		case 'signup':
		//this part for registration
		if(isTheseParametersAvailable(array('reg_id','username','firstname','lastname','email','password','branch','hostel')))
		{
			$reg_id = $_POST['reg_id'];
			$username = $_POST['username'];
			$firstname = $_POST['firstname'];
			$lastname = $_POST['lastname'];
			$email = $_POST['email'];
			$password = $_POST['password'];
			$branch=$_POST['branch'];
			$hostel=$_POST['hostel'];
			$stmt = $conn->prepare("SELECT regid FROM users where regid=?");
			$stmt->bind_param("s",$reg_id);
			$stmt->execute();
			$stmt->store_result();
			if($stmt->num_rows>0)
			{
				$response['error']=true;
				$response['message']='User Already Registered';
				$stmt->close();
				
			}else{
				$stmt=$conn->prepare("INSERT INTO USERS (regid,username,pass,branch,hostel,first_name,last_name,email) VALUES(?,?,?,?,?,?,?,?)");
				$stmt->bind_param("ssssssss",$reg_id,$username,$password,$branch,$hostel,$firstname,$lastname,$email);
				if($stmt->execute())
				{
				 $stmt = $conn->prepare("SELECT regid,username,first_name,last_name,email,pass,branch,hostel FROM users where regid=?");
			     $stmt->bind_param("s",$reg_id);
				 $stmt->execute();
				 $stmt->bind_result($reg_id,$username,$firstname,$lastname,$email,$password,$branch,$hostel);
				 $stmt->fetch();
				 $user=array(
					'reg_id'=>$reg_id,
					'username'=>$username,
					'firstname'=>$firstname,
					'lastname'=>$lastname,
					'email'=>$email,
					'password'=>$password,
					'branch'=>$branch,
					'hostel'=>$hostel
				 );
				 $stmt->close();
				 $response['error']=false;
				 $response['message']='User Registered Succesfully';
				 $response['user']=$user;
				 
				 
				}
				
			}
			
		}else{
			     $response['error']=true;
				 $response['message']='Requried Parameteres Are Not Available';
				
		}
		break;
		case 'login':
		//this part for login
		if(isTheseParametersAvailable(array('reg_id','password')))
		{
			$reg_id=$_POST['reg_id'];
			$password = $_POST['password'];
			$stmt=$conn->prepare("SELECT regid,username,pass,first_name,last_name,email,branch,hostel FROM users WHERE regid=? AND pass=?");
			$stmt->bind_param("ss",$reg_id,$password);
			$stmt->execute();
			$stmt->store_result();
			if($stmt->num_rows>0)
			{
				$stmt->bind_result($reg_id,$username,$firstname,$lastname,$email,$password,$branch,$hostel);
				$stmt->fetch();
				$user=array(
					'reg_id'=>$reg_id,
					'username'=>$username,
					'firstname'=>$firstname,
					'lastname'=>$lastname,
					'email'=>$email,
					'password'=>$password,
					'branch'=>$branch,
					'hostel'=>$hostel
				);
				$response['error']=false;
				$response['message']='Login Succesfull';
				$response['user']=$user;
				
			}else{
				$response['error']=true;
				$response['message']='Invalid Reg ID Or Password';
			}
		}
		break;
		case 'addComplaint':
		if(isTheseParametersAvailable(array('reg_id','description','type','student_vis','faculty_vis',
		'title')))
		$reg_id=$_POST['reg_id'];
		$description=$_POST['description'];
		$type=$_POST['type'];
		$title=$_POST['title'];
		$student_vis=$_POST['student_vis'];
		$faculty_vis=$_POST['faculty_vis'];
		$up_vote="0";
		$down_vote="0";
		$is_resolved ="0";
		$complaint_id=NULL;
		$created_at=date('Y-m-d');
		$stmt = $conn->prepare("INSERT INTO COMPLAINTS (complaint_id,user_id,description,type,student_visibility,faculty_visibility
		,up_vote,down_vote,is_resolved,created_at,title) VALUES(?,?,?,?,?,?,?,?,?,?,?)");
		$stmt->bind_param("sssssssssss",$complaint_id,
										$reg_id,
										$description,
										$type,
										$student_vis,
										$faculty_vis,
										$up_vote,
										$down_vote,
										$is_resolved,
										$created_at,$title);
		if($stmt->execute()){
					//echo $type;
				$sql = "SELECT complaint_id FROM complaints ORDER BY complaint_id DESC LIMIT 1";
				$result = $conn->query($sql);
			
				if($result->num_rows>0)
				{
					  while($row = $result->fetch_assoc()) 
					  {
						  $complaint_id=$row['complaint_id'];
					  }
				}
				if($type=="1")
				{
				 
				 $hostel_name="boys";
				
			     $stmt1=$conn->prepare("INSERT INTO HOSTEL_COMPLAINTS (hostel_name,complaint_id,user_id) VALUES(?,?,?)");
				 $hostelSql = "insert into notifications(complaint_id,title,description,is_seen,created_at) values('$complaint_id','$title','$description',0,'$created_at')";
				 $stmt1->bind_param("sss",$hostel_name,$complaint_id,$reg_id);
				 mysqli_query($conn,$hostelSql);
				 $stmt1->execute();
				 $stmt1->close();
				}else if($type=="0")
				{
				$stmt2=$conn->prepare("INSERT INTO USER_COMPLAINTS (user_id,complaint_id) VALUES(?,?)");
				$instituteSql = "insert into notifications(complaint_id,title,description,is_seen,created_at) values('$complaint_id','$title','$description',0,'$created_at')";
				 $stmt2->bind_param("ss",$reg_id,$complaint_id);
				mysqli_query($conn,$instituteSql);
				$stmt2->execute();
				 $stmt2->close();
				}else{
				$stmt3=$conn->prepare("INSERT INTO INSTITUTE_COMPLAINTS (complaint_id,user_id) VALUES(?,?)");
				 $stmt3->bind_param("ss",$complaint_id,$reg_id);
				$stmt3->execute();
				 $stmt3->close();
				}
				
			    $response['error']=false;
				$response['message']='Complaint Added';
				
		}else{
			    $response['error']=true;
				$response['message']='Something Went Wrong';
		}
		$stmt->close();
		break;
		default :
		$response['error']=true;
		$response['message']='Invalid Operation Called';
	}	
 }else{
	 $response['error']=true;
	 $response['message']='Invalid Api Call';
	 
 }
 echo json_encode($response);
 function isTheseParametersAvailable($params){
	 foreach($params as $param)
	 {
		 if(!isset($_POST[$param])){
			 return false;
		 }
	 }
	 return true;
 }
?>