<?php
//1.常數設定：資料庫連線參數
//使用 const 定義「不會改變」的參數（資料庫帳號、密碼等）
const DB_SERVER = "localhost";
const DB_USERNAME = "root";
const DB_PASSWORD = "";
const DB_NAME = "testdb";

//2.資料庫連線函式
//建立連線 封裝連線動作成函式，之後只要 create_connection() 就能快速建立連線
function create_connection()
{
    $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    // 🟡若連線失敗，回傳錯誤 JSON 並中斷程式
    if (!$conn) {
        echo json_encode(["state" => false, "data" => null, "message" => "連線失敗" . mysqli_connect_error()]);
        exit(); //連線失敗會直接回傳錯誤 JSON 並 exit() 停止執行
    }
    return $conn;
}

//取得所有的產品資料
//input:none
//output:{"state" : true, "data" : "所有的產品資料","message" : "讀取資料成功"}

//3.撈取資料主功能：get_all_product02_data
function get_all_product02_data()
{
    // 🧱 建立資料庫連線（使用上方封裝好的函式）
    $conn = create_connection();
    // 🧱撰寫 SQL 指令，準備從 product02 資料表中撈出所有資料，並依照 ID 倒序排序 可避免注入攻擊SQL injection
    $stmt = $conn->prepare("SELECT * FROM product02 ORDER BY ID DESC");
    // 🧱 執行 SQL 語句，並確認執行是否成功送出到資料庫
    $stmt->execute();
    // 🧱 取得查詢結果的結果集（ResultSet）
    $result = $stmt->get_result();
    // 🧱 檢查是否有撈到資料（num_rows 代表總筆數）
    if ($result->num_rows > 0) {
        // 建立空陣列準備裝查到的每一筆資料
        $mydata = array();
        // 🧱 一筆一筆從結果中取出資料（每筆資料是關聯式陣列）
        while ($row = $result->fetch_assoc()) {
            // 把這筆資料加到陣列中
            $mydata[] = $row;
        }
        // ✅ 全部資料準備完成，回傳 JSON 格式（成功）
        echo json_encode(["state" => true, "data" => $mydata, "message" => "讀取產品資料成功"]);
    } else {
        //❌ 沒有任何資料，回傳 JSON（失敗訊息）
        echo json_encode(["state" => false, "data" => null, "message" => "讀取資料失敗"]);
    }
}

//新增產品資料
//input : {"pane" : "美式咖啡", "price":" 99", "Pice": "微冰"}
//output: {"state": "true", "data":"所有的產品資料","message" : "讀取資料成功"}
function add_product()
{
    if (isset($_POST["seller_id"], $_POST["pname"], $_POST["price"], $_POST["pice"], $_POST["pnote"], $_FILES["file"]["name"])) {
        $seller_id = intval($_POST["seller_id"]); // 登入者ID
        $p_pname   = $_POST["pname"];
        $p_price   = $_POST["price"];
        $p_pice    = $_POST["pice"];
        $p_note    = $_POST["pnote"];

        // 價格上限檢查
        if ($p_price > 10000) {
            echo json_encode(["state" => false, "message" => "價格不得超過 10000 元"]);
            exit;
        }

        $conn = create_connection();

        // 圖檔名處理（避免重複）
        $filename = date("YmdHis") . '_' . uniqid() . '_' . $_FILES['file']['name'];
        $location = 'upload/' . $filename;

        // 檢查檔案格式
        if (in_array($_FILES['file']['type'], ['image/jpeg', 'image/png', 'image/gif'], true)) {
            // 在搬移檔案
            if (move_uploaded_file($_FILES['file']['tmp_name'], $location)) {

                $stmt = $conn->prepare("INSERT INTO product02 (seller_id, Pname, Price, Pice, Pimg, Pnote) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isisss", $seller_id, $p_pname, $p_price, $p_pice, $location, $p_note);

                if ($stmt->execute()) {
                    $datainfo = [
                        "seller_id" =>$seller_id,
                        "name"     => $_FILES['file']['name'],
                        "type"     => $_FILES['file']['type'],
                        "tmp_name" => $_FILES['file']['tmp_name'],
                        "error"    => $_FILES['file']['error'],
                        "size"     => $_FILES['file']['size'],
                        "location" => $location
                    ];

                    echo json_encode(["state" => true, "data" => $datainfo, "message" => "商品新增成功"]);
                } else {
                    echo json_encode(["state" => false, "message" => "新增失敗: " . $stmt->error]);
                }

                $stmt->close();
                $conn->close();
            } else {
                echo json_encode(["state" => false, "data" => null, "message" => "上傳圖片失敗: " . $_FILES['file']['error']]);
            }
        } else {
            echo json_encode(["state" => false, "data" => null, "message" => "圖片格式必須符合規定!"]);
        }
    } else {
        echo json_encode(["state" => false, "data" => null, "message" => "缺少必要欄位或檔案"]);
    }
}


