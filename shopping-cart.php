<?php
error_reporting(0);
//Setting session start
session_start();
$id = session_id();
$total = 0;
$sel_products = '';
$conn = oci_connect("username", "password", "shop");
if (!$conn) {
    $m = oci_error();
    echo $m['message'], "\n";
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : "";
$paid_action = isset($_GET['status']) ? $_GET['status'] : "";
function TranId($conn, $action)
{
    if ($action == 'addcart') {
        $get_id = getId($conn);
        TranInsert($conn, $get_id);
        return $get_id + 1;
    }
}

function getId($conn)
{
    $tran_id = "SELECT NVL(sh.trace_number, 0) FROM shop_transaction sh";
    $tran_id_parse = oci_parse($conn, $tran_id);
    oci_execute($tran_id_parse);
    $tran_id_result = oci_fetch_row($tran_id_parse);
    return $tran_id_result[0];
}

function TranInsert($conn, $trace_id)
{
    $trace_id = $trace_id + 1;
    $tid = oci_parse($conn, 'update shop_transaction set trace_number = ' . $trace_id . '');
    oci_execute($tid);
    oci_commit($conn);
}

//Add to cart
if ($action == 'addcart' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    //product find hiih
    $query1 = "SELECT * FROM products WHERE sku='" . $_POST['sku'] . "'";
    $stmt = oci_parse($conn, $query1);
    oci_execute($stmt);
    $product = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_NULLS);

    $currentQty = $_SESSION['products'][$_POST['sku']]['qty'] + 1;
    $_SESSION['products'][$_POST['sku']] = array('qty' => $currentQty, 'name' => $product['NAME'], 'image' => $product['IMAGE'], 'price' => $product['PRICE']);
    $product = '';
    header("Location:shopping-cart.php?pid=" . TranId($conn, $action) . "&status=prepaid");
}
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'paid' || $_GET['status'] == 'failed') {
        $data = array("trace_id" => $_GET['pid'], "src_amount" => $_SESSION['total_amount'], "merchant" => 279);
        $data_string = json_encode($data);

        $headers[] = 'ewa-session: 077e29b11be80ab57e1a2ecabb7da330';
        $headers[] = 'Accept: application/json';
        $headers[] = 'Content-Type: application/json';

        $cURL = curl_init();
        $url = 'http://ewa.api/api/checkTxn';
        curl_setopt($cURL, CURLOPT_URL, $url);
        curl_setopt($cURL, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($cURL, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($cURL, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cURL, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($cURL, CURLOPT_HTTPHEADER, $headers);
        $result = json_decode(trim(curl_exec($cURL)));

        $_SESSION['products'] = array();
        echo '<div style="text-align: center; color: green;"><h4>' . $result->msg . '</h4></div>';
    }
}

//Empty All
if ($action == 'emptyall') {
    $_SESSION['products'] = array();
    TranId($conn, 'addcart');
    header("Location:shopping-cart.php");
}

//Empty one by one
if ($action == 'empty') {
    $sku = $_GET['sku'];
    $products = $_SESSION['products'];
    unset($products[$sku]);
    $_SESSION['products'] = $products;
    header("Location:" . $_SERVER['HTTP_REFERER'] . "");
}

//Get all Products
$query = "SELECT * FROM products";
$stmt = oci_parse($conn, $query);
oci_execute($stmt);
//$products = oci_fetch_array($stmt, OCI_ASSOC+OCI_RETURN_NULLS);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>E-SHOP TEST</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <script src="jquery.min.js"></script>
    <script src="qrcode.js"></script>
</head>
<body>
<style type="text/css">
    .btn {
        padding: 3px 6px;
    }

    .navbar {
        min-height: 30px;
    }

    .navbar-brand {
        float: left;
        height: 30px;
        padding: 11px 10px;
        font-size: 16px;
        line-height: 5px;
    }
</style>
<div style="text-align: center; color: green;"><h4 id="payment_status"></h4></div>
<div class="container" style="width:700px;">
    <?php if (!empty($_SESSION['products'])): $_SESSION['total_amount'] = 0; ?>
        <nav class="navbar navbar-inverse" style="background:#04B745;">
            <div class="container-fluid pull-left" style="width:300px;">
                <div class="navbar-header"><a class="navbar-brand" href="#" style="color:#FFFFFF;">Сонгогдсон
                        бараанууд</a></div>
            </div>
            <div class="pull-right" style="margin:2px 2px;"><a href="shopping-cart.php?action=emptyall"
                                                               class="btn btn-info">Цуцлах</a></div>
        </nav>
        <table class="table table-striped" style="margin-bottom: 30px;">
            <thead>
            <tr>
                <th>Бараа</th>
                <th>Нэр</th>
                <th>Үнэ</th>
                <th>Тоо/ш</th>
                <th>Үйлдэл</th>
            </tr>
            </thead>
            <?php foreach ($_SESSION['products'] as $key => $product): ?>
                <tr>
                    <td><img src="<?php print $product['image'] ?>" width="50"></td>
                    <td width="40%"><?php print $product['name'] ?></td>
                    <td><?php print $product['price'] ?>төг</td>
                    <td><?php print $product['qty'] ?>ш</td>
                    <td><a href="shopping-cart.php?action=empty&sku=<?php print $key ?>" class="btn btn-info">Болих</a>
                    </td>
                </tr>
                <?php
                $total = $total + $product['price'] * $product['qty'];
                $_SESSION['total_amount'] = $total;
                $sel_products = $sel_products . $product['name'] . '[' . $product['qty'] . ']';
                ?>
            <?php endforeach; ?>
            <tr>
                <td colspan="2" align="left"><h5 style="font-weight: bold">Нийт дүн: <?php print $total ?>төг</h5></td>
                <td colspan="2"><select style="height: 35px;" id="select_pay_type">
                        <option value="null">Төлбөрийн хэлбэрээ сонгоно уу</option>
                        <option value="ewa_qr">EWA QR кодоор төлөх</option>
                        <option value="ewa_username">EWA хэрэглэгчийн эрхээр төлөх</option>
                        <option value="ewa_phone">EWA-д бүртгэлтэй утасны дугаараар төлөх</option>
                    </select></td>
                <td align="right">
                    <button onclick="Payment(<?php echo $total . ',' . $_GET['pid']; ?>)"
                            class="btn btn-info">
                        Төлөх
                    </button>
                </td>
            </tr>
        </table>
    <?php endif; ?>
    <div id="qrcode" style="padding-left: 200px; margin-bottom: 20px;"></div>
    <div id="phone_payment">
        <form method="post" action="#">
            <p style="text-align:center;">
                <input type="number" name="phone_number" id="phone_number" placeholder="Утас" style="height: 40px; width: 200px;">
                <input type="number" name="confirm_code" id="confirm_code" placeholder="Код" style="height: 40px; width: 90px;">
                <input type="hidden" name="trace_id" id="trace_id" value="">
                <button id="get_code" type="button" class="btn btn-warning" onclick="getOTP(<?php echo $total; ?>)">Код авах</button>
                <button id="phone_pay" type="button" class="btn btn-success" onclick="ConfirmPay()">Төлөх</button>
            </p>
        </form>
    </div>

    <nav class="navbar navbar-inverse" style="background:#04B745; height: 30px;">
        <div class="container-fluid">
            <div class="navbar-header"><a class="navbar-brand" href="#" style="color:#FFFFFF;">Бүтээгдэхүүн</a></div>
        </div>
    </nav>
    <div class="row">
        <div class="container" style="width:600px;">
            <?php while (($product = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_NULLS)) != false) { ?>
                <div class="col-md-4">
                    <div class="thumbnail"><img src="<?php print $product['IMAGE'] ?>" alt="Lights"/>
                        <div class="caption">
                            <p style="text-align:center;"><?php echo $product['NAME'] ?></p>
                            <p style="text-align:center;color:#04B745;"><b><?php echo $product['PRICE'] ?>₮</b></p>
                            <form method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>?action=addcart">
                                <p style="text-align:center;color:#04B745;">
                                    <button type="submit" class="btn btn-warning">Нэмэх</button>
                                    <input type="hidden" name="sku" value="<?php echo $product['SKU'] ?>">
                                </p>
                            </form>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>

<h1 id="bigOne"></h1>
<script src="https://www.gstatic.com/firebasejs/4.7.0/firebase.js"></script>
<script>
    // Initialize Firebase
    var me = 'd395771085aab05244a4fb8fd91bf4ee';// md db link
    var config = {
        apiKey: "AIzaSyAqxL9TXMWCe043VpWxYrRH6HJA5K1-q4g",
        authDomain: "ewa-demo.firebaseapp.com",
        databaseURL: "https://ewa-demo.firebaseio.com",
        projectId: "ewa-demo",
        storageBucket: "ewa-demo.appspot.com",
        messagingSenderId: "384757488855"
    };
    firebase.initializeApp(config);

    var bigOne = document.getElementById('bigOne');
    var dbRef = firebase.database().ref().child('merchants').child(me).child('transactions');
    dbRef.on('child_added', function (data) {
        if (data.val().trace_id == <?php echo getId($conn) ?>) {
            alert(data.val().status);
            //$('#payment_status').text(status);
            window.location.replace("http://e-shop.app/shopping-cart.php?action=emptyall");
        }
        //addCommentElement(postElement, data.key, data.val().text, data.val().author);
    });

    //bigOne.innerText = snap.val()

</script>
<script>
    var popup;
    $('#phone_payment').hide();
    function Payment(pay, pid) {
        if (document.getElementById("select_pay_type").value == 'ewa_username') {
            $("#qrcode").empty();
            $("#qrcode").hide();
            $('#phone_payment').hide();
            popup = window.open("http://ewa.api/api/shop_signin/" + pay + "/279/" + pid + "", "popup", "toolbar=no,scrollbars=yes,resizable=no,top=100,left=500,width=400,height=500");
            if (window.focus) {
                popup.focus();
            }
            return false
        }
        if (document.getElementById("select_pay_type").value == 'ewa_qr') {
            $('#phone_payment').hide();
            $("#qrcode").empty();
            $("#qrcode").show();
            $("#qrcode").css({
                'width': 150,
                'height': 150
            });
            // Generate and Output QR Code
            var qrdata = '{"user_id":279,"amount":' + pay + ',"trace_id":' + pid + ',"first_name":"Minii Delguur","last_name":"LLC","content":"<?php echo $sel_products; ?>","user_type":"business","profile_img":"/images/profile/5a2915b3cefd7.png","otp":"' + pid + '","rand":' + Math.random() + '}';
            $("#qrcode").qrcode({width: 150, height: 150, text: qrdata});
        }

        if (document.getElementById("select_pay_type").value == 'ewa_phone') {
            $("#qrcode").empty();
            $("#qrcode").hide();
            $('#phone_pay').hide();
            $('#phone_payment').show();
        }
    }

    function getOTP(pay) {
        var get_phone_number = $('#phone_number').val();
        $.ajax({
            url: 'curl.php',
            data: {
                phone: get_phone_number,
                src_amount: pay,
                content: "pay for phone",
                request: "otp"
            },
            type: 'POST',
            success: function (response) {
                var json = $.parseJSON(response);
                if (json.code == 0) {
                    $('#get_code').hide();
                    $('#phone_pay').show();
                    $("#trace_id").val(json.result);
                } else {
                    $('#payment_status').text(json.msg);
                }
            }
        });
    }
    
    function ConfirmPay() {
        $.ajax({
            url: 'curl.php',
            data: {
                id: $('#trace_id').val(),
                code: $('#confirm_code').val(),
                request: "confirm"
            },
            type: 'POST',
            success: function (response) {
                var json = $.parseJSON(response);
                $('#payment_status').text(json.msg);
            }
        });
    }
</script>
</body>
</html>