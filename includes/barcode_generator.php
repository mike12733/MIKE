<?php
// Enhanced Barcode Generator for Equipment Tracking System

class BarcodeGenerator {
    
    /**
     * Generate a unique barcode for equipment
     * Format: EQ[YEAR][5-digit-sequential-number]
     */
    public static function generateUniqueBarcode($db) {
        $year = date('Y');
        $attempts = 0;
        $maxAttempts = 100;
        
        do {
            // Generate sequential number
            $lastBarcode = $db->fetch("SELECT barcode FROM equipment WHERE barcode LIKE ? ORDER BY barcode DESC LIMIT 1", ["EQ{$year}%"]);
            
            if ($lastBarcode) {
                $lastNumber = (int)substr($lastBarcode['barcode'], 6);
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }
            
            $barcode = 'EQ' . $year . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
            
            // Check if barcode already exists
            $existing = $db->fetch("SELECT id FROM equipment WHERE barcode = ?", [$barcode]);
            $attempts++;
            
        } while ($existing && $attempts < $maxAttempts);
        
        if ($attempts >= $maxAttempts) {
            throw new Exception("Unable to generate unique barcode after {$maxAttempts} attempts");
        }
        
        return $barcode;
    }
    
    /**
     * Generate SVG barcode image
     */
    public static function generateBarcodeSVG($barcode, $width = 300, $height = 100) {
        $bars = self::getBarsFromBarcode($barcode);
        $barWidth = $width / strlen($bars);
        
        $svg = '<?xml version="1.0" encoding="UTF-8"?>';
        $svg .= '<svg width="' . $width . '" height="' . $height . '" xmlns="http://www.w3.org/2000/svg">';
        $svg .= '<rect width="' . $width . '" height="' . $height . '" fill="white"/>';
        
        $x = 0;
        for ($i = 0; $i < strlen($bars); $i++) {
            if ($bars[$i] == '1') {
                $svg .= '<rect x="' . $x . '" y="10" width="' . $barWidth . '" height="' . ($height - 30) . '" fill="black"/>';
            }
            $x += $barWidth;
        }
        
        // Add barcode text
        $svg .= '<text x="' . ($width / 2) . '" y="' . ($height - 5) . '" text-anchor="middle" font-family="monospace" font-size="14" fill="black">' . $barcode . '</text>';
        $svg .= '</svg>';
        
        return $svg;
    }
    
    /**
     * Generate barcode as base64 data URL
     */
    public static function generateBarcodeDataURL($barcode, $width = 300, $height = 100) {
        $svg = self::generateBarcodeSVG($barcode, $width, $height);
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
    
    /**
     * Convert barcode to bar pattern (simplified Code 128)
     */
    private static function getBarsFromBarcode($barcode) {
        // Simplified barcode pattern generation
        // In production, you might want to use a proper Code 128 implementation
        $pattern = '';
        
        // Start pattern
        $pattern .= '11010010000';
        
        // Convert each character to bars
        foreach (str_split($barcode) as $char) {
            $pattern .= self::getCharacterBars($char);
        }
        
        // Stop pattern
        $pattern .= '1100011101011';
        
        return $pattern;
    }
    
    /**
     * Get bar pattern for a character
     */
    private static function getCharacterBars($char) {
        $patterns = [
            '0' => '11011001100',
            '1' => '11001101100',
            '2' => '11001100110',
            '3' => '10010011000',
            '4' => '10010001100',
            '5' => '10001001100',
            '6' => '10011001000',
            '7' => '10011000100',
            '8' => '10001100100',
            '9' => '11001001000',
            'A' => '11001000100',
            'B' => '11000100100',
            'C' => '10110011100',
            'D' => '10011011100',
            'E' => '10011001110',
            'F' => '10111001000',
            'G' => '10011101000',
            'H' => '10011100100',
            'I' => '11001110010',
            'J' => '11001011100',
            'K' => '11001001110',
            'L' => '11011100100',
            'M' => '11001110100',
            'N' => '11101101110',
            'O' => '11101001100',
            'P' => '11100101100',
            'Q' => '11100100110',
            'R' => '11101100100',
            'S' => '11100110100',
            'T' => '11100110010',
            'U' => '11011011000',
            'V' => '11011000110',
            'W' => '11000110110',
            'X' => '10100011000',
            'Y' => '10001011000',
            'Z' => '10001000110'
        ];
        
        return $patterns[$char] ?? '11001001000'; // Default pattern
    }
    
    /**
     * Validate barcode format
     */
    public static function validateBarcodeFormat($barcode) {
        return preg_match('/^EQ\d{7}$/', $barcode);
    }
    
    /**
     * Generate QR Code for equipment (alternative to barcode)
     */
    public static function generateQRCode($data, $size = 200) {
        // Using Google Charts API for QR code generation
        $qrUrl = "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl=" . urlencode($data);
        return $qrUrl;
    }
}
?>