//更新產品功能
//type:POST
//input: {"id":"xxxx","price":95 ,"pice":半糖 ,"pnote":內容}
//output: {"state" :true, "data" : NULL , message:"更新產品資料成功"}
function update_product()
{   // 1 取得前端傳來的 JSON 資料
    $data = file_get_contents("php://input");
    $input = json_decode($data, true);//把前端送來的 JSON 字串，轉成 PHP 的陣列或物件
    

    // 2 確認資料中有包含 'name' 和 'price'和'id'
    if (isset($input["id"], $input["price"], $input["pice"])) {
        $p_id = $input["id"];        // 產品ID（要更新哪一筆）
        $p_price = $input["price"];  // 新價格
        $p_pice  = $input["pice"];   // 狀態
        $p_note = $input["pnote"] ?? null;   // 說明 預設null


        if ($p_price > 10000) {
            echo json_encode(["state" => false, "message" => "價格不得超過 10000 元"]);
            exit;
        }
        if ($p_price <= 0) {
            echo json_encode(["state" => false, "message" => "價格必須大於 0"]);
            exit;
        }

        //確保三個欄位都不是空值（防止空白更新）
        if ($p_id && $p_price && $p_pice) {
            // 呼叫自定義函式 create_connection() 來建立與資料庫的連線
            $conn = create_connection();
            //撰寫 SQL 更新語句(要跟下面的順序一樣)
             $stmt = $conn->prepare("UPDATE product02 SET Price = ?, Pice = ?, Pnote = ? WHERE ID = ?");
            //綁定三個參數：價格、狀態、產品ID、說明
           $stmt->bind_param("dssi", $p_price, $p_pice, $p_note, $p_id);

            //執行 SQL
            if ($stmt->execute()) {
                //檢查是否真的有資料被更新
                if ($stmt->affected_rows === 1) {
                    echo json_encode(["state" => true, "data" => null, "message" => " 更新產品成功"]);
                } else {
                    // 執行成功但資料內容與原本相同 → 沒有真正更新
                    echo json_encode(["state" => false, "data" => null, "message" => "更新產品失敗,無資料被更新"]);
                }
            } else {
                // SQL 執行失敗
                echo json_encode(["state" => false, "data" => null, "message" => "更新產品失敗"]);
            }
            //  關閉連線
            $stmt->close();
            $conn->close();
        } else {
            // 有欄位是空的
            echo json_encode(["state" => false, "data" => null, "message" => "⚠️ 欄位不得為空白"]);
        }
    } else {
        // 欄位格式錯誤，無 name 或 price
        echo json_encode(["state" => false, "data" => null, "message" => "⚠️ 欄位錯誤"]);
    }
}


