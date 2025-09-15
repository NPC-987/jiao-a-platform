// ==============================
// Auth.js - 全站會員登入管理模組
// ==============================
console.log("=== auth.js loaded at 20250909-1730 ===");
// 取得 Cookie
function getCookie(cname) {
    let name = cname + "=";
    let decodedCookie = decodeURIComponent(document.cookie);
    let ca = decodedCookie.split(';');
    for (let i = 0; i < ca.length; i++) {
        let c = ca[i].trim();
        if (c.indexOf(name) === 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}

// 設定 Cookie
function setCookie(cname, cvalue, exdays) {
    const d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    let expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

// 刪除 Cookie(登出用)
function deleteCookie(cname) {
    document.cookie = cname + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
}


// 檢查登入狀態
function checkLogin() {
    const uid = getCookie("Uid01");
    if (!uid) {
        renderLogoutUI();
        return;
    }

    $.ajax({
        type: "POST",
        url: "20250827-member-API.php?action=checkuid",//呼叫後端判斷uid01
        data: JSON.stringify({ "Uid01": uid }),
        dataType: "json",
        success: function (res) {
            if (res.state) {
                renderLoginUI(res.data); // 已登入
            } else {
                renderLogoutUI(); // 未登入
            }
        },
        error: function () {
            console.error("checkLogin 失敗");
            renderLogoutUI();
        }
    });
}


// 顯示登入狀態的 UI
function renderLoginUI(user) {
    console.log(user);
    //確保載入網頁時保留的暫存資料
    localStorage.setItem("role", user.role);
    localStorage.setItem("uid", user.id);

    // 隱藏登入/註冊
    $("#s02_login_btn").addClass("d-none");
    $("#s02_register_btn").addClass("d-none");

    // 判斷會員角色
    let roleText = "一般會員";
    if (user.role == 1) roleText = "高級會員";
    if (user.role == 9) roleText = "管理員";

    // 顯示會員名稱（兼容大小寫 key）
    const username = user.username || user.Username || user.user || "未命名會員";

    $("#login_showmessage").removeClass("d-none");
    $("#login_showmessage2").text(`${roleText}${username}`);
    $("#s06").removeClass("d-none");
    $("#s07").removeClass("d-none");
    $("#s08").removeClass("d-none");
    $("#s09").removeClass("d-none");

    // 管理員才顯示控制台
    if (user.role == 9) {//管理員
        $("#s02_control_btn").removeClass("d-none");
        $("#productlist").removeClass("d-none");//產品管理頁面
        $("#productadd").removeClass("d-none"); //新增產品頁面
    } else if (user.role == 1) {
        $("#s02_control_btn").addClass("d-none");
        $("#productadd").removeClass("d-none");
    } else if (user.role == 0) {
        $("#s02_control_btn").addClass("d-none");
    }

    // 顯示登出按鈕
    $("#logout_btn")
        .removeClass("d-none")
        .off("click")
        .on("click", function () {
            logout();//登出方法一
        });

    /* 登出方法二 
    $("#logout_btn").removeClass("d-none")
    .click(function(){
        //清空cookie
        setCookie("Uid01", "" ,7);
        //回到首頁
        location.href = "20250812-index.html";
    });
     */

}



// 顯示未登入狀態的 UI
function renderLogoutUI() {
    // 還原 UI
    $("#s02_login_btn").removeClass("d-none");
    $("#s02_register_btn").removeClass("d-none");
    $("#login_showmessage").addClass("d-none");//有寫沒寫沒差
    $("#login_showmessage2").text("");
    $("#s02_control_btn").addClass("d-none");
    $("#logout_btn").addClass("d-none");

 
    localStorage.removeItem("role");
    localStorage.removeItem("uid");
    console.log("已切換訪客模式，role:", localStorage.getItem("role"));
}


// 登出功能(刪除方法1)
function logout() {
    $.ajax({
        type: "POST",
        url: "20250827-member-API.php?action=logoutuser",
        dataType: "json",
        success: function (res) {
            renderLogoutUI();
            deleteCookie("Uid01");
            localStorage.clear();//瀏覽器的本地暫存區清除(clear)
            console.log("登出後 role:", localStorage.getItem("role"));
            Swal.fire({
                title: res.message || "已登出",//接收後端的message
                icon: "success",
                confirmButtonText: "OK"
            }).then(() => {
                // 2. 使用 replace() → 不留上一頁紀錄
                window.location.replace("20250812-index.html");
            });
        }
    });

}


// 頁面載入時就檢查
$(function () {
    checkLogin();
});
