<?php
require_once __DIR__ . '/../vendor/autoload.php';


use Picqer\Barcode\BarcodeGeneratorPNG;

class BarcodeGeneratorClass {

    public static function generateBarcode($data, $filename) {
        $generator = new BarcodeGeneratorPNG();

        // Generate barcode PNG (Code 128)
        // $barcode = $generator->getBarcode($data, $generator::TYPE_CODE_128);
        $barcode=$generator->getBarcode($data, $generator::TYPE_CODE_128, $widthFactor = 2, $height = 50);


        // Create directory if not exists
        $barcode_dir = 'assets/barcode/';
        if (!file_exists($barcode_dir)) {
            mkdir($barcode_dir, 0777, true);
        }

        $file_path = $barcode_dir . $filename . '.png';
        file_put_contents($file_path, $barcode);

        return $file_path;
    }
}

// Usage
$barcode_file = BarcodeGeneratorClass::generateBarcode("BILL202509074598", "bill_7155");
// echo "<img src='{$barcode_file}' alt='Barcode'>";
?>
