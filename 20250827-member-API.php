<?php

//1.常數設定：資料庫連線參數
//使用 const 定義「不會改變」的參數（資料庫帳號、密碼等）
/* 虛擬機的
const DB_SERVER = "localhost";
const DB_USERNAME = "admin";
const DB_PASSWORD = "123456";
const DB_NAME = "testdb"; */
const DB_SERVER = "localhost";
const DB_USERNAME = "root";
const DB_PASSWORD = "";
const DB_NAME = "testdb";

//建立連線
function create_connection()
{
    $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if (!$conn) {
        echo json_encode(["state" => false, "data" => null, "message" => "連線失敗" . mysqli_connect_error()]);
        exit();
    }
    return $conn;
}

/* 取得JSON的資料 */
function get_json_input()
{
    $data = file_get_contents("php://input");
    return json_decode($data, true);
}

/* 模組化回復json格式 */
function respond($state, $message, $data = null)
{
    echo json_encode(["state" => $state, "data" => $data, "message" => $message]);
}

//註冊功能 register
//type: POST
//input: {"username" : "xxxx", "password" : "xxxxx", "email" : "xxxxx"}
//output: {"state" : true, "data" : NULL ,"message": "帳號註冊成功"}
function register_user()
{
    $input = get_json_input();
    if (isset($input["username"], $input["password"], $input["email"])) {
        $p_username = $input["username"];
        /* 使用 password_hash 將密碼加密後再存入資料庫 */
        $p_password = password_hash($input["password"], PASSWORD_DEFAULT);
        $p_email    = $input["email"];
        if ($p_username && $p_password && $p_email) {
            //要寫資料庫語法的地方 新增產品
            $conn = create_connection();
            $stmt = $conn->prepare("INSERT INTO member(Username, Password, Email) VALUES(?, ?, ?)");
            $stmt->bind_param("sss", $p_username, $p_password, $p_email);

            if ($stmt->execute()) {
                respond(true, "歡迎加入二手平台的一分子");
            } else {
                respond(false, "帳號註冊失敗!");
            }
            $stmt->close();
            $conn->close();
        } else {
            respond(false, "欄位不得為空白!");
        }
    } else {
        respond(false, "欄位錯誤!");
    }
}

//確認帳號是否存在功能
//type:POST
//input: {"username":"xxxx"}
//output: {"state" :true, "data" : NULL , message:"確認不存在 可以使用"}
//output: {"state" :false, "data" : NULL , message:"確認已存在 不可以使用"}
function check_uni_username()
{
    $input = get_json_input();
    if (isset($input["username"])) {
        $p_username  = $input["username"];
        if ($p_username) {
            $conn = create_connection();
            $stmt = $conn->prepare("SELECT Username FROM member WHERE Username = ?");
            $stmt->bind_param("s", $p_username);

            //執行 SQL 查詢
            $stmt->execute();
            //取得查詢結果
            $result = $stmt->get_result();
            //判斷查詢結果的筆數
            if ($result->num_rows == 1) {
                //已存在 不可以用
                respond(false, "帳號已存在不可以用");
            } else {
                //不存在 可以用  
                respond(true, "帳號不存在可以用");
            }
            $stmt->close();
            $conn->close();
        } else {
            respond(false, "欄位不得為空白");
        }
    } else {
        respond(false, "欄位錯誤");
    }
}

//會員登入
//type:POST
//input: {"username":"owner01","password":"Aa@123456"}
//output: {"state" :true, "data" : NULL , message:"登入成功"}
//output: {"state" :false, "data" : NULL , message:"登入失敗"}
/* 當資料庫查詢結果($result) 返回一條結果時 ($result->num_rows == 1)。
    fetch_assoc() 會從 $result 結果集中取出這唯一的一行數據。
    將這行數據轉換成一個關聯陣列，並賦值給 $row 變數 */
