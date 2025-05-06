<?php
error_reporting(E_ALL);
//ini_set("display_errors", 1);
ini_set("log_errors", 1);

class FriendshipStatus {
    const PENDING = "pending";
    const ACCEPTED = "accepted";
    const NOINTERACTION = "noInteraction";
}


class FriendshipManager {
    private $conn;
    private $senderID;
    private $receiverID;

    public function __construct($conn, $senderID, $receiverID){
        $this->conn = $conn;
        $this->senderID = $senderID;
        $this->receiverID = $receiverID;
    }

    /**
     * @param int senderID
     * @param int receiverID
     * @return array userdata / error message
     */
    public function searchFriend() {
    
        $sql = "SELECT UserID, Username, LastLogin FROM Users WHERE UserID = ?";
        $param = ["i", $this->receiverID];
        $stmt = $this->executeQuery($this->conn, $sql, $param);
    
        $resultArray = [];
        $stmt->bind_result($userID, $username, $lastlogin);
    
        if($stmt->fetch()){

            $status = $this->getFriendshipStatus();
    
            $resultArray = [
                "UserID" => $userID,
                "Username" => $username,
                "LastLogin" => $lastlogin,
                "Status" => $status
            ];
        }else{
            return ["Error" => "用戶ID不存在"];
        }

        return $resultArray;    
    }

    /**
    * click once for sending friend request
    * click twice for cancelling friend request
    * if already accepted, return status immediately
    * @return array show updated friend request status
    */
    public function clickFriendAction(){
        $status = $this->getFriendshipStatus();
        $sql = "";

        switch ($status) {
            case FriendshipStatus::NOINTERACTION:
                $sql = "INSERT INTO Friendship (SendID, RequestID) VALUES(?,?)";
                $status = FriendshipStatus::PENDING;
                break;

            case FriendshipStatus::PENDING:
                $sql = "DELETE FROM Friendship WHERE SendID = ? AND RequestID = ?";
                $status = FriendshipStatus::NOINTERACTION;
                break;

            case FriendshipStatus::ACCEPTED:
                return ["Status" => $status];
        }
        
        $param = ["ii", $this->senderID, $this->receiverID];
        $stmt = $this->executeQuery($this->conn, $sql, $param);

        return ["Status" => $status];
    }


