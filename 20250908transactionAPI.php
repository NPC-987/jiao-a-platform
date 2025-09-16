<?php
//建立購買/交換的API
/* 虛擬機的
const DB_SERVER = "localhost";
const DB_USERNAME = "admin";
const DB_PASSWORD = "123456";
const DB_NAME = "testdb"; */
/* const DB_SERVER = "localhost";
const DB_USERNAME = "root";
const DB_PASSWORD = "";
const DB_NAME = "testdb"; */
const DB_SERVER = "btonfk1fezevlaynxohd-mysql.services.clever-cloud.com";
const DB_USERNAME = "uhpsngd8tma9dx2f";
const DB_PASSWORD = "SnBYocGCFJy7debiiA1B";
const DB_NAME = "btonfk1fezevlaynxohd";

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
    error_log("RAW INPUT: " . $data);
    return json_decode($data, true);
}

/* 模組化回復json格式 */
function respond($state, $message, $data = null)
{
    echo json_encode(["state" => $state, "data" => $data, "message" => $message]);
}


//建立購買
//input{"buyer_id": 1,"seller_id": 2,"product_id": 10}
//output{"state": true, "data": {"transaction_id": 15,"type": "purchase"},"message": "購買交易建立成功"}

function create_purchase_transaction()
{
    $input = get_json_input();

    if (isset($input["buyer_id"], $input["seller_id"], $input["product_id"])) {
        $buyer_id  = (int)$input["buyer_id"];
        $seller_id = (int)$input["seller_id"];
        $product_id = (int)$input["product_id"];
        $shipping_method = $input["shipping_method"] ?? "";
        $note = $input["note"] ?? "";

        $conn = create_connection();

        // 檢查會員是否存在
        $stmt = $conn->prepare("SELECT role FROM member WHERE id = ?");
        $stmt->bind_param("i", $buyer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (!$user) {
            respond(false, "會員不存在", null);
            return;
        }

        // 插入交易（購買）
        $stmt = $conn->prepare("INSERT INTO transactions 
            (buyer_id, seller_id, product_id, type, status, shipping_method, note, exchange_title, exchange_desc, exchange_img) 
            VALUES (?, ?, ?, 'purchase', 'pending', ?, ?, NULL, NULL, NULL)");

        $stmt->bind_param("iiiss", $buyer_id, $seller_id, $product_id, $shipping_method, $note);

        if ($stmt->execute()) {
            respond(true, "購買交易建立成功", [
                "transaction_id" => $stmt->insert_id,
                "type" => "purchase"
            ]);
        } else {
            respond(false, "新增失敗: " . $stmt->error, null);
        }

        $stmt->close();
        $conn->close();
    } else {
        respond(false, "缺少必要欄位", null);
    }
}




//建立交換
// input : {"buyer_id":1, "seller_id":2, "product_id":10, "type":"purchase", "exchange_product_id":20}
// output: {"state": true, "data": {"exchange_id":15, "type":"exchange"}, "message":"交換建立成功"}
function create_exchange_transaction()
{
    $input = $_POST; // 注意：交換有檔案，所以用 $_POST + $_FILES

    if (isset($input["buyer_id"], $input["seller_id"], $input["product_id"], $_FILES['exchange_img'])) {
        $buyer_id  = (int)$input["buyer_id"];
        $seller_id = (int)$input["seller_id"];
        $product_id = (int)$input["product_id"];
        $title = $input["exchange_title"] ?? "";
        $desc  = $input["exchange_desc"] ?? "";

        $conn = create_connection();

        // 檢查會員 & 角色權限
        $stmt = $conn->prepare("SELECT role FROM member WHERE id = ?");
        $stmt->bind_param("i", $buyer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (!$user) {
            respond(false, "會員不存在", null);
            return;
        }

        if ($user['role'] < 1) {
            respond(false, "權限不足：一般會員無法進行交換", null);
            return;
        }

        // 處理圖片
        $filename = date("YmdHis") . '_' . uniqid() . '_' . $_FILES['exchange_img']['name'];
        $location = 'exchange/' . $filename;

        if (!move_uploaded_file($_FILES['exchange_img']['tmp_name'], $location)) {
            respond(false, "圖片上傳失敗", null);
            return;
        }

        // 插入交易（交換）
        $stmt = $conn->prepare("INSERT INTO transactions 
            (buyer_id, seller_id, product_id, type, status, shipping_method, note, exchange_title, exchange_desc, exchange_img) 
            VALUES (?, ?, ?, 'exchange', 'pending', NULL, NULL, ?, ?, ?)");

        $stmt->bind_param("iisss", $buyer_id, $seller_id, $product_id, $title, $desc, $location);

        if ($stmt->execute()) {
            respond(true, "交換交易建立成功", [
                "transaction_id" => $stmt->insert_id,
                "type" => "exchange"
            ]);
        } else {
            respond(false, "新增失敗: " . $stmt->error, null);
        }

        $stmt->close();
        $conn->close();
    } else {
        respond(false, "缺少必要欄位", null);
    }
}






if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_GET['action'] ?? '';
    switch ($action) {
        case 'createpurchasetransaction':
            create_purchase_transaction();
            break;
        case 'createexchangetransaction':
            create_exchange_transaction();
            break;
        default:
            respond(false, "無效的操作!");
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    switch ($action) {


        default:
            respond(false, "無效的操作!");
    }
}