function login_user()
{
    $input = get_json_input();
    if (isset($input["username"], $input["password"])) {
        //trim — 去除字符串首尾处的空白字符（或者其他字符）
        $p_username = trim($input["username"]);
        $p_password = trim($input["password"]);
        if ($p_username && $p_password) {
            $conn = create_connection();
            $stmt = $conn->prepare("SELECT * FROM member WHERE Username = ?");
            $stmt->bind_param("s", $p_username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                //帳號比對成功, 繼續確認密碼是否正確
                $row = $result->fetch_assoc();  //$row["Password"]

                //密碼驗證
                if (password_verify($p_password, $row["Password"])) {

                    //登入成功 → 啟動 Session 讀取ID跟role
                    session_start();
                    $_SESSION['uid'] = $row['ID']; // 登入時存放會員ID
                    $_SESSION['role'] = $row['role']; // 存放角色

                    //驗證成功(登入成功)
                    //產生uid01 並更新置資料庫
                    $uid01 = substr(hash("sha512", uniqid(time())), 0, 6) . substr(hash("sha256", uniqid(time())), 12, 20);

                    $stmt = $conn->prepare("UPDATE member SET Uid01 = ? WHERE Username = ?");
                    $stmt->bind_param("ss", $uid01, $p_username);
                    $stmt->execute();

                    //取得該帳號的相關資訊傳給前端
                    $stmt = $conn->prepare("SELECT Username, Email ,Uid01,role FROM member WHERE Username = ?");
                    $stmt->bind_param("s", $p_username);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $userdata = $result->fetch_assoc();

                    respond(true, "登入成功", $userdata);
                } else {
                    //驗證失敗(登入失敗)
                    respond(false, "密碼錯誤");
                }
            } else {
                //此帳號不存在 可以用  
                respond(false, "登入失敗");
            }
            $stmt->close();
            $conn->close();
        } else {
            respond(false, "欄位不得為空白!");
        }
    } else {
        respond(false, "欄位錯誤!");
    }
}

/* 接收 cookie 裡的 Uid01 去資料庫查詢是否存在該 UID
如果存在 → 回傳 user 資料（Username、role 等）
如果不存在 → 回傳未登入 */
function check_uid()
{
    $input = get_json_input();

    if (isset($input["Uid01"])) {
        $uid01 = $input["Uid01"];
        if ($uid01) { //多一層防呆空直
            $conn = create_connection();
            $stmt = $conn->prepare("SELECT id, Username, role FROM member WHERE Uid01 = ?");
            $stmt->bind_param("s", $uid01);

            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows == 1) {
                $userdata = $result->fetch_assoc();
                respond(true, "驗證成功", $userdata);
            } else {
                respond(false, "驗證失敗");
            }
            $stmt->close();
            $conn->close();
        } else {
            respond(false, "缺少 Uid01");
        }
    } else {
        respond(false, "無效操作");
    }
}

//取得所有會員資料
//type:GET
//input: {"id":"xxxx"}
//output: {"state" :true, "data" : 所有會員資料(密碼除外) , message:"取得所有會員資料成功"}
//output: {"state" :false, "data" : NULL , message:"取得所有會員資料成功"}
function get_user_data()
{
    //這個是後端保護網 如果有人直接呼叫API就能串接取資料
    session_start();
    if (!isset($_SESSION['uid']) || $_SESSION['role'] != 9) {
        respond(false, "權限不足，請登入管理員帳號", null);
        exit();
    }

    $conn = create_connection();
    $stmt = $conn->prepare("SELECT * FROM member ORDER BY ID DESC"); //撈取全資料
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {

        $mydata = array();

        while ($row = $result->fetch_assoc()) {
            unset($row["Password"]); //撈取時排除密碼欄位
            $mydata[] = $row;
        }
        respond(true, "讀取會員資料成功", $mydata);
    } else {
        respond(false, "讀取會員資料失敗");
    }
}

//更新會員資料
//type:POST
//input: {"id":"xxxx","email":"xxxx"}
//output: {"state" :true, "data" : NULL , message:"更新成功"}
//output: {"state" :false, "data" : NULL , message:"更新失敗"}
function update_user()
{
    $input = get_json_input();
    if (isset($input["id"], $input["email"],)) {
        $p_id  = $input["id"];
        $p_email  = $input["email"];
        $p_role = $input["role"];
        $p_pwd = $input["password"] ?? "";

        if ($p_id && $p_email !== "") {
            $conn = create_connection();

            if (!empty($p_pwd)) { //有新的密碼就加密存入
                $hashed_pwd = password_hash($p_pwd, PASSWORD_DEFAULT); //官方建議的安全方式
                $stmt = $conn->prepare("UPDATE member SET Email = ?, role = ?, Password = ? WHERE ID = ?");
                $stmt->bind_param("sisi", $p_email, $p_role, $hashed_pwd, $p_id);
            } else {
                // 沒有修改密碼 → 只更新 Email 與 Role
                $stmt = $conn->prepare("UPDATE member SET Email = ?, role = ? WHERE ID = ?");
                $stmt->bind_param("sii", $p_email, $p_role, $p_id);
            }

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    respond(true, "會員資料更新成功");
                } else {
                    respond(false, "資料未變更");
                }
            } else {
                respond(false, "SQL 執行錯誤: " . $stmt->error);
            }

            $stmt->close();
            $conn->close();
        } else {
            respond(false, "欄位不得為空白");
        }
    } else {
        respond(false, "缺少必要欄位");
    }
}