    /**
    * accept A->B meanwhile create B->A with status='accept' (if no, then insert it)
    * client(senderID, B) is receiver in Friendship database A->B
    * @return array return error message only when an error occurs
    */
    public function acceptFriendRequest(){
        $this->conn->begin_transaction();

        try {
            $sql1 = "UPDATE Friendship SET Status = 'accepted' 
                    WHERE SendID = ? AND RequestID = ?";
            $param1 = ["ii", $this->receiverID, $this->senderID];
            $stmt1 = $this->executeQuery($this->conn, $sql1, $param1);

            $sql2 = "INSERT INTO Friendship (SendID, RequestID, Status) VALUES (?,?,'accepted')
                    ON DUPLICATE KEY UPDATE Status = VALUES(Status)";
            $param2 = ["ii", $this->senderID, $this->receiverID];
            $stmt2 = $this->executeQuery($this->conn, $sql2, $param2);

            $this->conn->commit();
        }
        catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error in acceptFriendRequest: " . $e->getMessage());
            return ["Error" => "Fail to accept friend request"];
        }
    }


    public function rejectFriendRequest(){
        try{
            //client(senderID, B) is receiver in Friendship database A->B
            $this->deleteFriendship($this->receiverID, $this->senderID); 
        }
        catch (Exception $e) {
            error_log("Error in rejectFriendRequest: " . $e->getMessage());
            return ["Error" => "Fail to reject friend request"];
        }
    }


    //delete A->B and B->A at the same time
    public function deleteFriend(){
        $this->conn->begin_transaction();

        try{
            $this->deleteFriendship($this->senderID, $this->recieverID);
            $this->deleteFriendship($this->recieverID, $this->senderID);

            $this->conn->commit();
        }
        catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error in deleteFriend: ". $e->getMessage());
            return ["Error" => "Fail to delete friend"];
        }
    }


    private function deleteFriendship($ppl1, $ppl2){
        $sql = "DELETE FROM Friendship WHERE SendID = ? AND RequestID = ? ";
        $param = ["ii", $ppl1, $ppl2];
        $stmt = $this->executeQuery($this->conn, $sql, $param);
    }

    /**
    * client(senderID) is receiver in Friendship Database
    * @return array array of user data that send friend request to you / error message
    */
    public function getFriendRequestListData(){
        try{
            $sql = "SELECT a.UserID, a.Username, a.LastLogin 
                    FROM Users as a
                    INNER JOIN Friendship ON a.UserID = Friendship.SendID
                    WHERE Friendship.RequestID = ? AND Status = 'pending'
                    ";
            $param = ["i", $senderID];
            $stmt = $this->executeQuery($this->conn, $sql, $param);
        
            $stmt->bind_result($userID, $username, $lastlogin);
        
            $resultArray = [];
            while($stmt->fetch()){
                $resultArray[] = [
                    "UserID" => $userID,
                    "Username" => $username,
                    "LastLogin" => $lastlogin
                ];
            }
        
            return $resultArray;
        }
        catch(Exception $e){
            error_log("Error in getFriendRequestListData: ". $e->getMessage());
            return ["Error" => "Fail to load Friend Request List"];
        }
    }


    /**
    * sql logic: 
    * 1. friend A in Users(A) must exist in Friendship(A->B, B->A)
    * 2. client(senderID) can not find himself
    * 3. each UserID can only show once (same person can only appear once)
    *** GROUP BY with multi-column only suitable for mysql
    */
    public function getFriendListData(){
        try{
            $sql = "SELECT a.UserID, a.Username, a.LastLogin
                    FROM Users as a
                    INNER JOIN Friendship ON (a.UserID = Friendship.SendID OR a.UserID = Friendship.RequestID)
                    WHERE Friendship.Status = 'accepted' AND a.UserID != ?
                    GROUP BY a.UserID
                    ";
            $param = ["i", $this->senderID];
            $stmt = $this->executeQuery($this->conn, $sql, $param);

            $stmt->bind_result($userID, $username, $lastlogin);
        
            $resultArray = [];
            while($stmt->fetch()){
                $resultArray[] = [
                    "UserID" => $userID,
                    "Username" => $username,
                    "LastLogin" => $lastlogin
                ];
            }
        
            return ["friends" => $resultArray];
        }
        catch(Exception $e){
            error_log("Error in getFriendListData: ". $e->getMessage());
            return ["Error" => "Fail to load Friend List"];
        }
    }
    
    /**
     * @return string FriendshipStatus
     */
    public function getFriendshipStatus(){
        $sql = "SELECT Status FROM Friendship WHERE SendID = ? AND RequestID = ?";
        $param = ["ii", $this->senderID, $this->receiverID];
        $stmt = $this->executeQuery($this->conn, $sql, $param);

        $stmt->bind_result($result);
    
        if(!$stmt->fetch()){
            return FriendshipStatus::NOINTERACTION;  //no such request in database
        }
        else {
            return $result;
        }
    }
    
    /**
     * Execute a prepared statement and returns the statment object
     * @return \mysql_stmt
     */
    private function executeQuery($conn, $sql, $param = []){
        $stmt = $conn->prepare($sql);
    
        if(!$stmt){
            throw new Exception("prepare stmt Fail: " . $conn->error);
        }
    
        if(!empty($param)){                 
            $type = array_shift($param);    
            $stmt->bind_param($type, ...$param);  
        }
    
        if(!$stmt->execute()){
            throw new Exception("Execute stmt Fail: " . $stmt->error);
        }
    
        return $stmt; 
    }


}


$servername = getenv("DB_HOST") ? : "localhost";
$username = getenv("DB_USER") ? : "root";
$password = getenv("DB_PASS") ? : "";
$dbname = getenv("DB_NAME") ? : "gameDemo";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    error_log("Error in Database Connection:" . $conn->connect_error);
    sendJSON(["error" => "Fail to connect Database, try again later"]);
    exit;
}

/**
 * only receive POST request
 */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    $data = json_decode(file_get_contents("php://input"), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Error in JSON decode:" . json_last_error_msg());
        sendJSON(["error" => " Invalid JSON input"]);
        exit;
    }

    $action = $data["Action"]; //string
    $senderID = intval($data["SenderID"]);
    $receiverID = intval($data["ReceiverID"]); 

    $friendshipManager = new FriendshipManager($conn, $senderID, $receiverID);
    $result = callFunctionByAction($action, $friendshipManager);
    sendJSON($result);
}


/**
 * execute function if exist in FriendShipManager, otherwise throw error msg
 */
function callFunctionByAction($function, $Manager){
    try{
        $result = [];
        
        if (method_exists($Manager, $function)) {
            $result = $Manager->$function();
        }else{
            throw new Exception("function '{$function}' does not exist");
        }

        return $result;
    }
    catch (Exception $e) {
        error_log("Error in function '${function}': " . $e->getMessage());
        return ["error" => "An Error occurred while processing your request."];
    }
}

function sendJSON($data){
    header("Content-Type: application/json");
    echo json_encode($data);
}

?>