<?php
require_once 'config/session.php';
// Function: Convert numbers to words (Indian system)
requireRole('staff');
function numberToWords($num) {
    $ones = array(
        "", "One", "Two", "Three", "Four", "Five", "Six",
        "Seven", "Eight", "Nine", "Ten", "Eleven", "Twelve",
        "Thirteen", "Fourteen", "Fifteen", "Sixteen",
        "Seventeen", "Eighteen", "Nineteen"
    );
    $tens = array(
        "", "", "Twenty", "Thirty", "Forty", "Fifty",
        "Sixty", "Seventy", "Eighty", "Ninety"
    );

    if ($num == 0) return "Zero";

    $result = "";

    if ($num >= 100000) {
        $result .= numberToWords(intval($num / 100000)) . " Lakh ";
        $num %= 100000;
    }
    if ($num >= 1000) {
        $result .= numberToWords(intval($num / 1000)) . " Thousand ";
        $num %= 1000;
    }
    if ($num >= 100) {
        $result .= numberToWords(intval($num / 100)) . " Hundred ";
        $num %= 100;
    }
    if ($num > 0) {
        if ($num < 20) {
            $result .= $ones[$num] . " ";
        } else {
            $result .= $tens[intval($num / 10)];
            if ($num % 10 > 0) {
                $result .= " " . $ones[$num % 10];
            }
        }
    }

    return trim($result) . " Only";
}

// Process form after submission
$total = 0;
$totalWords = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $items = $_POST['items'];
    $qty = $_POST['qty'];
    $cost = $_POST['cost'];

    for ($i = 0; $i < count($items); $i++) {
        $total += ($qty[$i] * $cost[$i]);
    }
    $totalWords = numberToWords($total);
}




?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Order Form</title>
<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h2 { text-align: center; text-decoration: underline; }
    table { width: 100%; border-collapse: collapse; margin: 15px 0; }
    th, td { border: 1px solid #000; padding: 8px; text-align: center; }
    th { background: #f2f2f2; }
    .section { margin-top: 20px; }
</style>
 
</head>
<body>

<h2>ORDER FORM</h2>
    <a class="btn btn-outline-light btn-sm me-2" href="javascript:history.back()">
        <i class="fas fa-arrow-left"></i> Back
    </a>
 <a class="btn btn-outline-light btn-sm" href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>

<form method="post">

<!-- Header -->
<table>
<tr>
  <td><b>Order No:</b> <input type="text" name="order_no"></td>
  <td><b>Department:</b> <input type="text" name="department"></td>
  <td><b>Program Name:</b> <input type="text" name="program"></td>
</tr>
<tr>
  <td><b>Date:</b> <input type="date" name="date"></td>
  <td><b>Supply Date:</b> <input type="date" name="supply_date"></td>
  <td></td>
</tr>
</table>

<!-- Items Table -->
<table>
  <tr>
    <th>S.No</th>
    <th>Item</th>
    <th>Place & Time of Supply (FN/AN)</th>
    <th>Quantity</th>
    <th>Unit Cost</th>
    <th>Amount</th>
  </tr>

<?php
$menu = ["Tea / Coffee","Snacks","Breakfast Menu","Lunch Menu","Dinner Menu","Water Bottle","Others"];
for ($i=0; $i<count($menu); $i++) {
    $q = $_POST['qty'][$i] ?? "";
    $c = $_POST['cost'][$i] ?? "";
    $amt = ($q && $c) ? $q*$c : "";
    echo "<tr>
        <td>".($i+1)."</td>
        <td><input type='hidden' name='items[]' value='".$menu[$i]."'>".$menu[$i]."</td>
        <td><input type='text' name='place[]'></td>
        <td><input type='number' name='qty[]' value='$q'></td>
        <td><input type='number' name='cost[]' value='$c'></td>
        <td>".($amt ? number_format($amt,2) : "")."</td>
    </tr>";
}
?>

<tr>
    <td colspan="5" style="text-align:right"><b>Total:</b></td>
    <td><b><?php echo number_format($total,2); ?></b></td>
</tr>
<tr>
    <td colspan="6"><b>Total in Words:</b> <?php echo $totalWords; ?></td>
</tr>
</table>

<!-- Declaration -->
<div class="section">
  <p>
    The memo for the above is to be raised in favour of 
    <input type="text" name="memo_favour" style="width:300px"> 
    and the same will be settled by me at an early date.
  </p>
</div>

<!-- Ordered By -->
<div class="section">
  <p><b>Ordered by:</b></p>
  <p>Signature: __________________________</p>
  <p>Name: <input type="text" name="ordered_name"></p>
  <p>Designation: <input type="text" name="ordered_designation"></p>
  <p>Contact No: <input type="text" name="contact_no"></p>
  <p>On behalf of: <input type="text" name="on_behalf"></p>
  <p>Designation: <input type="text" name="on_behalf_desig"></p>
</div>

<!-- Office Use -->
<div class="section">
  <h3>For Office Use</h3>
  <p>Memo No: <input type="text" name="memo_no"> &nbsp;&nbsp; Date: <input type="date" name="memo_date"></p>
  <p>Amount (in Rs.): <input type="text" name="amount"></p>
  <p>Accounted by: __________________________</p>
  <p>Overall Order: __________________________</p>
</div>

<div class="section">
    <button type="submit">Submit</button>
</div>

</form>

</body>
</html>