//刪除產品功能
//type:POST
//input: {"id":"xxxx"}
//output: {"state" :true, "data" : NULL , message:"刪除產品資料成功"}
function delete_product()
{   // 1 取得前端傳來的 JSON 資料
    $data = file_get_contents("php://input");
    $input = json_decode($data, true);
    if (isset($input["id"])) {
        $p_id = $input["id"]; //選取該id
        if ($p_id) {
            //刪除的語法
            $conn = create_connection();
            $stmt = $conn->prepare("DELETE FROM product02 WHERE ID = ?");
            $stmt->bind_param("s", $p_id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows === 1) {
                    echo json_encode(["state" => true, "data" => null, "message" => "刪除產品成功!"]);
                } else {
                    echo json_encode(["state" => false, "data" => null, "message" => "刪除產品失敗, 無資料被刪除!"]);
                }
            } else {
                echo json_encode(["state" => false, "data" => null, "message" => "刪除產品失敗!"]);
            }
            $stmt->close();
            $conn->close();
        } else {
            echo json_encode(["state" => false, "data" => null, "message" => "欄位不得為空白!"]);
        }
    } else {
        echo json_encode(["state" => false, "data" => null, "message" => "欄位錯誤!"]);
    }
}

//確認產品存在功能
//type:POST
//input: {"id":"xxxx"}
//output: {"state" :true, "data" : NULL , message:"產品名稱不存在 可以使用"}
//output: {"state" :false, "data" : NULL , message:"產品名稱已存在 不可以使用"}
function check_uni_product()
{
    $data = file_get_contents("php://input");
    $input = json_decode($data, true);
    if (isset($input["pname"])) {
        $p_pname    = $input["pname"];
        if ($p_pname) {
            $conn = create_connection();
            $stmt = $conn->prepare("SELECT Pname FROM product02 WHERE Pname = ?");
            $stmt->bind_param("s", $p_pname);

            //執行 SQL 查詢
            $stmt->execute();
            //取得查詢結果
            $result = $stmt->get_result();
            //判斷查詢結果的筆數
            if ($result->num_rows == 1) {
                //已存在 不可以用
                echo json_encode(["state" => false, "data" => null, "message" => "產品名稱已存在, 不可以使用!"]);
            } else {
                //不存在 可以用  
                echo json_encode(["state" => true, "data" => null, "message" => "產品名稱不存在, 可以使用!"]);
            }
            $stmt->close();
            $conn->close();
        } else {
            echo json_encode(["state" => false, "data" => null, "message" => "欄位不得為空白!"]);
        }
    } else {
        echo json_encode(["state" => false, "data" => null, "message" => "欄位錯誤!"]);
    }
}


//4.API 接收邏輯：只處理 GET 請求
//定自API呼叫的方法 如果請求方式是 GET，就執行撈資料的函式
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // 從網址中取得 action 參數，例如 ?action=getalldata
    $action = $_GET['action'] ?? '';
    // 判斷 action 要執行什麼功能
    switch ($action) {
        case 'getalldata':
            // 如果是 getalldata，執行撈取所有產品資料的函式
            get_all_product02_data();
            break;
        default:
            // 如果 action 不符合，回傳錯誤訊息（JSON 格式）
            echo json_encode(["state" => false, "data" => null, "message" => "無效操作"]);
    }
    // ✅ 若是 POST 請求，通常用來新增、更新、刪除資料
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ⚠️ 注意：這裡仍是從 $_GET 抓 action（因為你是從 URL 傳 action）
    $action = $_GET['action'] ?? '';
    // 根據 action 進行功能選擇
    switch ($action) {
        case 'addproduct':
            // 執行新增產品功能
            add_product();
            break;
        case 'updateproduct':
            // 執行更新產品功能
            update_product();
            break;
        case 'deleteproduct':
            //執行刪除產品功能
            delete_product();
            break;
        case 'checkuniproduct':
            //執行監聽功能
            check_uni_product();
            break;
        default:
            // 如果 action 無效，回傳錯誤訊息
            echo json_encode(["state" => false, "data" => null, "message" => "無效操作"]);
    }
}