//刪除會員資料功能
//type:POST
//input: {"id":"xxxx"}
//output: {"state" :true, "data" : NULL , message:"刪除產品資料成功"}
function delete_user()
{
    $input = get_json_input();
    if (isset($input["id"])) {
        $p_id = $input["id"]; //選取該id
        if ($p_id) {
            //刪除的語法
            $conn = create_connection();
            $stmt = $conn->prepare("DELETE FROM member WHERE ID = ?");
            $stmt->bind_param("s", $p_id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows === 1) {
                    respond(true, "刪除會員資料成功!");
                } else {
                    respond(false, "刪除資料失敗, 無資料被刪除!");
                }
            } else {
                respond(false, "刪除資料失敗!");
            }
            $stmt->close();
            $conn->close();
        } else {
            respond(false, "欄位不得為空白!");
        }
    } else {
        respond(false, "欄位錯誤!");
    }
}

// 登出功能 logout_user
// type: POST
// input: 無（僅需依照當前登入的 Session）
// output: {"state": true, "data": null, "message": "登出成功"}
//         {"state": false, "data": null, "message": "尚未登入"}
// 登出流程：
// 1. 啟動 session，確認目前是否有登入（$_SESSION['uid'] 是否存在）
// 2. 如果有 → 清除資料庫中的 Uid01（避免舊 Cookie 被重用）
// 3. 同時清除後端 session（session_unset + session_destroy）
// 4. 回傳成功訊息
function logout_user()
{
    session_start();

    $uid = $_SESSION['uid'];

    // 清除資料庫中的 Uid01
    $conn = create_connection();
    $stmt = $conn->prepare("UPDATE member SET Uid01 = NULL WHERE ID = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    // 清除 Session
    session_unset();
    session_destroy();

    respond(true, "登出成功", null);
}


//圖表api 分析會員等級
//type:GET
//input: {"id":"xxxx"}
//output: {"state" :true, "data" :會員等級統計數據 , message:"取得所有會員級統計數據成功"}
//output: {"state" :false, "data" : NULL , message:"取得所有會員級統計數據失敗"}
function get_user_Chart_role()
{

    $conn = create_connection();
    $stmt = $conn->prepare("SELECT role, COUNT(role) as count_role  FROM member GROUP BY role");
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $mydata = array();

        while ($row = $result->fetch_assoc()) {
            $mydata[] = $row;
        }
        respond(true, "讀取會員資料成功", $mydata);
    } else {
        respond(false, "讀取會員資料失敗");
    }
}

// 圖表api 分析會員登入次數
// type:GET
// output: {"state":true,"data":[{"username":"owner01","login_count":5}, ...]}
function get_user_login_count()
{
    $conn = create_connection();
    $stmt = $conn->prepare("SELECT Username, login_count FROM member ORDER BY login_count DESC");
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $mydata = array();
        while ($row = $result->fetch_assoc()) {
            $mydata[] = $row;
        }
        respond(true, "取得會員登入次數成功", $mydata);
    } else {
        respond(false, "沒有登入紀錄");
    }
}



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_GET['action'] ?? '';
    switch ($action) {
        case 'registeruser':
            register_user();
            break;
        case 'checkuniusername':
            check_uni_username();
            break;
        case 'loginuser':
            login_user();
            break;
        case 'updateuser':
            update_user();
            break;
        case 'deleteuser':
            delete_user();
            break;
        case 'checkuid':
            check_uid();
            break;
        case 'logoutuser':
            logout_user();
            break;
        default:
            respond(false, "無效的操作!");
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    switch ($action) {
        case 'getuserdata':
            get_user_data();
            break;
        case 'getuserchartrole':
            get_user_Chart_role();
            break;
        case 'getuserlogincount':
            get_user_login_count();
            break;
        default:
            respond(false, "無效的操作!");
    }
}
